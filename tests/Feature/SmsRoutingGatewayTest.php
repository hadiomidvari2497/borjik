<?php

namespace Tests\Feature;

use App\Models\SmsProvider;
use App\Services\Sms\SmsProviderConfig;
use App\Services\Sms\SmsRoutingGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsRoutingGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sms.allow_private_hosts' => true]);
    }

    private function provider(string $name, array $config, bool $default = false): SmsProvider
    {
        return SmsProvider::create([
            'name' => $name,
            'driver' => 'http',
            'config' => $config,
            'is_active' => true,
            'is_default' => $default,
        ]);
    }

    private function normalConfig(): array
    {
        return [
            'method' => 'POST',
            'url' => 'https://normal.example.test/send',
            'number_format' => 'local',
            'headers' => [],
            'body_type' => 'form',
            'body' => "to={{mobile}}\ntext={{message}}",
            'params' => [],
            'success' => [],
            'ref_path' => '',
            'timeout' => 5,
        ];
    }

    private function sharedConfig(int $fallbackId, array $bodyIds): array
    {
        return [
            'method' => 'GET',
            'url' => 'https://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber2',
            'number_format' => 'local',
            'headers' => [],
            'body_type' => 'form',
            'body' => "username={{username}}\npassword={{password}}\ntext={{pattern_args}}\nto={{mobile}}\nbodyId={{body_id}}",
            'params' => [
                ['name' => 'username', 'value' => 'test-user', 'secret' => false],
                ['name' => 'password', 'value' => 'test-password', 'secret' => true],
                ['name' => 'body_id', 'value' => '', 'secret' => false],
            ],
            'template_body_ids' => $bodyIds,
            'body_id_fallback_provider_id' => $fallbackId,
            'success' => [],
            'ref_path' => '',
            'timeout' => 5,
        ];
    }

    public function test_template_with_body_id_uses_shared_line_and_missing_body_id_uses_selected_regular_provider(): void
    {
        $normal = $this->provider('ملی‌پیامک معمولی', $this->normalConfig());
        $shared = $this->provider('ملی‌پیامک اشتراکی', $this->sharedConfig($normal->id, ['receipt_approved' => '12345']), true);

        Http::fake([
            'api.payamak-panel.com/*' => Http::response('123456789012345678', 200),
            'normal.example.test/*' => Http::response('OK', 200),
        ]);

        $gateway = new SmsRoutingGateway($shared);
        $gateway->send('09123456789', 'فیش تأیید شد', 'receipt_approved', ['amount' => '250000', 'unit' => '12']);
        $this->assertSame('ملی‌پیامک اشتراکی', $gateway->label());

        $gateway->send('09123456789', 'یادآوری عادی', 'expense_reminder', ['month' => 'مهر']);
        $this->assertSame('ملی‌پیامک معمولی', $gateway->label());

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'SendByBaseNumber2')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['text'] ?? null) === '250000;12'
                && (string) ($query['bodyId'] ?? '') === '12345';
        });
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'normal.example.test')
            && $request['text'] === 'یادآوری عادی');
    }

    public function test_pattern_args_is_a_supported_generated_placeholder(): void
    {
        $this->assertContains('pattern_args', SmsProviderConfig::BUILTIN);
        $this->assertSame(['pattern_args'], SmsProviderConfig::placeholdersIn('{{pattern_args}}'));
    }

    public function test_missing_or_inactive_selected_fallback_fails_closed(): void
    {
        $normal = $this->provider('ملی‌پیامک معمولی', $this->normalConfig());
        $shared = $this->provider('ملی‌پیامک اشتراکی', $this->sharedConfig($normal->id, []));

        $normal->update(['is_active' => false]);

        $this->expectException(\App\Services\Sms\SmsException::class);
        (new SmsRoutingGateway($shared))->send('09123456789', 'پیام', 'expense_reminder', []);
    }
}
