<?php

namespace Tests\Feature;

use App\Models\SmsLog;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** این تست‌ها فرض می‌کنند routes/auth.php در web.php لود شده و مسیری به نام dashboard وجود دارد (README). */
class OtpFlowTest extends TestCase
{
    use RefreshDatabase;

    private object $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new class implements SmsGateway {
            public array $sent = [];

            public function send(string $mobile, string $text): string
            {
                $this->sent[] = [$mobile, $text];

                return 'fake';
            }
        };
        $this->app->instance(SmsGateway::class, $this->gateway);
    }

    private function lastCode(): string
    {
        preg_match('/\d{6}/', end($this->gateway->sent)[1], $m);

        return $m[0];
    }

    public function test_new_user_registers_and_logs_in_with_otp(): void
    {
        $this->post(route('login.otp'), ['mobile' => '۰۹۱۲۳۴۵۶۷۸۹']);
        $this->assertCount(1, $this->gateway->sent);
        $this->assertSame('09123456789', $this->gateway->sent[0][0]);

        $this->withSession(['otp_mobile' => '09123456789'])
            ->post(route('login.otp.verify'), ['code' => $this->lastCode()]);

        $this->assertAuthenticated();
        $user = User::where('mobile', '09123456789')->firstOrFail();
        $this->assertNotNull($user->mobile_verified_at);
        $this->assertNull($user->profile_completed_at);
    }

    public function test_wrong_code_locks_the_code_after_five_attempts(): void
    {
        $this->post(route('login.otp'), ['mobile' => '09123456789']);
        $good = $this->lastCode();
        $wrong = $good === '111111' ? '222222' : '111111';

        for ($i = 0; $i < 5; $i++) {
            $this->withSession(['otp_mobile' => '09123456789'])->post(route('login.otp.verify'), ['code' => $wrong]);
        }
        $this->withSession(['otp_mobile' => '09123456789'])->post(route('login.otp.verify'), ['code' => $good]);

        $this->assertGuest();
    }

    public function test_resend_within_a_minute_is_blocked(): void
    {
        $this->post(route('login.otp'), ['mobile' => '09123456789']);
        $this->post(route('login.otp'), ['mobile' => '09123456789'])->assertSessionHasErrors('mobile');

        $this->assertCount(1, $this->gateway->sent);
    }

    public function test_user_with_two_factor_cannot_use_sms_only_login(): void
    {
        User::create(['mobile' => '09123456789', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'GEZDGNBVGY3TQOJQ']);

        $this->post(route('login.otp'), ['mobile' => '09123456789'])->assertSessionHasErrors('mobile');
        $this->assertCount(0, $this->gateway->sent);
    }

    public function test_inactive_user_cannot_complete_a_two_factor_challenge(): void
    {
        $secret = Totp::generateSecret();
        $user = User::create([
            'mobile' => '09129999999',
            'first_name' => 'کاربر',
            'last_name' => 'غیرفعال',
            'status' => 'active',
            'password' => 'correct-password',
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);

        $this->post(route('login.password'), [
            'mobile' => $user->mobile,
            'password' => 'correct-password',
        ])->assertRedirect(route('two-factor.challenge'));

        $user->forceFill(['status' => 'inactive'])->save();

        $response = $this->post('/two-factor/challenge', ['code' => Totp::code($secret)]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors('mobile')
            ->assertSessionMissing('2fa_user_id');

        $this->assertGuest();
    }

    public function test_otp_text_is_not_stored_in_sms_logs(): void
    {
        $this->post(route('login.otp'), ['mobile' => '09123456789']);

        $this->assertSame('[محرمانه]', SmsLog::firstOrFail()->body);
        $this->assertSame('sent', SmsLog::firstOrFail()->status);
    }
}
