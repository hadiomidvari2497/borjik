<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\SystemErrorLog;
use App\Support\SystemErrorReporter;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_record_with_actor_is_visible_in_admin_diagnostics(): void
    {
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
            'actor_type' => 'admin',
            'admin_user_id' => $admin->id,
            'created_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.system-diagnostics.index'))
            ->assertOk()
            ->assertSee('مرکز خطا و عیب‌یابی')
            ->assertSee('Diagnostic test failure')
            ->assertSee('سلامت اجزای سامانه')
            ->assertSee('example.test')
            ->assertSee('diagnostics-admin');
    }


    public function test_authorized_admin_can_clear_all_system_error_logs(): void
    {
        $admin = AdminUser::create(['username' => 'diagnostics-clear-admin', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => true]);
        SystemErrorLog::query()->create([
            'exception_class' => RuntimeException::class,
            'message' => 'clear-me',
            'status_code' => 500,
            'created_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')->delete(route('admin.system-diagnostics.clear'), [
            'scope' => 'all',
            'confirmation' => 'حذف',
        ])->assertRedirect(route('admin.system-diagnostics.index'));

        $this->assertDatabaseMissing('system_error_logs', ['message' => 'clear-me']);
    }

    public function test_handled_application_warning_is_recorded_with_details_and_copyable_data(): void
    {
        $request = Request::create('/subscription/plan/1', 'POST');
        $request->setLaravelSession(app('session')->driver());

        SystemErrorReporter::warning(
            $request,
            'payment.gateway',
            'Payment gateway is not configured.',
            'Purchase was stopped before creating a transaction.',
            ['plan_id' => 1, 'gateway_active' => false],
            422,
        );

        $log = SystemErrorLog::query()->latest('id')->firstOrFail();

        $this->assertSame('warning', $log->severity);
        $this->assertSame('payment.gateway', $log->category);
        $this->assertSame(422, $log->status_code);
        $this->assertStringContainsString('Purchase was stopped', $log->details);
        $this->assertSame(['plan_id' => 1, 'gateway_active' => false], $log->context);
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
