<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBuildingUserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = []): AdminUser
    {
        static $n = 0;
        $n++;
        $admin = AdminUser::create(['username' => 'buildingusers'.$n, 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false]);
        if ($permissions !== []) {
            $role = AdminRole::create(['name' => 'buildingusers-role'.$n]);
            $role->syncPermissions($permissions);
            $admin->roles()->attach($role->id);
        }
        return $admin;
    }

    public function test_admin_with_users_view_permission_can_search_building_users(): void
    {
        $admin = $this->admin(['users.view']);
        $user = User::create(['mobile' => '09120000111', 'first_name' => 'آزمایش', 'last_name' => 'کاربر']);
        $building = Building::create(['name' => 'ساختمان آزمایشی', 'code' => 'A1234', 'created_by' => $user->id]);
        $role = Role::create(['key' => 'manager', 'name_fa' => 'مدیر ساختمان']);
        BuildingUserRole::create(['building_id' => $building->id, 'user_id' => $user->id, 'role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($admin, 'admin')->get(route('admin.building-users.index', ['q' => '09120000111', 'building_id' => $building->id]))
            ->assertOk()->assertSee('مدیریت کاربران پنل ساختمان‌ها')->assertSee('ساختمان آزمایشی')->assertSee($role->name_fa);
    }

    public function test_admin_without_users_view_permission_cannot_access_building_user_directory(): void
    {
        $admin = $this->admin(['plans.manage']);
        $this->actingAs($admin, 'admin')->get(route('admin.building-users.index'))->assertForbidden();
    }
}
