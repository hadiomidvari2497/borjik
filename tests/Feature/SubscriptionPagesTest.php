<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private SubscriptionPlan $pro;
    private SubscriptionPlan $big;
    private SubscriptionPayment $payment;
    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['mobile' => '09120000077', 'first_name' => 'مریم', 'last_name' => 'احمدی', 'status' => 'active', 'profile_completed_at' => now()]);
        $this->seed(RolePermissionSeeder::class);
        $this->building = Building::create(['name' => 'ساختمان صفحه اشتراک', 'code' => '20077', 'created_by' => $this->user->id]);
        BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $this->user->id, 'role_id' => Role::where('key', 'owner')->whereNull('building_id')->value('id'), 'status' => 'active']);

        $this->pro = SubscriptionPlan::create(['name' => 'حرفه‌ای', 'units_limit' => 50, 'duration_months' => 3, 'price' => 1250000, 'is_active' => true]);
        $this->big = SubscriptionPlan::create([
            'name' => 'سازمانی', 'units_limit' => 200, 'duration_months' => 12, 'price' => 9800000, 'is_active' => true,
            'discount_type' => 'percent', 'discount_value' => 20,
            'discount_starts_at' => now()->subDay(), 'discount_ends_at' => now()->addDays(5),
        ]);
        $subscription = Subscription::create(['user_id' => $this->user->id, 'building_id' => $this->building->id, 'plan_id' => $this->pro->id, 'units_limit' => 50, 'starts_at' => now()->subDays(10), 'expires_at' => now()->addDays(80), 'status' => 'active']);
        $this->building->update(['subscription_id' => $subscription->id]);
        $this->payment = SubscriptionPayment::create(['subscription_id' => $subscription->id, 'user_id' => $this->user->id, 'amount' => 1250000, 'gateway' => 'zarinpal', 'ref_id' => '555111', 'status' => 'paid', 'paid_at' => now()->subDays(10)]);
        SiteSetting::set('payment.gateway', ['provider_name' => 'زرین‌پال', 'driver' => 'zarinpal', 'environment' => 'sandbox', 'api_base_url' => 'https://sandbox.zarinpal.com/pg/v4', 'merchant_id' => '00000000-0000-0000-0000-000000000000', 'active' => true]);
    }

    private function asBuilding()
    {
        return $this->actingAs($this->user)->withSession(['current_building_id' => $this->building->id]);
    }

    public function test_plans_page_highlights_current_plan_discount_and_history(): void
    {
        $this->asBuilding()->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('پلن فعلی شما')
            ->assertSee('تخفیف ویژه')
            ->assertSee('روز تا پایان اعتبار')
            ->assertSee('مشاهده فاکتور')
            ->assertSee(route('subscription.payment.receipt', $this->payment->id), false)
            ->assertDontSee('تومان تومان');
    }

    public function test_checkout_shows_steps_savings_and_a_single_currency_label(): void
    {
        $this->asBuilding()->get(route('subscription.show', ['checkout' => $this->big->id]))
            ->assertOk()
            ->assertSee('تکمیل خرید')
            ->assertSee('تخفیف پلن')
            ->assertSee('ادامه و پرداخت امن')
            ->assertSee('name="gateway_id"', false)
            ->assertDontSee('تومان تومان');
    }

    public function test_invoice_shows_number_amount_and_print_action(): void
    {
        $this->asBuilding()->get(route('subscription.payment.receipt', $this->payment->id))
            ->assertOk()
            ->assertSee('SH-'.now()->subDays(10)->format('Y').'-000001', false)
            ->assertSee('پرداخت موفق')
            ->assertSee('555111')
            ->assertSee('چاپ فاکتور')
            ->assertDontSee('تومان تومان');
    }
}
