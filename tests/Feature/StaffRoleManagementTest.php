<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private Building $otherBuilding;
    private User $owner;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::create(['mobile' => '09120000001', 'first_name' => 'مالک', 'last_name' => 'تست', 'status' => 'active', 'profile_completed_at' => now()]);
        $this->manager = User::create(['mobile' => '09120000002', 'first_name' => 'مدیر', 'last_name' => 'تست', 'status' => 'active', 'profile_completed_at' => now()]);
        $this->building = Building::create(['name' => 'ساختمان یک', 'code' => '11111', 'created_by' => $this->owner->id]);
        $this->otherBuilding = Building::create(['name' => 'ساختمان دو', 'code' => '22222', 'created_by' => $this->owner->id]);
        $this->join($this->owner, $this->building, 'owner');
        $this->join($this->manager, $this->building, 'manager');
        $this->join($this->owner, $this->otherBuilding, 'owner');
    }

    private function join(User $user, Building $building, string $key): void
    {
        BuildingUserRole::create([
            'building_id' => $building->id,
            'user_id' => $user->id,
            'role_id' => Role::whereNull('building_id')->where('key', $key)->value('id'),
            'status' => 'active',
        ]);
    }

    private function as(User $user, Building $building)
    {
        return $this->actingAs($user)->withSession(['current_building_id' => $building->id]);
    }

    public function test_manager_can_create_update_and_delete_a_custom_staff_role(): void
    {
        $this->as($this->manager, $this->building)->get(route('staff-roles.index'))->assertOk();

        $this->as($this->manager, $this->building)->post(route('staff-roles.store'), [
            'name_fa' => 'مسئول تأسیسات',
            'permissions' => ['ticket.view.assigned', 'ticket.respond'],
        ])->assertRedirect(route('staff-roles.index'));

        $role = Role::where('building_id', $this->building->id)->where('name_fa', 'مسئول تأسیسات')->firstOrFail();
        $this->assertTrue($role->is_staff);
        $this->assertFalse($role->is_default);
        $this->assertSame(['ticket.respond', 'ticket.view.assigned'], $role->permissions()->orderBy('key')->pluck('key')->all());

        $this->as($this->manager, $this->building)->put(route('staff-roles.update', $role->id), [
            'name_fa' => 'سرپرست تأسیسات',
            'permissions' => ['ticket.respond'],
        ])->assertRedirect(route('staff-roles.index'));
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name_fa' => 'سرپرست تأسیسات']);
        $this->assertSame(['ticket.respond'], $role->fresh()->permissions()->pluck('key')->all());

        $this->as($this->manager, $this->building)->delete(route('staff-roles.destroy', $role->id))
            ->assertRedirect(route('staff-roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_resident_requests_are_assigned_to_the_selected_custom_role(): void
    {
        $staff = User::create(['mobile' => '09120000003', 'first_name' => 'پرسنل', 'last_name' => 'تست', 'status' => 'active', 'profile_completed_at' => now()]);
        $resident = User::create(['mobile' => '09120000004', 'first_name' => 'ساکن', 'last_name' => 'تست', 'status' => 'active', 'profile_completed_at' => now()]);
        $role = Role::create([
            'building_id' => $this->building->id,
            'key' => 'custom_request_route',
            'name_fa' => 'تأسیسات ویژه',
            'is_staff' => true,
            'is_default' => false,
            'is_active' => true,
        ]);
        $role->permissions()->sync(Role::whereNull('building_id')->where('key', 'maintenance')->firstOrFail()->permissions()->pluck('permissions.id')->all());
        BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $staff->id, 'role_id' => $role->id, 'status' => 'active']);
        $this->join($resident, $this->building, 'tenant');

        $this->as($resident, $this->building)->post(route('my.requests.store'), [
            'title' => 'خرابی تأسیسات ویژه',
            'description' => 'درخواست آزمایشی برای نقش اختصاصی ساختمان',
            'category' => 'general',
            'target_role_id' => $role->id,
            'priority' => 'medium',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $ticket = \App\Models\Ticket::where('kind', 'request')->where('created_by', $resident->id)->firstOrFail();
        $this->assertSame($role->id, (int) $ticket->target_role_id);
        $this->assertDatabaseHas('ticket_assignees', [
            'building_id' => $this->building->id,
            'ticket_id' => $ticket->id,
            'user_id' => $staff->id,
        ]);
    }

    public function test_roles_cannot_be_managed_across_buildings_or_by_staff(): void
    {
        $role = Role::create([
            'building_id' => $this->building->id,
            'key' => 'custom_isolation_test',
            'name_fa' => 'نقش اختصاصی',
            'is_staff' => true,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->as($this->owner, $this->otherBuilding)->put(route('staff-roles.update', $role->id), [
            'name_fa' => 'دستکاری',
            'permissions' => [],
        ])->assertNotFound();
    }
}
