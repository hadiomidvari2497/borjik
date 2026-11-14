<?php

namespace App\Services\Payments;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystarGateway
{
    private const BASE_URL = 'https://core.paystar.ir/api/pardakht';

    /** @return array{order_id:string,ref_num:string,token:string,payment_url:string,response:array<string,mixed>} */
    public function request(int $amountRial, string $orderId, User $user, ?array $configuredSettings = null, ?string $callbackUrl = null): array
    {
        $settings = $this->settings($configuredSettings);
        if ($amountRial < 100000 || $amountRial > 500000000) {
            throw new RuntimeException('Paystar transaction amount must be between 100,000 and 500,000,000 Rial.');
        }

        $callback = $callbackUrl ?? route('payment.gateway.callback', ! empty($settings['_gateway_id']) ? ['gateway_id' => $settings['_gateway_id']] : []);
        $sign = hash_hmac('sha512', $amountRial.'#'.$orderId.'#'.$callback, $this->secret($settings['api_secret'] ?? null));
        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->secret($settings['api_key'] ?? null))
            ->timeout(30)
            ->post($this->baseUrl($settings).'/create', [
                'amount' => $amountRial,
                'order_id' => $orderId,
                'callback' => $callback,
                'callback_method' => 1,
                'sign' => $sign,
                'name' => (string) $user->name,
                'phone' => (string) ($user->mobile ?? ''),
                'mail' => (string) ($user->email ?? ''),
                'description' => 'پرداخت صابخونه - '.$orderId,
            ])
            ->throw()
            ->json();

        $token = trim((string) data_get($response, 'data.token', ''));
        $refNum = trim((string) data_get($response, 'data.ref_num', ''));
        if ((int) data_get($response, 'status', 0) !== 1 || $token === '' || $refNum === '') {
            throw new RuntimeException('Paystar payment request was rejected: '.mb_substr((string) data_get($response, 'message', 'Invalid create response.'), 0, 250));
        }

        return [
            'order_id' => $orderId,
            'ref_num' => $refNum,
            'token' => $token,
            'payment_url' => $this->baseUrl($settings).'/payment?'.http_build_query(['token' => $token]),
            'response' => is_array($response) ? $response : [],
        ];
    }

    /** @return array{verified:bool,response:array<string,mixed>} */
    public function verify(string $refNum, int $amountRial, string $cardNumber, string $trackingCode, ?array $configuredSettings = null): array
    {
        $settings = $this->settings($configuredSettings);
        if ($refNum === '' || $cardNumber === '' || $trackingCode === '' || $amountRial <= 0) {
            throw new RuntimeException('Paystar callback is missing required verification fields.');
        }

        $signData = $amountRial.'#'.$refNum.'#'.$cardNumber.'#'.$trackingCode;
        $sign = hash_hmac('sha512', $signData, $this->secret($settings['api_secret'] ?? null));
        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->secret($settings['api_key'] ?? null))
            ->timeout(20)
            ->post($this->baseUrl($settings).'/verify', [
                'ref_num' => $refNum,
                'amount' => $amountRial,
                'sign' => $sign,
            ])
            ->throw()
            ->json();

        $verified = is_array($response)
            && (int) data_get($response, 'status', 0) === 1
            && (int) data_get($response, 'data.price', 0) === $amountRial
            && hash_equals($refNum, (string) data_get($response, 'data.ref_num', ''));

        return ['verified' => $verified, 'response' => is_array($response) ? $response : []];
    }

    /** @param array<string,mixed> $settings */
    private function baseUrl(array $settings): string
    {
        $base = trim((string) ($settings['api_base_url'] ?? ''));
        return rtrim($base !== '' ? $base : self::BASE_URL, '/');
    }

    /** @return array<string,mixed> */
    private function settings(?array $configuredSettings = null): array
    {
        $settings = $configuredSettings ?? SiteSetting::get('payment.gateway', []);
        if (! is_array($settings)
            || empty($settings['active'])
            || ($settings['driver'] ?? '') !== 'paystar'
            || empty($settings['api_key'])
            || empty($settings['api_secret'])) {
            throw new RuntimeException('Paystar gateway is not configured.');
        }

        return $settings;
    }

    private function secret(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            throw new RuntimeException('Paystar sign key is missing.');
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return $value;
        }
    }
}
