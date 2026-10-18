<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBuildingDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_buildings_view_permission_can_view_building_directory(): void
    {
        $admin = AdminUser::create(['username' => 'building-directory-admin', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false]);
        $role = AdminRole::create(['name' => 'building-directory-role']);
        $role->syncPermissions(['buildings.view']);
        $admin->roles()->attach($role->id);

        $owner = User::create(['mobile' => '09120000222', 'first_name' => 'سازنده', 'last_name' => 'آزمایشی']);
        $building = Building::create(['name' => 'برج نمونه', 'code' => 'B1234', 'created_by' => $owner->id, 'status' => 'active']);

        $this->actingAs($admin, 'admin')->get(route('admin.buildings.index', ['q' => 'برج نمونه']))
            ->assertOk()->assertSee('مدیریت ساختمان‌های سامانه')->assertSee('برج نمونه')->assertSee('سازنده');
    }

    public function test_admin_without_buildings_view_permission_cannot_view_building_directory(): void
    {
        $admin = AdminUser::create(['username' => 'building-directory-denied', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false]);
        $role = AdminRole::create(['name' => 'building-directory-denied-role']);
        $role->syncPermissions(['users.view']);
        $admin->roles()->attach($role->id);

        $this->actingAs($admin, 'admin')->get(route('admin.buildings.index'))->assertForbidden();
    }
}
