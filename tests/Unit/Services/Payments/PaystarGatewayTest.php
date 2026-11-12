<?php

namespace Tests\Unit\Services\Payments;

use App\Models\User;
use App\Services\Payments\PaystarGateway;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystarGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
    }

    public function test_it_creates_a_paystar_payment_with_a_signed_rial_amount(): void
    {
        Http::fake([
            'core.paystar.ir/api/pardakht/create' => Http::response([
                'status' => 1,
                'data' => ['token' => 'one-time-token', 'ref_num' => 'paystar-ref-123'],
            ], 200),
        ]);

        $user = new User();
        $user->name = 'Test User';
        $user->mobile = '09120000000';
        $user->email = 'test@example.com';

        $callback = 'https://sabkhoneh.ir/payment/gateway/callback?gateway_id=test-gateway';
        $settings = [
            'driver' => 'paystar',
            'active' => true,
            'api_key' => Crypt::encryptString('gateway-id-123'),
            'api_secret' => Crypt::encryptString('sign-key-123'),
        ];

        $result = app(PaystarGateway::class)->request(150000, 'SUB123', $user, $settings, $callback);

        $this->assertSame('paystar-ref-123', $result['ref_num']);
        $this->assertSame('one-time-token', $result['token']);
        $this->assertStringContainsString('/payment?token=one-time-token', $result['payment_url']);

        $expectedSign = hash_hmac('sha512', '150000#SUB123#'.$callback, 'sign-key-123');
        Http::assertSent(fn (ClientRequest $request) =>
            str_ends_with($request->url(), '/api/pardakht/create')
            && $request->hasHeader('Authorization', 'Bearer gateway-id-123')
            && data_get($request->data(), 'amount') === 150000
            && data_get($request->data(), 'order_id') === 'SUB123'
            && data_get($request->data(), 'callback_method') === 1
            && data_get($request->data(), 'sign') === $expectedSign
        );
    }

    public function test_it_accepts_only_a_verified_paystar_response_with_the_expected_amount_and_reference(): void
    {
        Http::fake([
            'core.paystar.ir/api/pardakht/verify' => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => ['price' => 150000, 'ref_num' => 'paystar-ref-123', 'card_number' => '6037****1234'],
            ], 200),
        ]);

        $settings = [
            'driver' => 'paystar',
            'active' => true,
            'api_key' => Crypt::encryptString('gateway-id-123'),
            'api_secret' => Crypt::encryptString('sign-key-123'),
        ];

        $result = app(PaystarGateway::class)->verify(
            'paystar-ref-123',
            150000,
            '603799******1234',
            'tracking-123',
            $settings
        );

        $this->assertTrue($result['verified']);

        $expectedSign = hash_hmac('sha512', '150000#paystar-ref-123#603799******1234#tracking-123', 'sign-key-123');
        Http::assertSent(fn (ClientRequest $request) =>
            str_ends_with($request->url(), '/api/pardakht/verify')
            && $request->hasHeader('Authorization', 'Bearer gateway-id-123')
            && data_get($request->data(), 'amount') === 150000
            && data_get($request->data(), 'ref_num') === 'paystar-ref-123'
            && data_get($request->data(), 'tracking_code') === 'tracking-123'
            && data_get($request->data(), 'sign') === $expectedSign
        );
    }

    public function test_it_rejects_a_verification_with_a_different_amount(): void
    {
        Http::fake([
            'core.paystar.ir/api/pardakht/verify' => Http::response([
                'status' => 1,
                'data' => ['price' => 149999, 'ref_num' => 'paystar-ref-123'],
            ], 200),
        ]);

        $settings = [
            'driver' => 'paystar',
            'active' => true,
            'api_key' => Crypt::encryptString('gateway-id-123'),
            'api_secret' => Crypt::encryptString('sign-key-123'),
        ];

        $result = app(PaystarGateway::class)->verify(
            'paystar-ref-123',
            150000,
            '603799******1234',
            'tracking-123',
            $settings
        );

        $this->assertFalse($result['verified']);
    }
}
