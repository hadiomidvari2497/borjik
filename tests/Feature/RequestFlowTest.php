<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusLog;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $manager;
    private User $resident;
    private User $maintenance;
    private User $cleaning;
    private BuildingUserRole $managerMembership;
    private BuildingUserRole $residentMembership;
    private BuildingUserRole $maintenanceMembership;
    private BuildingUserRole $cleaningMembership;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->manager = $this->makeUser('09120000001', 'مدیر');
        $this->building = Building::create(['name' => 'برج درخواست‌ها', 'code' => 'RQ001', 'created_by' => $this->manager->id]);
        $this->managerMembership = $this->membership($this->manager, 'manager');
        $this->resident = $this->makeUser('09120000002', 'ساکن');
        $this->residentMembership = $this->membership($this->resident, 'tenant');
        $this->maintenance = $this->makeUser('09120000003', 'تعمیرات');
        $this->maintenanceMembership = $this->membership($this->maintenance, 'maintenance');
        $this->cleaning = $this->makeUser('09120000004', 'نظافت');
        $this->cleaningMembership = $this->membership($this->cleaning, 'cleaning');
    }

    public function test_request_is_visible_to_manager_and_matching_staff_but_not_unrelated_staff(): void
    {
        $this->actingAs($this->resident)->withSession($this->sessionFor($this->residentMembership))
            ->post(route('my.requests.store'), [
                'title' => 'خرابی پمپ آب', 'description' => 'پمپ آب ساختمان کار نمی‌کند.',
                'category' => 'maintenance', 'priority' => 'high', 'location' => 'موتورخانه',
            ])->assertRedirect();

        $ticket = Ticket::withoutGlobalScopes()->where('kind', 'request')->firstOrFail();
        $this->assertSame('new', $ticket->status->value);
        $this->assertDatabaseHas('ticket_assignees', [
            'building_id' => $this->building->id, 'ticket_id' => $ticket->id, 'user_id' => $this->maintenance->id,
        ]);

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('requests.index'))->assertOk()->assertSee('خرابی پمپ آب');
        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->get(route('requests.index'))->assertOk()->assertSee('خرابی پمپ آب');
        $this->actingAs($this->cleaning)->withSession($this->sessionFor($this->cleaningMembership))
            ->get(route('requests.index'))->assertOk()->assertDontSee('خرابی پمپ آب');
        $this->actingAs($this->resident)->withSession($this->sessionFor($this->residentMembership))
            ->get(route('my.requests.show', $ticket->id))->assertOk()->assertSee('خرابی پمپ آب')->assertSee('ثبت‌شده');
    }


    public function test_request_is_unassigned_until_first_matching_staff_claims_it(): void
    {
        $this->actingAs($this->resident)->withSession($this->sessionFor($this->residentMembership))->post(route('my.requests.store'), ['title' => 'خرابی آسانسور', 'description' => 'آسانسور خراب است.', 'category' => 'maintenance', 'priority' => 'high'])->assertRedirect();
        $ticket = Ticket::withoutGlobalScopes()->where('kind', 'request')->firstOrFail();
        $this->assertNull($ticket->accepted_by);
        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))->post(route('requests.claim', $ticket->id))->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'accepted_by' => $this->maintenance->id]);
        $this->assertDatabaseHas('ticket_events', ['ticket_id' => $ticket->id, 'type' => 'claimed']);
        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))->post(route('requests.comments.store', $ticket->id), ['body' => 'در حال بررسی', 'reply_to_id' => null])->assertRedirect()->assertSessionHasNoErrors();
        $parent = TicketComment::withoutGlobalScopes()->where('ticket_id', $ticket->id)->firstOrFail();
        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))->post(route('requests.comments.store', $ticket->id), ['body' => 'پاسخ مشخص', 'reply_to_id' => $parent->id])->assertRedirect();
        $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'body' => 'پاسخ مشخص', 'reply_to_id' => $parent->id]);

    }

    public function test_a_request_can_only_be_claimed_once_and_duplicate_claims_are_rejected(): void
    {
        $this->actingAs($this->resident)->withSession($this->sessionFor($this->residentMembership))
            ->post(route('my.requests.store'), [
                'title' => 'بررسی تابلو برق', 'description' => 'تابلو نیاز به بررسی دارد.',
                'category' => 'maintenance', 'priority' => 'medium',
            ])->assertRedirect();

        $ticket = Ticket::withoutGlobalScopes()->where('kind', 'request')->firstOrFail();
        $session = $this->sessionFor($this->maintenanceMembership);

        $this->actingAs($this->maintenance)->withSession($session)
            ->post(route('requests.claim', $ticket->id))->assertRedirect();
        $this->actingAs($this->maintenance)->withSession($session)
            ->post(route('requests.claim', $ticket->id))->assertConflict();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'accepted_by' => $this->maintenance->id]);
        $this->assertDatabaseCount('ticket_events', 4);
        $this->assertDatabaseHas('ticket_events', ['ticket_id' => $ticket->id, 'type' => 'claimed', 'user_id' => $this->maintenance->id]);
    }

    public function test_closed_request_cannot_be_claimed_by_staff(): void
    {
        $this->actingAs($this->resident)->withSession($this->sessionFor($this->residentMembership))
            ->post(route('my.requests.store'), [
                'title' => 'چراغ پارکینگ', 'description' => 'چراغ روشن نمی‌شود.',
                'category' => 'maintenance', 'priority' => 'low',
            ])->assertRedirect();

        $ticket = Ticket::withoutGlobalScopes()->where('kind', 'request')->firstOrFail();

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->post(route('requests.status', $ticket->id), ['status' => 'closed', 'note' => 'درخواست بسته شد.'])
            ->assertRedirect();

        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->post(route('requests.claim', $ticket->id))->assertConflict();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'accepted_by' => null, 'status' => 'closed']);
    }

    public function test_manager_can_view_building_scoped_request_performance_report(): void
    {
        $createdAt = now()->subDays(2);
        $resolvedAt = now()->subDay();

        app(CurrentBuilding::class)->actingAs($this->building->id, function () use ($createdAt, $resolvedAt) {
            $resolvedTicket = Ticket::create([
                'building_id' => $this->building->id, 'number' => 'REQ-REPORT-001',
                'kind' => 'request', 'title' => 'درخواست حل‌شده', 'description' => 'تست گزارش',
                'category' => 'maintenance', 'priority' => 'medium', 'status' => 'done',
                'created_by' => $this->resident->id, 'created_at' => $createdAt, 'updated_at' => $resolvedAt,
            ]);
            TicketStatusLog::create([
                'building_id' => $this->building->id, 'ticket_id' => $resolvedTicket->id,
                'from_status' => 'doing', 'to_status' => 'done', 'user_id' => $this->manager->id,
                'note' => 'حل شد', 'created_at' => $resolvedAt,
            ]);
            Ticket::create([
                'building_id' => $this->building->id, 'number' => 'REQ-REPORT-002',
                'kind' => 'request', 'title' => 'درخواست باز', 'description' => 'تست گزارش',
                'category' => 'general', 'priority' => 'low', 'status' => 'new',
                'created_by' => $this->resident->id,
            ]);
        });

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('requests.reports'))
            ->assertOk()->assertSee('گزارش عملکرد رسیدگی')
            ->assertSee('درخواست ثبت‌شده در بازه')->assertSee('درخواست حل‌شده در بازه')
            ->assertSee('میانگین زمان رسیدگی');

        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->get(route('requests.reports'))->assertForbidden();
    }

    private function makeUser(string $mobile, string $name): User
    {
        return User::create([
            'mobile' => $mobile, 'first_name' => $name, 'last_name' => 'تست',
            'status' => 'active', 'profile_completed_at' => now(),
        ]);
    }

    private function membership(User $user, string $role): BuildingUserRole
    {
        return BuildingUserRole::create([
            'building_id' => $this->building->id, 'user_id' => $user->id,
            'role_id' => Role::where('key', $role)->value('id'), 'status' => 'active',
        ]);
    }

    private function sessionFor(BuildingUserRole $membership): array
    {
        return ['current_building_id' => $this->building->id, 'current_building_user_role_id' => $membership->id];
    }
}
