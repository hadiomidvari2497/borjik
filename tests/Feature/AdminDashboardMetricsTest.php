<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_active_users_revenue_and_feature_usage_metrics(): void
    {
        $admin = AdminUser::create([
            'username' => 'dashboard-metrics-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);
        User::create([
            'mobile' => '09120000771',
            'first_name' => 'فعال',
            'last_name' => 'آزمایشی',
            'status' => 'active',
            'last_login_at' => now()->subDays(3),
        ]);
        SubscriptionPayment::create([
            'user_id' => User::query()->firstOrFail()->id,
            'amount' => 1234567,
            'gateway' => 'test',
            'status' => 'paid',
            'paid_at' => now()->subDays(2),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('کاربران فعال در ۳۰ روز')
            ->assertSee('درآمد ماه جاری')
            ->assertSee('درآمد سال جاری')
            ->assertSee('میزان استفاده از قابلیت‌ها')
            ->assertSee('درخواست‌های پشتیبانی');
    }
}
