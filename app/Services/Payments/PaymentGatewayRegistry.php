<?php

namespace App\Services\Payments;

use App\Models\SiteSetting;
use Illuminate\Support\Str;

class PaymentGatewayRegistry
{
    /** @return array<string,array<string,mixed>> */
    public function all(): array
    {
        $gateways = SiteSetting::get('payment.gateways', []);
        if (is_array($gateways) && $gateways !== []) {
            return $gateways;
        }

        // Backward compatibility for installations configured before multi-gateway support.
        $legacy = SiteSetting::get('payment.gateway', []);
        if (is_array($legacy) && ! empty($legacy['driver'])) {
            return ['legacy' => $legacy];
        }

        return [];
    }

    /** @return array<string,array<string,mixed>> */
    public function active(): array
    {
        return array_filter($this->all(), static function ($gateway): bool {
            if (! is_array($gateway) || empty($gateway['active'])) {
                return false;
            }

            return match ((string) ($gateway['driver'] ?? '')) {
                'bitpay' => ! empty($gateway['api_key']),
                'zarinpal' => ! empty($gateway['merchant_id']),
                default => false,
            };
        });
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        $all = $this->all();

        return isset($all[$id]) && is_array($all[$id]) ? $all[$id] : null;
    }

    public function newId(): string
    {
        return (string) Str::uuid();
    }
}
