<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketStatusLog;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskFlowTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $manager;
    private User $maintenance;
    private User $cleaning;
    private BuildingUserRole $managerMembership;
    private BuildingUserRole $maintenanceMembership;
    private BuildingUserRole $cleaningMembership;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->manager = $this->makeUser('09121110001', 'مدیر');
        $this->building = Building::create([
            'name' => 'برج تسک‌ها',
            'code' => 'TSK001',
            'created_by' => $this->manager->id,
        ]);
        $this->managerMembership = $this->membership($this->manager, 'manager');
        $this->maintenance = $this->makeUser('09121110002', 'تعمیرات');
        $this->maintenanceMembership = $this->membership($this->maintenance, 'maintenance');
        $this->cleaning = $this->makeUser('09121110003', 'نظافت');
        $this->cleaningMembership = $this->membership($this->cleaning, 'cleaning');
    }

    public function test_manager_can_create_task_and_assign_it_to_a_role(): void
    {
        Notification::fake();
        Queue::fake();

        $roleId = Role::where('key', 'maintenance')->value('id');
        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->post(route('tickets.store'), [
                'title' => 'تعمیر پمپ آب',
                'description' => 'پمپ آب موتورخانه بررسی و تعمیر شود.',
                'category' => 'maintenance',
                'priority' => 'high',
                'target_role_id' => $roleId,
                'location' => 'موتورخانه',
            ])
            ->assertRedirect();

        $ticket = Ticket::withoutGlobalScopes()->where('kind', 'task')->firstOrFail();
        $this->assertSame('تعمیر پمپ آب', $ticket->title);
        $this->assertSame('TSK', substr($ticket->number, 0, 3));
        $this->assertDatabaseHas('ticket_assignees', [
            'building_id' => $this->building->id,
            'ticket_id' => $ticket->id,
            'user_id' => $this->maintenance->id,
        ]);
        $this->assertDatabaseMissing('ticket_assignees', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->cleaning->id,
        ]);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'type' => 'created',
        ]);
    }

    public function test_assigned_staff_can_view_and_update_task_but_unassigned_staff_cannot_view_it(): void
    {
        Queue::fake();
        $ticket = app(CurrentBuilding::class)->actingAs($this->building->id, function () {
            $ticket = Ticket::create([
                'building_id' => $this->building->id,
                'number' => 'TSK-TEST-001',
                'kind' => 'task',
                'title' => 'تست تسک',
                'description' => 'شرح تست',
                'category' => 'maintenance',
                'priority' => 'medium',
                'status' => 'new',
                'created_by' => $this->manager->id,
            ]);
            \App\Models\TicketAssignee::create([
                'building_id' => $this->building->id,
                'ticket_id' => $ticket->id,
                'user_id' => $this->maintenance->id,
                'assigned_by' => $this->manager->id,
                'assigned_at' => now(),
            ]);

            return $ticket;
        });

        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->get(route('tickets.show', $ticket->id))
            ->assertOk()->assertSee('تست تسک');

        $this->actingAs($this->cleaning)->withSession($this->sessionFor($this->cleaningMembership))
            ->get(route('tickets.show', $ticket->id))
            ->assertForbidden();

        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->post(route('tickets.claim', $ticket->id))->assertRedirect();
        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->post(route('tickets.status', $ticket->id), ['status' => 'doing', 'note' => 'کار شروع شد'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'doing']);
        $this->assertDatabaseHas('ticket_status_logs', [
            'ticket_id' => $ticket->id,
            'from_status' => 'new',
            'to_status' => 'doing',
        ]);
    }

    public function test_assigned_staff_can_claim_and_comment_on_a_task(): void
    {
        $ticket = app(CurrentBuilding::class)->actingAs($this->building->id, function () {
            $ticket = Ticket::create([
                'building_id' => $this->building->id,
                'number' => 'TSK-TEST-002',
                'kind' => 'task',
                'title' => 'تسک قابل قبول',
                'description' => 'شرح تسک',
                'category' => 'maintenance',
                'priority' => 'medium',
                'status' => 'new',
                'created_by' => $this->manager->id,
            ]);
            \App\Models\TicketAssignee::create([
                'building_id' => $this->building->id,
                'ticket_id' => $ticket->id,
                'user_id' => $this->maintenance->id,
                'assigned_by' => $this->manager->id,
                'assigned_at' => now(),
            ]);

            return $ticket;
        });

        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->post(route('tickets.claim', $ticket->id))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'accepted_by' => $this->maintenance->id,
            'status' => 'review',
        ]);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'type' => 'claimed',
        ]);

        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->post(route('tickets.comments.store', $ticket->id), ['body' => 'قطعه موردنیاز بررسی شد'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->maintenance->id,
            'body' => 'قطعه موردنیاز بررسی شد',
        ]);
    }

    public function test_manager_can_edit_task_details_and_reassign_staff(): void
    {
        Notification::fake();
        Queue::fake();
        $ticket = app(CurrentBuilding::class)->actingAs($this->building->id, function () {
            $ticket = Ticket::create([
                'building_id' => $this->building->id,
                'number' => 'TSK-TEST-003',
                'kind' => 'task',
                'title' => 'عنوان اولیه',
                'description' => 'شرح اولیه',
                'category' => 'maintenance',
                'priority' => 'medium',
                'status' => 'review',
                'accepted_by' => $this->maintenance->id,
                'created_by' => $this->manager->id,
            ]);
            \App\Models\TicketAssignee::create([
                'building_id' => $this->building->id,
                'ticket_id' => $ticket->id,
                'user_id' => $this->maintenance->id,
                'assigned_by' => $this->manager->id,
                'assigned_at' => now(),
                'accepted_at' => now(),
            ]);

            return $ticket;
        });

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('tickets.edit', $ticket->id))
            ->assertOk()->assertSee('ویرایش تسک');

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->put(route('tickets.update', $ticket->id), [
                'title' => 'عنوان به‌روزشده',
                'description' => 'شرح به‌روزشده',
                'category' => 'cleaning',
                'priority' => 'high',
                'user_ids' => [$this->cleaning->id],
            ])
            ->assertRedirect(route('tickets.show', $ticket->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'عنوان به‌روزشده',
            'category' => 'cleaning',
            'priority' => 'high',
            'accepted_by' => null,
        ]);
        $this->assertDatabaseHas('ticket_assignees', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->cleaning->id,
        ]);
        $this->assertDatabaseMissing('ticket_assignees', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->maintenance->id,
        ]);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'type' => 'updated',
        ]);
    }

    public function test_task_routes_require_building_scoped_permissions(): void
    {
        $this->actingAs($this->cleaning)->withSession($this->sessionFor($this->cleaningMembership))
            ->get(route('tickets.create'))
            ->assertForbidden();

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('tickets.index'))
            ->assertOk()->assertSee('مدیریت تیکت و تسک');
    }

    public function test_editing_task_preserves_acceptance_for_assignees_who_remain_assigned(): void
    {
        Notification::fake();
        Queue::fake();

        $acceptedAt = now()->subMinutes(5);
        $ticket = app(CurrentBuilding::class)->actingAs($this->building->id, function () use ($acceptedAt) {
            $ticket = Ticket::create([
                'building_id' => $this->building->id,
                'number' => 'TSK-TEST-004',
                'kind' => 'task',
                'title' => 'تسک پذیرفته‌شده',
                'description' => 'شرح اولیه',
                'category' => 'maintenance',
                'priority' => 'medium',
                'status' => 'review',
                'accepted_by' => $this->maintenance->id,
                'created_by' => $this->manager->id,
            ]);
            \App\Models\TicketAssignee::create([
                'building_id' => $this->building->id,
                'ticket_id' => $ticket->id,
                'user_id' => $this->maintenance->id,
                'assigned_by' => $this->manager->id,
                'assigned_at' => now()->subHour(),
                'accepted_at' => $acceptedAt,
            ]);

            return $ticket;
        });

        $this->assertSame(
            $acceptedAt->toDateTimeString(),
            \App\Models\TicketAssignee::withoutGlobalScopes()
                ->where('building_id', $this->building->id)
                ->where('ticket_id', $ticket->id)
                ->where('user_id', $this->maintenance->id)
                ->value('accepted_at')?->toDateTimeString()
        );

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->put(route('tickets.update', $ticket->id), [
                'title' => 'تسک پذیرفته‌شده ویرایش‌شده',
                'description' => 'شرح به‌روزشده',
                'category' => 'maintenance',
                'priority' => 'high',
                'user_ids' => [$this->maintenance->id],
            ])
            ->assertRedirect(route('tickets.show', $ticket->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'accepted_by' => $this->maintenance->id,
        ]);
        $this->assertSame(
            $acceptedAt->toDateTimeString(),
            \App\Models\TicketAssignee::withoutGlobalScopes()
                ->where('building_id', $this->building->id)
                ->where('ticket_id', $ticket->id)
                ->where('user_id', $this->maintenance->id)
                ->value('accepted_at')?->toDateTimeString()
        );
        $this->assertSame(
            1,
            \App\Models\TicketAssignee::withoutGlobalScopes()
                ->where('building_id', $this->building->id)
                ->where('ticket_id', $ticket->id)
                ->where('user_id', $this->maintenance->id)
                ->count()
        );
    }

    public function test_staff_cannot_claim_a_completed_task_and_manager_must_approve_completion(): void
    {
        $ticket = app(CurrentBuilding::class)->actingAs($this->building->id, function () {
            $ticket = Ticket::create([
                'building_id' => $this->building->id,
                'number' => 'TSK-TEST-APPROVAL',
                'kind' => 'task',
                'title' => 'تسک نیازمند تأیید',
                'description' => 'شرح تست',
                'category' => 'maintenance',
                'priority' => 'medium',
                'status' => 'done',
                'created_by' => $this->manager->id,
            ]);
            \\App\\Models\\TicketAssignee::create([
                'building_id' => $this->building->id,
                'ticket_id' => $ticket->id,
                'user_id' => $this->maintenance->id,
                'assigned_by' => $this->manager->id,
                'assigned_at' => now(),
            ]);
            return $ticket;
        });

        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->post(route('tickets.claim', $ticket->id))->assertConflict();

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->post(route('tickets.status', $ticket->id), ['status' => 'closed'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'closed']);
    }

    private function makeUser(string $mobile, string $name): User
    {
        return User::create([
            'mobile' => $mobile,
            'first_name' => $name,
            'last_name' => 'تست',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
    }

    private function membership(User $user, string $role): BuildingUserRole
    {
        return BuildingUserRole::create([
            'building_id' => $this->building->id,
            'user_id' => $user->id,
            'role_id' => Role::where('key', $role)->value('id'),
            'status' => 'active',
        ]);
    }

    private function sessionFor(BuildingUserRole $membership): array
    {
        return [
            'current_building_id' => $this->building->id,
            'current_building_user_role_id' => $membership->id,
        ];
    }
}
