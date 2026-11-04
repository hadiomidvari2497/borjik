<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\PropertyListingCreditTransaction;
use App\Models\PropertyListingPackageOrder;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PropertyListingPackageCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'mobile' => '09120000123',
            'first_name' => 'کاربر',
            'last_name' => 'بازارچه',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);

        SiteSetting::set('property_listings.free_limit', 3);
        SiteSetting::set('property_listings.packages', [
            ['quantity' => 1, 'price' => 50000, 'active' => true],
            ['quantity' => 3, 'price' => 120000, 'active' => true],
            ['quantity' => 10, 'price' => 350000, 'active' => true],
        ]);
        SiteSetting::set('payment.gateway', [
            'provider_name' => 'زرین‌پال آزمایشی',
            'driver' => 'zarinpal',
            'environment' => 'sandbox',
            'api_base_url' => 'https://sandbox.zarinpal.com/pg/v4',
            'merchant_id' => '00000000-0000-0000-0000-000000000000',
            'active' => true,
        ]);
    }

    private function fakeSuccessfulZarinpal(): void
    {
        Http::fake([
            '*/payment/request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'A-PACKAGE-ORDER-123'],
            ], 200),
            '*/payment/verify.json' => Http::response([
                'data' => ['code' => 100, 'ref_id' => 778899],
            ], 200),
        ]);
    }

    private function startPurchase(array $extra = []): PropertyListingPackageOrder
    {
        $this->actingAs($this->user)
            ->post(route('properties.packages.buy'), array_merge([
                'quantity' => 1,
                'gateway_id' => 'legacy',
            ], $extra))
            ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A-PACKAGE-ORDER-123');

        return PropertyListingPackageOrder::query()->where('user_id', $this->user->id)->firstOrFail();
    }

    private function successfulCallback(PropertyListingPackageOrder $order): void
    {
        $this->get(route('properties.packages.callback', [
            'gateway_id' => $order->gateway_id,
            'Authority' => $order->authority,
            'Status' => 'OK',
        ]))->assertRedirect(route('properties.mine'));
    }

    public function test_package_credits_are_granted_only_after_server_side_gateway_verification_and_only_once(): void
    {
        $this->fakeSuccessfulZarinpal();
        $order = $this->startPurchase();

        $this->assertSame('pending', $order->status);
        $this->assertSame(0, app(\App\Services\PropertyListingQuota::class)->availableCredits($this->user->id));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/payment/request.json')
            && $request['amount'] === 500000);

        $this->successfulCallback($order);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('778899', (string) $order->fresh()->ref_id);
        $this->assertSame(1, app(\App\Services\PropertyListingQuota::class)->availableCredits($this->user->id));
        $this->assertDatabaseHas('property_listing_credit_transactions', [
            'user_id' => $this->user->id,
            'package_order_id' => $order->id,
            'quantity' => 1,
            'type' => 'purchase',
        ]);

        $this->get(route('properties.packages.callback', [
            'gateway_id' => $order->gateway_id,
            'Authority' => $order->authority,
            'Status' => 'OK',
        ]));

        $this->assertSame(1, app(\App\Services\PropertyListingQuota::class)->availableCredits($this->user->id));
        $this->assertSame(1, PropertyListingCreditTransaction::where('package_order_id', $order->id)->count());
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/payment/verify.json')
            && $request['amount'] === 500000
            && $request['authority'] === 'A-PACKAGE-ORDER-123');
    }

    public function test_cancelled_or_unverified_package_payment_never_grants_credits(): void
    {
        Http::fake([
            '*/payment/request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'A-PACKAGE-CANCELLED'],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->post(route('properties.packages.buy'), ['quantity' => 1, 'gateway_id' => 'legacy'])
            ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A-PACKAGE-CANCELLED');

        $order = PropertyListingPackageOrder::firstOrFail();

        $this->get(route('properties.packages.callback', [
            'gateway_id' => 'legacy',
            'Authority' => $order->authority,
            'Status' => 'NOK',
        ]))->assertRedirect(route('properties.mine'));

        $this->assertSame('failed', $order->fresh()->status);
        $this->assertSame(0, app(\App\Services\PropertyListingQuota::class)->availableCredits($this->user->id));
        $this->assertDatabaseCount('property_listing_credit_transactions', 0);
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/payment/verify.json'));
    }

    public function test_a_verified_package_credit_allows_one_additional_listing_and_is_consumed_atomically(): void
    {
        $this->fakeSuccessfulZarinpal();
        $order = $this->startPurchase();

        $this->successfulCallback($order);

        foreach (range(1, 3) as $number) {
            PropertyListing::create([
                'user_id' => $this->user->id,
                'listing_type' => 'sale',
                'property_type' => 'residential',
                'title' => 'آگهی رایگان '.$number,
                'description' => 'توضیحات آگهی',
                'city' => 'تهران',
                'status' => 'active',
            ]);
        }

        $this->actingAs($this->user)->post(route('properties.store'), [
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی چهارم با اعتبار خریداری‌شده',
            'description' => 'توضیحات آگهی',
            'city' => 'تهران',
            'price' => 500000000,
        ])->assertRedirect(route('properties.mine'));

        $this->assertDatabaseCount('property_listings', 4);
        $this->assertSame(0, app(\App\Services\PropertyListingQuota::class)->availableCredits($this->user->id));
        $publishedListing = PropertyListing::where('title', 'آگهی چهارم با اعتبار خریداری‌شده')->firstOrFail();
        $this->assertDatabaseHas('property_listing_credit_transactions', [
            'user_id' => $this->user->id,
            'package_order_id' => null,
            'property_listing_id' => $publishedListing->id,
            'quantity' => -1,
            'type' => 'consume',
        ]);
    }

    public function test_package_quantity_and_price_are_taken_from_admin_settings_not_from_the_request(): void
    {
        $this->fakeSuccessfulZarinpal();

        $order = $this->startPurchase(['amount' => 1, 'price' => 1]);

        $this->assertSame(50000, (int) $order->amount);
        $this->assertSame(1, (int) $order->quantity);
    }
}
