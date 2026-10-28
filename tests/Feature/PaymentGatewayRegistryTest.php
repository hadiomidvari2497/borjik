<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\Payments\PaymentGatewayRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PaymentGatewayRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_exposes_multiple_independently_active_gateways(): void
    {
        SiteSetting::set('payment.gateways', [
            'bitpay-main' => [
                'provider_name' => 'بیت‌پی اصلی',
                'driver' => 'bitpay',
                'api_key' => Crypt::encryptString('bitpay-secret'),
                'active' => true,
            ],
            'zarinpal-backup' => [
                'provider_name' => 'زرین‌پال پشتیبان',
                'driver' => 'zarinpal',
                'merchant_id' => 'merchant-test',
                'active' => true,
            ],
            'disabled' => [
                'provider_name' => 'غیرفعال',
                'driver' => 'bitpay',
                'api_key' => Crypt::encryptString('inactive-secret'),
                'active' => false,
            ],
            'incomplete' => [
                'provider_name' => 'ناقص',
                'driver' => 'zarinpal',
                'merchant_id' => '',
                'active' => true,
            ],
        ], true);

        $registry = app(PaymentGatewayRegistry::class);

        $this->assertSame(['bitpay-main', 'zarinpal-backup'], array_keys($registry->active()));
        $this->assertSame('بیت‌پی اصلی', $registry->find('bitpay-main')['provider_name']);
        $this->assertSame('زریـن‌پال پشتیبان', $registry->active()['zarinpal-backup']['provider_name'] ?? 'زریـن‌پال پشتیبان');
    }

    public function test_registry_falls_back_to_existing_single_gateway_configuration(): void
    {
        SiteSetting::set('payment.gateway', [
            'provider_name' => 'بیت‌پی قدیمی',
            'driver' => 'bitpay',
            'api_key' => Crypt::encryptString('legacy-secret'),
            'active' => true,
        ], true);

        $registry = app(PaymentGatewayRegistry::class);

        $this->assertArrayHasKey('legacy', $registry->active());
        $this->assertSame('bitpay', $registry->active()['legacy']['driver']);
    }
}
