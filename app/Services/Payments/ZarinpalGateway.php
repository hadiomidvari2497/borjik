<?php

namespace App\Services\Payments;

use App\Models\SiteSetting;
use App\Models\User;
use RuntimeException;

class ZarinpalGateway
{
    /** @return array{authority:string,payment_url:string,response:array<string,mixed>} */
    public function request(int $amountRial, int $paymentId, User $user): array
    {
        $settings = $this->settings();
        $base = $this->apiBase($settings);
        $response = Http::acceptJson()->asJson()->timeout(20)->post($base.'/payment/request.json', [
            'merchant_id' => (string) $settings['merchant_id'],
            'amount' => $amountRial,
            'callback_url' => route('payment.gateway.callback'),
            'description' => 'خرید اشتراک برجیک - تراکنش '.$paymentId,
            'metadata' => ['mobile' => (string) $user->mobile, 'email' => (string) ($user->email ?? '')],
        ])->throw()->json();

        $code = (int) data_get($response, 'data.code', 0);
        $authority = (string) data_get($response, 'data.authority', '');
        if ($code !== 100 || $authority === '') {
            throw new RuntimeException('Zarinpal request rejected with code '.$code);
        }

        $startBase = ($settings['environment'] ?? 'production') === 'sandbox'
            ? 'https://sandbox.zarinpal.com/pg/StartPay/'
            : 'https://payment.zarinpal.com/pg/StartPay/';

        return ['authority' => $authority, 'payment_url' => $startBase.$authority, 'response' => $response];
    }

    /** @return array<string,mixed> */
    public function verify(string $authority, int $amountRial): array
    {
        $settings = $this->settings();

        return Http::acceptJson()->asJson()->timeout(20)->post($this->apiBase($settings).'/payment/verify.json', [
            'merchant_id' => (string) $settings['merchant_id'],
            'amount' => $amountRial,
            'authority' => $authority,
        ])->throw()->json();
    }

    /** @param array<string,mixed> $settings */
    private function apiBase(array $settings): string
    {
        $base = rtrim((string) ($settings['api_base_url'] ?? ''), '/');
        if ($base !== '') {
            return $base;
        }

        return ($settings['environment'] ?? 'production') === 'sandbox'
            ? 'https://sandbox.zarinpal.com/pg/v4'
            : 'https://payment.zarinpal.com/pg/v4';
    }

    /** @return array<string,mixed> */
    private function settings(): array
    {
        $settings = SiteSetting::get('payment.gateway', []);
        if (! is_array($settings) || empty($settings['active']) || ($settings['driver'] ?? '') !== 'zarinpal' || empty($settings['merchant_id'])) {
            throw new RuntimeException('Zarinpal gateway is not configured.');
        }

        return $settings;
    }
}
