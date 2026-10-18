<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\SystemErrorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_record_with_actor_is_visible_in_admin_diagnostics(): void
    {
        $user = \App\Models\User::factory()->create();
        $admin = AdminUser::create(['username' => 'diagnostics-admin', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => true]);

        SystemErrorLog::query()->create([
            'exception_class' => \RuntimeException::class,
            'message' => 'Diagnostic test failure',
            'file' => base_path('app/Example.php'),
            'line' => 42,
            'method' => 'GET',
            'path' => '/example',
            'route_name' => 'example.test',
            'status_code' => 500,
            'actor_type' => 'user',
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.system-diagnostics.index'))
            ->assertOk()
            ->assertSee('مرکز خطا و عیب‌یابی')
            ->assertSee('Diagnostic test failure')
            ->assertSee('سلامت اجزای سامانه')
            ->assertSee('example.test');
    }

    public function test_diagnostics_page_requires_logs_view_permission(): void
    {
        $admin = AdminUser::create(['username' => 'diagnostics-limited', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false]);
        $role = \App\Models\AdminRole::create(['name' => 'diagnostics-limited-role']);
        $role->syncPermissions(['users.view']);
        $admin->roles()->attach($role->id);

        $this->actingAs($admin, 'admin')->get(route('admin.system-diagnostics.index'))->assertForbidden();
    }
}
