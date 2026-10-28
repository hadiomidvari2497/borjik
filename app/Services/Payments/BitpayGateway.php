<?php

namespace App\Services\Payments;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BitpayGateway
{
    /** @return array{id_get:string,payment_url:string,response:string} */
    public function request(int $amountRial, int $paymentId, User $user, ?array $configuredSettings = null): array
    {
        $settings = $this->settings($configuredSettings);
        $api = $this->secret($settings['api_key'] ?? null);
        $callback = route('payment.gateway.callback', ! empty($settings['_gateway_id']) ? ['gateway_id' => $settings['_gateway_id']] : []);
        $response = Http::asForm()->timeout(20)->post('https://bitpay.ir/payment/gateway-send', [
            'api' => $api,
            'amount' => $amountRial,
            'redirect' => $callback,
            'factorId' => (string) $paymentId,
            'name' => (string) $user->name,
            'email' => (string) ($user->email ?? ''),
            'description' => 'خرید اشتراک صابخونه - پرداخت '.$paymentId,
        ])->throw();

        $idGet = trim($response->body());
        if (! ctype_digit($idGet) || (int) $idGet <= 0) {
            throw new RuntimeException('Bitpay payment request was rejected: '.mb_substr($idGet, 0, 160));
        }

        return [
            'id_get' => $idGet,
            'payment_url' => 'https://bitpay.ir/payment/gateway-'.$idGet.'-get',
            'response' => mb_substr($response->body(), 0, 500),
        ];
    }

    /** @return array<string,mixed> */
    public function verify(string $idGet, string $transId, ?array $configuredSettings = null): array
    {
        $settings = $this->settings($configuredSettings);
        $response = Http::asForm()->acceptJson()->timeout(20)->post('https://bitpay.ir/payment/gateway-result-second', [
            'api' => $this->secret($settings['api_key'] ?? null),
            'id_get' => $idGet,
            'trans_id' => $transId,
            'json' => 1,
        ])->throw()->json();

        if (! is_array($response)) {
            throw new RuntimeException('Bitpay verification returned an invalid response.');
        }

        return $response;
    }

    /** @return array<string,mixed> */
    private function settings(?array $configuredSettings = null): array
    {
        $settings = $configuredSettings ?? SiteSetting::get('payment.gateway', []);
        if (! is_array($settings) || empty($settings['active']) || ($settings['driver'] ?? '') !== 'bitpay' || empty($settings['api_key'])) {
            throw new RuntimeException('Bitpay gateway is not configured.');
        }

        return $settings;
    }

    private function secret(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            throw new RuntimeException('Bitpay API key is missing.');
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Supports legacy plaintext values while settings are migrated.
            return $value;
        }
    }
}
