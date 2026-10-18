<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_operational_health_summary(): void
    {
        $admin = AdminUser::create([
            'username' => 'dashboard-health-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('داشبورد مدیریت')
            ->assertSee('نیازمند رسیدگی')
            ->assertSee('مورد فوری برای رسیدگی پیدا نشد')
            ->assertSee('دسترسی سریع');
    }
}
