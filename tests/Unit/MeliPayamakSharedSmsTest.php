<?php

namespace Tests\Unit;

use App\Models\SmsProvider;
use App\Services\Sms\HttpSmsGateway;
use App\Services\Sms\SmsException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeliPayamakSharedSmsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['sms.allow_private_hosts' => true]);
    }

    private function gateway(string $bodyId = '12345'): HttpSmsGateway
    {
        $provider = new SmsProvider([
            'name' => 'ملی‌پیامک اشتراکی',
            'config' => [
                'method' => 'GET',
                'url' => 'https://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber2',
                'number_format' => 'local',
                'headers' => [],
                'body_type' => 'form',
                'body' => "username={{username}}\npassword={{password}}\ntext={{pattern_args}}\nto={{mobile}}\nbodyId={{body_id}}",
                'params' => [
                    ['name' => 'username', 'value' => 'test-user', 'secret' => false],
                    ['name' => 'password', 'value' => 'test-password', 'secret' => true],
                ],
                'template_body_ids' => ['receipt_approved' => $bodyId],
                'success' => [],
                'ref_path' => '',
                'timeout' => 5,
            ],
        ]);

        return new HttpSmsGateway($provider);
    }

    public function test_shared_line_sends_the_template_body_id_and_ordered_pattern_arguments(): void
    {
        Http::fake(['api.payamak-panel.com/*' => Http::response('123456789012345678', 200)]);

        $ref = $this->gateway()->send(
            '09123456789',
            'فیش شما تأیید شد',
            'receipt_approved',
            ['amount' => '250000', 'unit' => '12']
        );

        $this->assertSame('http-'.substr($ref, 5), $ref);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'SendByBaseNumber2')
                && $request->url() !== ''
                && $request['text'] === '250000;12'
                && (string) $request['bodyId'] === '12345'
                && $request['to'] === '09123456789';
        });
    }

    public function test_shared_line_http_200_with_provider_error_code_is_not_marked_successful(): void
    {
        Http::fake(['api.payamak-panel.com/*' => Http::response('-5', 200)]);

        try {
            $this->gateway()->send('09123456789', 'متن', 'receipt_approved', ['amount' => '250000']);
            $this->fail('A MeliPayamak pattern error code must throw.');
        } catch (SmsException $exception) {
            $this->assertStringContainsString('متغیرهای الگو', $exception->getMessage());
        }
    }
}
