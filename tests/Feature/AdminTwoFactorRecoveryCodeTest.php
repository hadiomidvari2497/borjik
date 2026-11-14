<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTwoFactorRecoveryCodeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attributes = []): AdminUser
    {
        return AdminUser::create($attributes + [
            'username' => 'recovery-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
            'two_factor_enabled' => true,
            'two_factor_secret' => Totp::generateSecret(),
        ]);
    }

    public function test_admin_can_generate_recovery_codes_after_password_and_authenticator_verification(): void
    {
        $secret = Totp::generateSecret();
        $admin = $this->admin(['two_factor_secret' => $secret]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.account.recovery-codes'), [
                'password' => 'secret-pass-1',
                'authenticator_code' => Totp::code($secret),
            ])
            ->assertRedirect(route('admin.account'))
            ->assertSessionHas('recovery_codes');

        $this->assertCount(10, $admin->fresh()->two_factor_recovery_codes);
    }

    public function test_recovery_code_allows_admin_login_once_and_is_removed_after_use(): void
    {
        $admin = $this->admin([
            'two_factor_recovery_codes' => [Hash::make('A1B2C3D4E5F60718')],
        ]);

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'secret-pass-1',
        ])->assertRedirect(route('admin.two-factor.challenge'));

        $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee('data-delay="120"', false)->assertDontSee('id="recovery-tab"');

        $pending = session('admin_2fa');
        $pending['started_at'] = now()->subSeconds(121)->timestamp;
        session(['admin_2fa' => $pending]);

        $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee('استفاده از کد اضطراری');

        $this->post(route('admin.two-factor.recovery.verify'), ['code' => 'A1B2-C3D4-E5F6-0718'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertCount(0, $admin->fresh()->two_factor_recovery_codes);
    }


    public function test_recovery_code_cannot_be_used_before_two_minutes(): void
    {
        $admin = $this->admin([
            'two_factor_recovery_codes' => [Hash::make('A1B2C3D4E5F60718')],
        ]);

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'secret-pass-1',
        ])->assertRedirect(route('admin.two-factor.challenge'));

        $this->post(route('admin.two-factor.recovery.verify'), ['code' => 'A1B2-C3D4-E5F6-0718'])
            ->assertRedirect(route('admin.two-factor.challenge'))
            ->assertSessionHas('error');

        $this->assertGuest('admin');
        $this->assertCount(1, $admin->fresh()->two_factor_recovery_codes);
    }

    public function test_invalid_recovery_code_does_not_consume_a_valid_code(): void
    {
        $admin = $this->admin([
            'two_factor_recovery_codes' => [Hash::make('A1B2C3D4E5F60718')],
        ]);

        $this->post(route('admin.login.submit'), [
            'username' => $admin->username,
            'password' => 'secret-pass-1',
        ]);

        $this->post(route('admin.two-factor.recovery.verify'), ['code' => 'FFFFFFFFFFFFFFFF'])
            ->assertSessionHasErrors('recovery_code');

        $this->assertCount(1, $admin->fresh()->two_factor_recovery_codes);
        $this->assertGuest('admin');
    }
}
