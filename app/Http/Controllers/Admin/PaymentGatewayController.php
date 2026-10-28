<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class PaymentGatewayController extends Controller
{
    private const KEY = 'payment.gateway';

    public function edit()
    {
        $settings = SiteSetting::get(self::KEY, []);
        $settings = is_array($settings) ? $settings : [];

        return view('admin.payment-gateway.index', [
            'gateway' => $settings,
            'hasApiKey' => ! empty($settings['api_key']),
            'hasApiSecret' => ! empty($settings['api_secret']),
            'hasWebhookSecret' => ! empty($settings['webhook_secret']),
            'callbackUrl' => url('/payment/gateway/callback'),
        ]);
    }

    public function update(Request $request)
    {
        $old = SiteSetting::get(self::KEY, []);
        $old = is_array($old) ? $old : [];

        $data = $request->validate([
            'provider_name' => ['nullable', 'string', 'max:100'],
            'driver' => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'environment' => ['required', 'in:sandbox,production'],
            'api_base_url' => ['nullable', 'url:http,https', 'max:500'],
            'merchant_id' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:2000'],
            'api_secret' => ['nullable', 'string', 'max:2000'],
            'webhook_secret' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            'clear_api_key' => ['nullable', 'boolean'],
            'clear_api_secret' => ['nullable', 'boolean'],
            'clear_webhook_secret' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'driver.regex' => 'شناسه فنی درگاه فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.',
            'environment.in' => 'محیط درگاه معتبر نیست.',
            'api_base_url.url' => 'آدرس API باید یک URL معتبر با http یا https باشد.',
        ]);

        $secret = function (string $field, string $clearField) use ($data, $old): ?string {
            if ((bool) ($data[$clearField] ?? false)) {
                return null;
            }
            $value = trim((string) ($data[$field] ?? ''));
            if ($value !== '') {
                return Crypt::encryptString($value);
            }

            return $old[$field] ?? null;
        };

        $settings = [
            'provider_name' => trim((string) ($data['provider_name'] ?? '')),
            'driver' => trim($data['driver']),
            'environment' => $data['environment'],
            'api_base_url' => trim((string) ($data['api_base_url'] ?? '')),
            'merchant_id' => trim((string) ($data['merchant_id'] ?? '')),
            'api_key' => $secret('api_key', 'clear_api_key'),
            'api_secret' => $secret('api_secret', 'clear_api_secret'),
            'webhook_secret' => $secret('webhook_secret', 'clear_webhook_secret'),
            'active' => $request->boolean('active'),
            'notes' => trim((string) ($data['notes'] ?? '')),
            'updated_at' => now()->toIso8601String(),
        ];

        if ($settings['active'] && $settings['provider_name'] === '') {
            throw ValidationException::withMessages(['provider_name' => 'برای فعال‌سازی اتصال، نام درگاه را وارد کنید.']);
        }

        if ($settings['active'] && $settings['driver'] === 'bitpay' && empty($settings['api_key'])) {
            throw ValidationException::withMessages(['api_key' => 'برای فعال‌سازی بیت‌پی، کد API را وارد کنید.']);
        }

        if ($settings['active'] && $settings['driver'] === 'zarinpal' && empty($settings['merchant_id'])) {
            throw ValidationException::withMessages(['merchant_id' => 'برای فعال‌سازی زرین‌پال، شناسه پذیرنده لازم است.']);
        }

        // Settings are stored as a JSON value; credentials are encrypted before persistence.
        SiteSetting::set(self::KEY, $settings, true);

        return redirect()->route('admin.payment-gateway.edit')
            ->with('status', match ($settings['driver']) {
                'zarinpal' => 'تنظیمات زرین‌پال ذخیره شد. پیش از دریافت وجه، شناسه پذیرنده و محیط مناسب را بررسی کنید.',
                'bitpay' => 'تنظیمات بیت‌پی ذخیره شد. پیش از فعال‌سازی پرداخت عمومی، یک تراکنش کنترل‌شده و تأیید سمت سرور را آزمایش کنید.',
                default => 'تنظیمات درگاه ذخیره شد؛ برای این درگاه آداپتور اختصاصی لازم است.',
            });
    }

    public function validateConfiguration()
    {
        $settings = SiteSetting::get(self::KEY, []);
        $settings = is_array($settings) ? $settings : [];
        $missing = [];

        if (empty($settings['provider_name'])) $missing[] = 'نام درگاه';
        if (empty($settings['driver'])) $missing[] = 'شناسه فنی درگاه';
        if (($settings['driver'] ?? '') === 'zarinpal' && empty($settings['merchant_id'])) $missing[] = 'شناسه پذیرنده زرین‌پال';
        if (in_array(($settings['driver'] ?? ''), ['bitpay', 'custom'], true) && empty($settings['api_key'])) $missing[] = 'کلید API';

        if (empty($settings['active'])) {
            return back()->with('warning', 'اتصال درگاه غیرفعال است؛ این بررسی فقط تنظیمات ذخیره‌شده را اعتبارسنجی می‌کند و تراکنش آزمایشی ارسال نمی‌کند.');
        }

        if ($missing !== []) {
            return back()->with('error', 'تنظیمات ناقص است: '.implode('، ', $missing).' .');
        }

        return back()->with('status', ($settings['driver'] ?? '') === 'zarinpal' ? 'تنظیمات پایه زرین‌پال کامل است؛ برای اطمینان از اتصال، یک پرداخت آزمایشی واقعی در محیط Sandbox انجام دهید.' : 'فیلدهای پایه کامل هستند؛ این بررسی به معنی تأیید اتصال زنده نیست.');
    }
}
