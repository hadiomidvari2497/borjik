<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Building;
use App\Models\User;
use App\Services\Auth\InvitationService;
use App\Services\Sms\SmsGateway;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use App\Models\SiteSetting;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** تنظیمات سایت (روش‌های ورود) و تأیید دومرحلهٔ ادمین. */
class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = [], array $perms = []): AdminUser
    {
        static $n = 0;
        $n++;
        $user = AdminUser::create($attrs + ['username' => "set{$n}", 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false]);
        if ($perms) {
            $role = AdminRole::create(['name' => "srole{$n}"]);
            $role->syncPermissions($perms);
            $user->roles()->attach($role->id);
        }

        return $user;
    }

    private function payload(array $over = []): array
    {
        return $over + ['otp_enabled' => 1, 'password_enabled' => 1, 'site_name' => 'برجیک', 'support_phone' => '', 'support_email' => ''];
    }

    // ---------- تنظیمات سایت
    public function test_settings_page_requires_site_settings_permission(): void
    {
        $plain = $this->admin([], ['plans.manage']);
        $this->actingAs($plain, 'admin')->get('/admin/settings')->assertForbidden();
        $this->actingAs($plain, 'admin')->put('/admin/settings', $this->payload())->assertForbidden();

        $ok = $this->admin([], ['site.settings']);
        $this->actingAs($ok, 'admin')->get('/admin/settings')->assertOk();
    }

    public function test_defaults_have_both_login_methods_enabled(): void
    {
        $this->assertTrue(SiteSetting::otpLoginEnabled());
        $this->assertTrue(SiteSetting::passwordLoginEnabled());
    }

    public function test_settings_are_saved_and_visible_immediately(): void
    {
        $a = $this->admin(['is_super' => true]);

        $this->actingAs($a, 'admin')->put('/admin/settings', $this->payload([
            'otp_enabled' => 0, 'site_name' => 'ساختمان من', 'support_phone' => '021-1234', 'support_email' => 'a@b.ir',
        ]))->assertRedirect(route('admin.settings.edit'));

        $this->assertFalse(SiteSetting::otpLoginEnabled());
        $this->assertTrue(SiteSetting::passwordLoginEnabled());
        $this->assertSame('ساختمان من', SiteSetting::get('site.name'));
        $this->assertSame('a@b.ir', SiteSetting::get('support.email'));
    }

    public function test_at_least_one_login_method_must_remain(): void
    {
        $a = $this->admin(['is_super' => true]);

        $this->actingAs($a, 'admin')->put('/admin/settings', $this->payload(['otp_enabled' => 0, 'password_enabled' => 0]))
            ->assertSessionHasErrors('otp_enabled');
        $this->assertTrue(SiteSetting::otpLoginEnabled());
    }

    public function test_settings_validation(): void
    {
        $a = $this->admin(['is_super' => true]);

        $this->actingAs($a, 'admin')->put('/admin/settings', $this->payload(['support_email' => 'bad', 'site_name' => '']))
            ->assertSessionHasErrors(['support_email', 'site_name']);
    }

    // ---------- اعمال روی ورود کاربران
    public function test_disabled_otp_blocks_sms_login_and_hides_the_form(): void
    {
        SiteSetting::set('auth.otp_enabled', false);

        $this->post(route('login.otp'), ['mobile' => '09123456789'])->assertSessionHasErrors('mobile');
        $this->get(route('login'))->assertOk()
            ->assertDontSee(route('login.otp'), false)
            ->assertSee(route('login.password'), false);
    }

    public function test_disabled_password_blocks_password_login_and_hides_the_form(): void
    {
        SiteSetting::set('auth.password_enabled', false);
        \App\Models\User::create(['mobile' => '09123456789', 'status' => 'active', 'password' => bcrypt('pw-123456')]);

        $this->post(route('login.password'), ['mobile' => '09123456789', 'password' => 'pw-123456'])->assertSessionHasErrors('mobile');
        $this->assertGuest();
        $this->get(route('login'))->assertDontSee(route('login.password'), false)->assertSee(route('login.otp'), false);
    }

    public function test_otp_session_started_earlier_cannot_be_verified_after_otp_is_disabled(): void
    {
        SiteSetting::set('auth.otp_enabled', false);

        $this->withSession(['otp_mobile' => '09123456789'])
            ->post(route('login.otp.verify'), ['code' => '123456'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ---------- ۲FA ادمین
    public function test_admin_can_enable_two_factor_and_login_then_requires_code(): void
    {
        $a = $this->admin(['username' => 'twofa', 'password' => 'secret-pass-1']);

        $this->actingAs($a, 'admin')->get('/admin/account/two-factor')->assertOk();
        $secret = session('admin_2fa_setup_secret');
        $this->assertNotEmpty($secret);

        $this->post('/admin/account/two-factor', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/admin/account/two-factor', ['code' => Totp::code($secret)])->assertRedirect(route('admin.account'));

        $fresh = $a->fresh();
        $this->assertTrue($fresh->two_factor_enabled);
        $this->assertSame($secret, $fresh->two_factor_secret);
        $this->assertNotSame($secret, $fresh->getRawOriginal('two_factor_secret')); // رمزنگاری‌شده در دیتابیس

        auth('admin')->logout();
        $this->flushSession();

        // رمز درست: هنوز وارد نشده و به چالش می‌رود
        $this->post('/admin/login', ['username' => 'twofa', 'password' => 'secret-pass-1'])->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertGuest('admin');
        $this->get('/admin')->assertRedirect(route('admin.login'));

        $this->post('/admin/two-factor', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest('admin');

        $this->post('/admin/two-factor', ['code' => Totp::code($secret)])->assertRedirect();
        $this->assertAuthenticatedAs($a, 'admin');
        $this->assertNotNull($a->fresh()->last_login_at);
    }

    public function test_wrong_password_never_reaches_the_challenge(): void
    {
        $a = $this->admin(['username' => 'x2', 'two_factor_enabled' => true, 'two_factor_secret' => Totp::generateSecret()]);

        $this->post('/admin/login', ['username' => 'x2', 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->get('/admin/two-factor')->assertRedirect(route('admin.login'));
    }

    public function test_challenge_locks_after_too_many_wrong_codes(): void
    {
        $secret = Totp::generateSecret();
        $this->admin(['username' => 'x3', 'two_factor_enabled' => true, 'two_factor_secret' => $secret]);

        $this->post('/admin/login', ['username' => 'x3', 'password' => 'secret-pass-1']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/two-factor', ['code' => '000000']);
        }

        // حتی کد درست هم پس از قفل پذیرفته نمی‌شود
        $this->post('/admin/two-factor', ['code' => Totp::code($secret)])->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_disable_two_factor_needs_current_password(): void
    {
        $a = $this->admin(['two_factor_enabled' => true, 'two_factor_secret' => Totp::generateSecret()]);

        $this->actingAs($a, 'admin')->delete('/admin/account/two-factor', ['password' => 'nope'])->assertSessionHasErrors('password');
        $this->assertTrue($a->fresh()->two_factor_enabled);

        $this->delete('/admin/account/two-factor', ['password' => 'secret-pass-1'])->assertRedirect(route('admin.account'));
        $this->assertFalse($a->fresh()->two_factor_enabled);
    }

    public function test_admin_with_permission_can_reset_another_admins_two_factor(): void
    {
        $manager = $this->admin([], ['admin.users.manage']);
        $target = $this->admin(['two_factor_enabled' => true, 'two_factor_secret' => Totp::generateSecret()]);

        $this->actingAs($manager, 'admin')->post("/admin/users/{$target->id}/reset-two-factor")->assertSessionHas('status');
        $this->assertFalse($target->fresh()->two_factor_enabled);
        $this->assertNull($target->fresh()->two_factor_secret);

        $super = $this->admin(['is_super' => true, 'two_factor_enabled' => true, 'two_factor_secret' => Totp::generateSecret()]);
        $this->actingAs($manager, 'admin')->post("/admin/users/{$super->id}/reset-two-factor")->assertForbidden();
        $this->assertTrue($super->fresh()->two_factor_enabled);
    }

    public function test_admin_can_change_own_password(): void
    {
        $a = $this->admin(['password' => 'old-password-1']);

        $this->actingAs($a, 'admin')->post('/admin/account/password', [
            'current_password' => 'wrong', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrors('current_password');

        $this->post('/admin/account/password', [
            'current_password' => 'old-password-1', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1',
        ])->assertRedirect(route('admin.account'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password-1', $a->fresh()->password));
    }

    /** با خاموش بودن OTP، فقط دعوت‌نامهٔ معتبرِ همان شماره می‌تواند کد بگیرد. */
    public function test_invitation_still_works_when_otp_login_is_disabled(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $gateway = new class implements SmsGateway {
            public function send(string $mobile, string $text): string
            {
                return 'fake';
            }
        };
        $this->app->instance(SmsGateway::class, $gateway);

        $owner = User::create(['mobile' => '09120000001', 'status' => 'active']);
        $building = Building::create(['name' => 'برج', 'code' => '22222', 'created_by' => $owner->id]);
        $token = app(CurrentBuilding::class)->actingAs($building->id, fn () => app(InvitationService::class)->invite('09121112233', 'tenant', $owner)[1]);

        SiteSetting::set('auth.otp_enabled', false);

        // شمارهٔ دیگر با توکن معتبر: رد می‌شود
        $this->post(route('login.otp'), ['mobile' => '09129999999', 'invite' => $token])->assertSessionHasErrors('mobile');
        $this->assertNull(session('otp_mobile'));

        // بدون توکن: رد می‌شود
        $this->post(route('login.otp'), ['mobile' => '09121112233'])->assertSessionHasErrors('mobile');

        // شمارهٔ دعوت‌شده با توکن خودش: مجاز است
        $this->post(route('login.otp'), ['mobile' => '09121112233', 'invite' => $token])->assertSessionHasNoErrors();
        $this->assertSame('09121112233', session('otp_mobile'));
    }
}
