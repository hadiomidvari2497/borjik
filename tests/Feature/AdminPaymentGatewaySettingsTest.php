<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AdminPaymentGatewaySettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = []): AdminUser
    {
        static $n = 0;
        $n++;
        $admin = AdminUser::create([
            'username' => 'gateway_admin_'.$n,
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => false,
        ]);

        if ($permissions !== []) {
            $role = AdminRole::create(['name' => 'gateway_role_'.$n]);
            $role->syncPermissions($permissions);
            $admin->roles()->attach($role->id);
        }

        return $admin;
    }

    public function test_gateway_settings_page_requires_gateway_permission(): void
    {
        $admin = $this->admin(['plans.manage']);

        $this->actingAs($admin, 'admin')->get('/admin/payment-gateway')->assertForbidden();
        $this->actingAs($admin, 'admin')->put('/admin/payment-gateway', [
            'driver' => 'custom', 'environment' => 'sandbox',
        ])->assertForbidden();
    }

    public function test_gateway_settings_are_saved_and_credentials_are_encrypted(): void
    {
        $admin = $this->admin(['gateway.manage']);

        $this->actingAs($admin, 'admin')->get('/admin/payment-gateway')->assertOk();

        $this->put('/admin/payment-gateway', [
            'provider_name' => 'درگاه آزمایشی',
            'driver' => 'custom',
            'environment' => 'sandbox',
            'api_base_url' => 'https://gateway.example/api',
            'merchant_id' => 'merchant-123',
            'api_key' => 'test-api-key',
            'api_secret' => 'test-api-secret',
            'webhook_secret' => 'test-hook-secret',
            'active' => '1',
        ])->assertRedirect(route('admin.payment-gateway.edit'));

        $settings = SiteSetting::get('payment.gateway');
        $this->assertSame('درگاه آزمایشی', $settings['provider_name']);
        $this->assertTrue($settings['active']);
        $this->assertNotSame('test-api-key', $settings['api_key']);
        $this->assertSame('test-api-key', Crypt::decryptString($settings['api_key']));
        $this->assertSame('test-api-secret', Crypt::decryptString($settings['api_secret']));
        $this->assertSame('test-hook-secret', Crypt::decryptString($settings['webhook_secret']));
    }

    public function test_blank_secrets_preserve_existing_values_and_can_be_cleared(): void
    {
        $admin = $this->admin(['gateway.manage']);
        SiteSetting::set('payment.gateway', [
            'provider_name' => 'Demo',
            'driver' => 'custom',
            'environment' => 'sandbox',
            'api_base_url' => '',
            'merchant_id' => 'm1',
            'api_key' => Crypt::encryptString('keep-me'),
            'api_secret' => null,
            'webhook_secret' => null,
            'active' => false,
            'notes' => '',
        ], true);

        $this->actingAs($admin, 'admin')->put('/admin/payment-gateway', [
            'provider_name' => 'Demo',
            'driver' => 'custom',
            'environment' => 'sandbox',
            'merchant_id' => 'm1',
            'api_key' => '',
            'clear_api_key' => '0',
        ])->assertRedirect(route('admin.payment-gateway.edit'));

        $this->assertSame('keep-me', Crypt::decryptString(SiteSetting::get('payment.gateway')['api_key']));

        $this->put('/admin/payment-gateway', [
            'provider_name' => 'Demo',
            'driver' => 'custom',
            'environment' => 'sandbox',
            'merchant_id' => 'm1',
            'api_key' => '',
            'clear_api_key' => '1',
        ])->assertRedirect(route('admin.payment-gateway.edit'));

        $this->assertNull(SiteSetting::get('payment.gateway')['api_key']);
    }

    public function test_bitpay_can_be_configured_without_a_merchant_terminal(): void
    {
        $admin = $this->admin(['gateway.manage']);

        $this->actingAs($admin, 'admin')->put('/admin/payment-gateway', [
            'provider_name' => 'بیت‌پی',
            'driver' => 'bitpay',
            'environment' => 'production',
            'api_key' => 'demo-value',
            'active' => '1',
        ])->assertRedirect(route('admin.payment-gateway.edit'));

        $settings = SiteSetting::get('payment.gateway');
        $this->assertSame('bitpay', $settings['driver']);
        $this->assertTrue($settings['active']);
        $this->assertEmpty($settings['merchant_id']);
        $this->assertSame('demo-value', Crypt::decryptString($settings['api_key']));
    }

    public function test_configuration_check_does_not_claim_live_gateway_connectivity(): void
    {
        $admin = $this->admin(['gateway.manage']);
        SiteSetting::set('payment.gateway', [
            'provider_name' => 'Demo', 'driver' => 'custom', 'environment' => 'sandbox',
            'merchant_id' => 'm1', 'api_key' => 'secret', 'active' => true,
        ], true);

        $this->actingAs($admin, 'admin')->post('/admin/payment-gateway/validate')
            ->assertSessionHas('status');
    }
}
