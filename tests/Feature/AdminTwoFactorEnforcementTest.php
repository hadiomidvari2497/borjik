<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTwoFactorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_super_admin_must_enable_two_factor_before_using_admin_panel(): void
    {
        config(['app.env' => 'production']);
        $admin = AdminUser::create([
            'username' => 'must-enable-2fa',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
            'two_factor_enabled' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.account.two-factor'));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.account.two-factor'))
            ->assertOk()
            ->assertSee('کلید محرمانه');
    }

    public function test_super_admin_with_two_factor_can_use_admin_panel_and_regular_admin_is_not_forced(): void
    {
        config(['app.env' => 'production']);
        $protected = AdminUser::create([
            'username' => 'protected-super',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
            'two_factor_enabled' => true,
            'two_factor_secret' => Totp::generateSecret(),
        ]);
        $this->actingAs($protected, 'admin')->get(route('admin.dashboard'))->assertOk();

        $regular = AdminUser::create([
            'username' => 'regular-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => false,
            'two_factor_enabled' => false,
        ]);
        $this->actingAs($regular, 'admin')->get(route('admin.dashboard'))->assertOk();
    }
}
