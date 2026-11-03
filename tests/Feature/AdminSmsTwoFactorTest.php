<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Services\Sms\SmsGateway;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmsTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private object $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new class implements SmsGateway {
            public array $sent = [];

            public function send(string $mobile, string $text, ?string $templateKey = null, array $templateVars = []): string
            {
                $this->sent[] = [$mobile, $text];

                return 'fake';
            }
        };
        $this->app->instance(SmsGateway::class, $this->gateway);
    }

    private function code(): string
    {
        preg_match('/\d{6}/', end($this->gateway->sent)[1], $matches);

        return $matches[0];
    }

    private function admin(array $attributes = []): AdminUser
    {
        static $number = 0;
        $number++;

        return AdminUser::create($attributes + [
            'username' => 'sms-admin-'.$number,
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);
    }

    public function test_admin_can_verify_mobile_from_account_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.account'))
            ->assertOk()
            ->assertSee('شماره موبایل و ورود پیامکی');

        $this->post(route('admin.account.mobile'), ['mobile' => '۰۹۱۲۳۴۵۶۷۸۹'])->assertRedirect();
        $this->post(route('admin.account.mobile.request'))->assertRedirect();
        $this->assertSame('09123456789', $this->gateway->sent[0][0]);

        $this->post(route('admin.account.mobile.verify'), ['code' => $this->code()])->assertRedirect();
        $this->assertNotNull($admin->fresh()->mobile_verified_at);
    }

    public function test_admin_can_complete_two_factor_login_with_sms_fallback(): void
    {
        $admin = $this->admin([
            'two_factor_enabled' => true,
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_sms_enabled' => true,
            'mobile' => '09123456789',
            'mobile_verified_at' => now(),
        ]);

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'secret-pass-1',
        ])->assertRedirect(route('admin.two-factor.challenge'));

        $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee('ارسال کد پیامکی');
        $this->post(route('admin.two-factor.sms.request'))->assertRedirect();
        $this->assertSame('09123456789', $this->gateway->sent[0][0]);

        $this->post(route('admin.two-factor.sms.verify'), ['code' => $this->code()])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_unverified_admin_mobile_cannot_enable_sms_fallback(): void
    {
        $admin = $this->admin([
            'two_factor_enabled' => true,
            'two_factor_secret' => Totp::generateSecret(),
            'mobile' => '09123456789',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.account.sms-two-factor'), ['enabled' => 1])
            ->assertSessionHasErrors('sms_two_factor');

        $this->assertFalse((bool) $admin->fresh()->two_factor_sms_enabled);
    }
}
