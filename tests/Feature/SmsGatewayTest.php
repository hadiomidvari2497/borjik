<?php

namespace Tests\Feature;

use App\Jobs\SendSmsJob;
use App\Models\SmsLog;
use App\Models\SmsProvider;
use App\Services\Sms\HttpSmsGateway;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\SmsException;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsGatewayFactory;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** درایور عمومی HTTP: هر اپراتوری فقط با تنظیمات پنل قابل اتصال است. */
class SmsGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function provider(array $config = [], array $attrs = []): SmsProvider
    {
        static $n = 0;
        $n++;

        return SmsProvider::create($attrs + [
            'name' => "p{$n}", 'driver' => 'http', 'is_active' => true, 'is_default' => false,
            'config' => $config + [
                'method' => 'POST', 'url' => 'https://sms.example.com/send', 'number_format' => 'local', 'headers' => [],
                'body_type' => 'form', 'body' => "to={{mobile}}\ntext={{message}}", 'params' => [],
                'success' => [], 'ref_path' => '', 'timeout' => 15,
            ],
        ]);
    }

    /** مشابه کاوه‌نگار: کلید در مسیر، فرم، موفقیت = return.status == 200 */
    private function kavenegarLike(): SmsProvider
    {
        return $this->provider([
            'url' => 'https://sms.example.com/v1/{{api_key}}/sms/send.json',
            'body' => "receptor={{mobile}}\nsender={{sender}}\nmessage={{message}}",
            'params' => [['name' => 'api_key', 'value' => 'SECRET-KEY-123', 'secret' => true], ['name' => 'sender', 'value' => '3000', 'secret' => false]],
            'success' => ['json_path' => 'return.status', 'expected' => '200'],
            'ref_path' => 'entries.0.messageid',
        ]);
    }

    public function test_form_request_with_key_in_url_and_json_success_rule(): void
    {
        Http::fake(['sms.example.com/*' => Http::response(['return' => ['status' => 200], 'entries' => [['messageid' => 8812]]])]);

        $ref = (new HttpSmsGateway($this->kavenegarLike()))->send('09123456789', 'سلام دنیا');

        $this->assertSame('8812', $ref);
        Http::assertSent(function (Request $r) {
            return $r->method() === 'POST'
                && $r->url() === 'https://sms.example.com/v1/SECRET-KEY-123/sms/send.json'
                && $r['receptor'] === '09123456789' && $r['sender'] === '3000' && $r['message'] === 'سلام دنیا'
                && str_contains($r->header('Content-Type')[0], 'application/x-www-form-urlencoded');
        });
    }

    public function test_provider_rejection_with_http_200_is_a_failure_and_secrets_are_masked(): void
    {
        Http::fake(['*' => Http::response(['return' => ['status' => 401, 'message' => 'bad key SECRET-KEY-123']])]);

        try {
            (new HttpSmsGateway($this->kavenegarLike()))->send('09123456789', 'x');
            $this->fail('باید خطا می‌داد');
        } catch (SmsException $e) {
            $this->assertStringContainsString('return.status', $e->getMessage());
            $this->assertStringNotContainsString('SECRET-KEY-123', $e->getMessage());
            $this->assertStringContainsString('***', $e->getMessage());
        }
    }

    public function test_http_error_status_is_a_failure(): void
    {
        Http::fake(['*' => Http::response('server down', 503)]);

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage('HTTP 503');
        (new HttpSmsGateway($this->provider()))->send('09123456789', 'x');
    }

    public function test_json_body_escapes_message_and_formats_number_and_sends_headers(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $p = $this->provider([
            'number_format' => 'intl', 'body_type' => 'json',
            'body' => '{"lineNumber": {{sender}}, "MessageText": "{{message}}", "Mobiles": ["{{mobile}}"]}',
            'headers' => [['name' => 'X-API-KEY', 'value' => '{{api_key}}']],
            'params' => [['name' => 'api_key', 'value' => 'K-1', 'secret' => true], ['name' => 'sender', 'value' => '30007732', 'secret' => false]],
        ]);

        (new HttpSmsGateway($p))->send('09123456789', "او گفت \"سلام\"\nخط دوم");

        Http::assertSent(function (Request $r) {
            $b = json_decode($r->body(), true);

            return $b !== null
                && $b['MessageText'] === "او گفت \"سلام\"\nخط دوم"
                && $b['Mobiles'] === ['989123456789'] && $b['lineNumber'] === 30007732
                && $r->header('X-API-KEY')[0] === 'K-1'
                && str_contains($r->header('Content-Type')[0], 'application/json');
        });
    }

    public function test_get_method_sends_form_lines_as_query(): void
    {
        Http::fake(['*' => Http::response('OK')]);
        $p = $this->provider(['method' => 'GET', 'url' => 'https://sms.example.com/send?u={{user}}',
            'params' => [['name' => 'user', 'value' => 'a b', 'secret' => false]]]);

        (new HttpSmsGateway($p))->send('09123456789', 'hi');

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && str_contains($r->url(), 'u=a%20b') && str_contains($r->url(), 'to=09123456789') && str_contains($r->url(), 'text=hi'));
    }

    public function test_contains_rule(): void
    {
        Http::fake(['*' => Http::response('status=ERR')]);
        $p = $this->provider(['success' => ['contains' => 'status=OK']]);

        $this->expectException(SmsException::class);
        (new HttpSmsGateway($p))->send('09123456789', 'x');
    }

    public function test_redirects_are_not_followed(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest'])]);

        $this->expectException(SmsException::class);
        (new HttpSmsGateway($this->provider()))->send('09123456789', 'x');
        Http::assertSentCount(1);
    }

    public function test_private_and_internal_hosts_are_blocked_unless_explicitly_allowed(): void
    {
        Http::fake(['*' => Http::response('OK')]);

        foreach (['http://127.0.0.1/send', 'http://10.0.0.5/send', 'http://169.254.169.254/x', 'http://[::1]/x', 'ftp://example.com/x'] as $url) {
            try {
                (new HttpSmsGateway($this->provider(['url' => $url])))->send('09123456789', 'x');
                $this->fail("باید مسدود می‌شد: {$url}");
            } catch (SmsException) {
                $this->assertTrue(true);
            }
        }
        Http::assertNothingSent();

        config(['sms.allow_private_hosts' => true]);
        (new HttpSmsGateway($this->provider(['url' => 'http://127.0.0.1/send'])))->send('09123456789', 'x');
        Http::assertSentCount(1);
    }

    public function test_factory_uses_default_active_provider_else_log_driver(): void
    {
        $this->assertInstanceOf(LogSmsGateway::class, app(SmsGateway::class));

        $this->provider([], ['is_default' => true, 'is_active' => false]);
        $this->assertInstanceOf(LogSmsGateway::class, app(SmsGatewayFactory::class)->make());

        $this->provider([], ['is_default' => true, 'is_active' => true, 'name' => 'main']);
        $gw = app(SmsGateway::class);
        $this->assertInstanceOf(HttpSmsGateway::class, $gw);
        $this->assertSame('main', $gw->label());
    }

    public function test_job_records_gateway_on_success_and_error_on_failure(): void
    {
        $this->provider(['success' => ['json_path' => 'ok', 'expected' => '1']], ['is_default' => true, 'name' => 'اپراتور من']);
        $log = SmsLog::create(['mobile' => '09123456789', 'body' => 'x', 'status' => 'queued']);

        Http::fake(['*' => Http::sequence()->push(['ok' => 1])->push(['ok' => 0])]);
        (new SendSmsJob($log->id, '09123456789', 'x'))->handle(app(SmsGateway::class));
        $this->assertSame(['sent', 'اپراتور من', null], [$log->fresh()->status, $log->fresh()->gateway, $log->fresh()->error]);

        try {
            (new SendSmsJob($log->id, '09123456789', 'x'))->handle(app(SmsGateway::class));
        } catch (SmsException) {
        }
        $fresh = $log->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertStringContainsString('تأیید نکرد', $fresh->error);
    }

    public function test_config_is_encrypted_at_rest(): void
    {
        $p = $this->kavenegarLike();

        $raw = \Illuminate\Support\Facades\DB::table('sms_providers')->where('id', $p->id)->value('config');
        $this->assertStringNotContainsString('SECRET-KEY-123', $raw);
        $this->assertStringNotContainsString('sms.example.com', $raw);
        $this->assertSame('SECRET-KEY-123', $p->fresh()->settings()['params'][0]['value']);
    }
    public function test_sms_sender_delivers_immediately_and_records_final_status_without_a_queue_worker(): void
    {
        config(['sms.queue' => false]);
        $this->provider(['success' => ['json_path' => 'ok', 'expected' => '1'], 'ref_path' => 'id'], ['is_default' => true, 'name' => 'اپراتور فوری']);
        Http::fake(['*' => Http::response(['ok' => 1, 'id' => 'ref-123'])]);

        $log = app(SmsSender::class)->sendTemplate('otp', '09123456789', ['code' => '123456']);

        $this->assertSame('sent', $log->fresh()->status);
        $this->assertSame('اپراتور فوری', $log->fresh()->gateway);
        $this->assertSame('[محرمانه]', $log->fresh()->body);
        $this->assertSame('ref-123', $log->fresh()->provider_ref);
        $this->assertNotNull($log->fresh()->sent_at);
        Http::assertSent(fn (Request $request) => $request['text'] === 'صابخونه: کد ورود شما 123456 است. اعتبار ۲ دقیقه.');
    }

    public function test_sms_sender_marks_delivery_failed_when_no_real_provider_is_configured(): void
    {
        config(['sms.queue' => false, 'sms.driver' => 'log']);

        $log = app(SmsSender::class)->sendTemplate('otp', '09123456789', ['code' => '654321']);

        $this->assertSame('failed', $log->fresh()->status);
        $this->assertSame('log (بدون ارسال واقعی)', $log->fresh()->gateway);
        $this->assertStringContainsString('پنل پیامکی فعال نیست', $log->fresh()->error);
        $this->assertSame('[محرمانه]', $log->fresh()->body);
    }

}
