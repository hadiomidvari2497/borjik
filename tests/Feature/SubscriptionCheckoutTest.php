<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private SubscriptionPlan $paidPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'mobile' => '09120000099',
            'first_name' => 'کاربر',
            'last_name' => 'پرداخت',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);

        $this->paidPlan = SubscriptionPlan::create([
            'name' => 'حرفه‌ای',
            'units_limit' => 50,
            'duration_months' => 3,
            'price' => 125000,
            'is_active' => true,
        ]);
    }

    private function configureZarinpal(): void
    {
        SiteSetting::set('payment.gateway', [
            'provider_name' => 'زرین‌پال',
            'driver' => 'zarinpal',
            'environment' => 'sandbox',
            'api_base_url' => 'https://sandbox.zarinpal.com/pg/v4',
            'merchant_id' => '00000000-0000-0000-0000-000000000000',
            'active' => true,
        ]);
    }

    public function test_paid_plan_stays_pending_until_zarinpal_verifies_payment(): void
    {
        $this->configureZarinpal();
        Http::fake([
            '*/payment/request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'A00000000000000000000000000000000001'],
            ], 200),
            '*/payment/verify.json' => Http::response([
                'data' => ['code' => 100, 'ref_id' => 987654321],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->post(route('subscription.subscribe', $this->paidPlan->id))
            ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A00000000000000000000000000000000001');

        $subscription = Subscription::where('user_id', $this->user->id)->firstOrFail();
        $payment = SubscriptionPayment::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame('pending', $subscription->status);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(125000, (int) $payment->amount);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/payment/request.json')
            && $request['amount'] === 1250000);

        $this->get(route('payment.gateway.callback', [
            'Authority' => 'A00000000000000000000000000000000001',
            'Status' => 'OK',
        ]))->assertRedirect(route('subscription.show'))->assertSessionHas('status');

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('987654321', (string) $payment->fresh()->ref_id);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/payment/verify.json')
            && $request['amount'] === 1250000
            && $request['authority'] === 'A00000000000000000000000000000000001');
    }

    public function test_cancelled_payment_does_not_activate_subscription(): void
    {
        $this->configureZarinpal();
        Http::fake([
            '*/payment/request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'A-CANCELLED'],
            ], 200),
        ]);

        $this->actingAs($this->user)->post(route('subscription.subscribe', $this->paidPlan->id))->assertRedirect();
        $subscription = Subscription::where('user_id', $this->user->id)->firstOrFail();

        $this->get(route('payment.gateway.callback', ['Authority' => 'A-CANCELLED', 'Status' => 'NOK']))
            ->assertRedirect(route('subscription.show'))->assertSessionHas('error');

        $this->assertSame('failed', $subscription->fresh()->status);
        $this->assertDatabaseHas('subscription_payments', ['user_id' => $this->user->id, 'status' => 'failed']);
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/payment/verify.json'));
    }

    public function test_paid_plan_cannot_activate_when_gateway_is_not_configured(): void
    {
        $this->actingAs($this->user)
            ->from(route('subscription.show'))
            ->post(route('subscription.subscribe', $this->paidPlan->id))
            ->assertRedirect(route('subscription.show'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('subscriptions', ['user_id' => $this->user->id, 'status' => 'active']);
        $this->assertDatabaseMissing('subscription_payments', ['user_id' => $this->user->id]);
    }

    public function test_free_plan_can_be_activated_without_a_payment_gateway(): void
    {
        $free = SubscriptionPlan::create([
            'name' => 'رایگان',
            'units_limit' => 3,
            'duration_months' => 1,
            'price' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('subscription.subscribe', $free->id))
            ->assertRedirect(route('buildings.create'));

        $this->assertDatabaseHas('subscriptions', ['user_id' => $this->user->id, 'plan_id' => $free->id, 'status' => 'active']);
    }
}
