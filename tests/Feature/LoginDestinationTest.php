<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginDestinationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->user = User::create([
            'mobile' => '09125550123', 'first_name' => 'کاربر', 'last_name' => 'آزمایشی',
            'status' => 'active', 'password' => 'SecretPass123', 'profile_completed_at' => now(),
        ]);
        $this->building = Building::create([
            'name' => 'برج مقصد', 'code' => '87654', 'created_by' => $this->user->id,
        ]);
    }

    private function membership(string $roleKey): BuildingUserRole
    {
        return BuildingUserRole::create([
            'building_id' => $this->building->id, 'user_id' => $this->user->id,
            'role_id' => Role::where('key', $roleKey)->firstOrFail()->id, 'status' => 'active',
        ]);
    }

    public function test_user_can_save_a_fixed_login_destination(): void
    {
        $membership = $this->membership('owner');
        $this->actingAs($this->user)->post(route('profile.login-destination'), [
            'login_destination_mode' => 'fixed', 'preferred_membership_id' => $membership->id,
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id, 'login_destination_mode' => 'fixed',
            'preferred_building_user_role_id' => $membership->id,
        ]);
    }

    public function test_fixed_destination_is_restored_after_password_login(): void
    {
        $this->membership('owner');
        $tenantMembership = $this->membership('tenant');
        $this->user->forceFill([
            'login_destination_mode' => 'fixed',
            'preferred_building_user_role_id' => $tenantMembership->id,
        ])->save();

        $this->post(route('login.password'), [
            'mobile' => $this->user->mobile, 'password' => 'SecretPass123',
        ])->assertRedirect(route('dashboard'))
          ->assertSessionHas('current_building_id', $this->building->id)
          ->assertSessionHas('current_building_user_role_id', $tenantMembership->id);
    }

    public function test_logout_remembers_the_last_valid_building_and_role(): void
    {
        $membership = $this->membership('owner');
        $this->actingAs($this->user)->withSession([
            'current_building_id' => $this->building->id,
            'current_building_user_role_id' => $membership->id,
        ])->post(route('logout'))->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id, 'last_building_user_role_id' => $membership->id,
        ]);
    }
}
