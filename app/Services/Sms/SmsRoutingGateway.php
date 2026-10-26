<?php

namespace App\Services\Sms;

use App\Models\SmsProvider;

/**
 * Routes templated SMS through the shared-line provider when that template has a Body ID,
 * and otherwise through the explicitly selected regular provider.
 */
class SmsRoutingGateway implements SmsGateway
{
    private ?SmsProvider $lastProvider = null;

    public function __construct(private readonly SmsProvider $current)
    {
        $this->lastProvider = $current;
    }

    public function label(): string
    {
        return $this->lastProvider?->name ?? $this->current->name;
    }

    public function send(string $mobile, string $text, ?string $templateKey = null, array $templateVars = []): string
    {
        $provider = $this->providerFor($templateKey);
        $this->lastProvider = $provider;

        return (new HttpSmsGateway($provider))->send($mobile, $text, $templateKey, $templateVars);
    }

    private function providerFor(?string $templateKey): SmsProvider
    {
        $settings = $this->current->settings();
        $url = strtolower((string) ($settings['url'] ?? ''));
        $bodyIds = $settings['template_body_ids'] ?? [];

        // Keep fallback routing scoped to the shared-line pattern endpoint, not regular providers.
        if (! str_contains($url, 'sendbybasenumber2') && empty($bodyIds)) {
            return $this->current;
        }
        $bodyId = trim((string) ($bodyIds[$templateKey ?? ''] ?? ''));

        // A configured Body ID always takes precedence; do not route those messages away.
        if ($bodyId !== '') {
            return $this->current;
        }

        $fallbackId = (int) ($settings['body_id_fallback_provider_id'] ?? 0);
        if ($fallbackId < 1) {
            return $this->current;
        }

        $fallback = SmsProvider::query()
            ->whereKey($fallbackId)
            ->where('is_active', true)
            ->first();

        if (! $fallback || $fallback->is($this->current)) {
            throw new SmsException('اتصال معمولی انتخاب‌شده برای پیامک‌های بدون Body ID فعال نیست؛ تنظیمات اتصال خدماتی اشتراکی را بررسی کنید.');
        }

        return $fallback;
    }
}
