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
            ->assertSee('دسترسی سریع')
            ->assertSee('روند رشد سامانه')
            ->assertSee('۷ روز')
            ->assertSee('۳۰ روز')
            ->assertSee('۹۰ روز');
    }
    public function test_dashboard_accepts_supported_trend_ranges(): void
    {
        $admin = AdminUser::create([
            'username' => 'dashboard-range-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);

        foreach ([7, 30, 90] as $days) {
            $this->actingAs($admin, 'admin')
                ->get(route('admin.dashboard', ['days' => $days]))
                ->assertOk()
                ->assertSee('روند رشد سامانه');
        }

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['days' => 14]))
            ->assertSessionHasErrors('days');
    }
}
