<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
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
        $this->assertSame('review', $ticket->status->value);
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
            ->get(route('my.requests.show', $ticket->id))->assertOk()->assertSee('خرابی پمپ آب')->assertSee('در حال بررسی');
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
