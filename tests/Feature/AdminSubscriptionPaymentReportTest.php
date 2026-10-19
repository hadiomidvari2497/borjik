<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionPaymentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_payment_metrics_and_subscription_history(): void
    {
        $admin = AdminUser::create([
            'username' => 'payment-report-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);

        $user = User::create([
            'first_name' => 'آزمایش',
            'last_name' => 'کاربر',
            'mobile' => '09120000001',
            'status' => 'active',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'پلن آزمایشی',
            'units_limit' => 20,
            'duration_months' => 12,
            'price' => 250000,
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'units_limit' => $plan->units_limit,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(12),
            'status' => 'active',
        ]);
        SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'amount' => 250000,
            'gateway' => 'zarinpal',
            'ref_id' => 'REF-REPORT-123',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.subscription-payments.index'))
            ->assertOk()
            ->assertSee('گزارش پرداخت و تاریخچه اشتراک‌ها')
            ->assertSee('۲۵۰٬۰۰۰ تومان')
            ->assertSee('REF-REPORT-123')
            ->assertSee('پلن آزمایشی')
            ->assertSee('تاریخچه فعال‌سازی پلن‌ها');
    }

    public function test_payment_report_can_filter_by_status_and_export_csv(): void
    {
        $admin = AdminUser::create([
            'username' => 'payment-report-export-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);
        $user = User::create(['first_name' => 'گزارش', 'last_name' => 'تست', 'mobile' => '09120000002', 'status' => 'active']);
        $plan = SubscriptionPlan::create(['name' => 'پلن CSV', 'units_limit' => 5, 'duration_months' => 1, 'price' => 10000, 'is_active' => true]);
        $subscription = Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'units_limit' => 5, 'starts_at' => now(), 'expires_at' => now()->addMonth(), 'status' => 'active']);
        SubscriptionPayment::create(['subscription_id' => $subscription->id, 'user_id' => $user->id, 'amount' => 10000, 'gateway' => 'zarinpal', 'ref_id' => 'REF-CSV-456', 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.subscription-payments.index', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('برای این بازه تراکنشی ثبت نشده است.');

        $this->get(route('admin.subscription-payments.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('REF-CSV-456');
    }
}
