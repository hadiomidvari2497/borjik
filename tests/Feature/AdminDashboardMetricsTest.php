<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Building;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Support\Fa;
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
    public function test_expired_subscription_alert_counts_each_building_independently(): void
    {
        $admin = AdminUser::create([
            'username' => 'dashboard-building-expiry',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);
        $owner = User::create([
            'mobile' => '09120000772',
            'first_name' => 'مالک',
            'last_name' => 'چندساختمان',
            'status' => 'active',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'پلن تست',
            'units_limit' => 10,
            'duration_months' => 1,
            'price' => 0,
            'is_active' => true,
        ]);

        $expiredBuilding = Building::create([
            'name' => 'ساختمان منقضی',
            'code' => 'EXP-772',
            'created_by' => $owner->id,
        ]);
        $expired = Subscription::create([
            'user_id' => $owner->id,
            'building_id' => $expiredBuilding->id,
            'plan_id' => $plan->id,
            'units_limit' => 10,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
            'status' => 'expired',
        ]);
        $expiredBuilding->update(['subscription_id' => $expired->id]);

        $activeBuilding = Building::create([
            'name' => 'ساختمان فعال',
            'code' => 'ACT-772',
            'created_by' => $owner->id,
        ]);
        $active = Subscription::create([
            'user_id' => $owner->id,
            'building_id' => $activeBuilding->id,
            'plan_id' => $plan->id,
            'units_limit' => 10,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        $activeBuilding->update(['subscription_id' => $active->id]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('اشتراک‌های منقضی یا غیرفعال')
            ->assertSee(Fa::num(1));
    }

}
