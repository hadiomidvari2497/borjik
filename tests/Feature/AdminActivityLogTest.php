<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_admin_write_action_is_recorded_and_visible_to_authorized_admin(): void
    {
        $admin = AdminUser::create(['username' => 'activity-log-admin', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => true]);

        $this->actingAs($admin, 'admin')->post(route('admin.logout'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $admin->id,
            'method' => 'POST',
            'route_name' => 'admin.logout',
        ]);

        $this->actingAs($admin, 'admin')->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('تاریخچه فعالیت مدیران')
            ->assertSee('عملیات امروز')
            ->assertSee('admin.logout');
    }


    public function test_authorized_admin_can_clear_activity_logs_for_a_date_range(): void
    {
        $admin = AdminUser::create(['username' => 'activity-clear-admin', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => true]);
        AdminActivityLog::query()->create(['admin_user_id' => $admin->id, 'method' => 'POST', 'action' => 'old action', 'path' => '/old', 'created_at' => now()->subDays(5)]);
        AdminActivityLog::query()->create(['admin_user_id' => $admin->id, 'method' => 'POST', 'action' => 'new action', 'path' => '/new', 'created_at' => now()]);

        $this->actingAs($admin, 'admin')->delete(route('admin.activity-logs.clear'), [
            'scope' => 'range',
            'from' => now()->subDays(6)->toDateString(),
            'to' => now()->subDays(4)->toDateString(),
            'confirmation' => 'حذف',
        ])->assertRedirect(route('admin.activity-logs.index'));

        $this->assertDatabaseMissing('admin_activity_logs', ['action' => 'old action']);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'new action']);
    }

    public function test_activity_log_page_requires_logs_view_permission(): void
    {
        $admin = AdminUser::create(['username' => 'activity-log-limited', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false]);
        $role = AdminRole::create(['name' => 'activity-log-limited-role']);
        $role->syncPermissions(['users.view']);
        $admin->roles()->attach($role->id);

        $this->actingAs($admin, 'admin')->get(route('admin.activity-logs.index'))->assertForbidden();
    }
}
