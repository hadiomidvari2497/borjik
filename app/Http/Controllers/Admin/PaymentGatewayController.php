<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\SubscriptionPayment;
use App\Services\Payments\PaymentGatewayRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class PaymentGatewayController extends Controller
{
    private const KEY = 'payment.gateways';
    private const LEGACY_KEY = 'payment.gateway';

    public function edit(Request $request, PaymentGatewayRegistry $registry)
    {
        $gateways = $registry->all();
        $id = (string) $request->query('gateway_id', '');
        if ($id === '' && ! $request->boolean('new') && $gateways !== []) {
            $id = (string) array_key_first($gateways);
        }
        $gateway = ($id !== '' && isset($gateways[$id]) && is_array($gateways[$id])) ? $gateways[$id] : [];
        $gatewayId = $id !== '' && isset($gateways[$id]) ? $id : null;

        return view('admin.payment-gateway.index', [
            'gateways' => $gateways,
            'gateway' => $gateway,
            'gatewayId' => $gatewayId,
            'hasApiKey' => ! empty($gateway['api_key']),
            'hasApiSecret' => ! empty($gateway['api_secret']),
            'hasWebhookSecret' => ! empty($gateway['webhook_secret']),
            'callbackUrl' => url('/payment/gateway/callback'),
        ]);
    }

    public function store(Request $request, PaymentGatewayRegistry $registry)
    {
        $id = $registry->newId();
        return $this->save($request, $id, $registry, true);
    }

    public function update(Request $request, PaymentGatewayRegistry $registry, ?string $gatewayId = null)
    {
        $id = $gatewayId ?: trim((string) $request->input('gateway_id', 'legacy'));
        if ($id === '') {
            $id = 'legacy';
        }
        return $this->save($request, $id, $registry, false);
    }

    private function save(Request $request, string $id, PaymentGatewayRegistry $registry, bool $creating)
    {
        $all = $registry->all();
        $old = $all[$id] ?? [];
        if (! is_array($old) && $id === 'legacy') {
            $old = SiteSetting::get(self::LEGACY_KEY, []);
        }
        $old = is_array($old) ? $old : [];

        $data = $request->validate([
            'provider_name' => ['required', 'string', 'max:100'],
            'driver' => ['required', 'in:bitpay,zarinpal,custom'],
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
            'driver.in' => 'درگاه انتخاب‌شده پشتیبانی نمی‌شود.',
            'environment.in' => 'محیط درگاه معتبر نیست.',
            'api_base_url.url' => 'آدرس API باید یک URL معتبر با http یا https باشد.',
        ]);

        $secret = function (string $field, string $clearField) use ($data, $old): ?string {
            if ((bool) ($data[$clearField] ?? false)) return null;
            $value = trim((string) ($data[$field] ?? ''));
            if ($value !== '') return Crypt::encryptString($value);
            return $old[$field] ?? null;
        };

        $settings = [
            'provider_name' => trim($data['provider_name']),
            'driver' => $data['driver'],
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

        if ($settings['active'] && $settings['driver'] === 'bitpay' && empty($settings['api_key'])) {
            throw ValidationException::withMessages(['api_key' => 'برای فعال‌سازی بیت‌پی، کد API را وارد کنید.']);
        }
        if ($settings['active'] && $settings['driver'] === 'zarinpal' && empty($settings['merchant_id'])) {
            throw ValidationException::withMessages(['merchant_id' => 'برای فعال‌سازی زرین‌پال، شناسه پذیرنده لازم است.']);
        }

        $all[$id] = $settings;
        SiteSetting::set(self::KEY, $all, true);
        // Keep the old setting populated for existing integrations and backward compatibility.
        if ($id === 'legacy' || count($all) === 1 || ! is_array(SiteSetting::get(self::LEGACY_KEY, []))) {
            SiteSetting::set(self::LEGACY_KEY, $settings, true);
        }

        return redirect()->route('admin.payment-gateway.edit', ['gateway_id' => $id])
            ->with('status', $creating ? 'درگاه پرداخت جدید ذخیره شد.' : 'تنظیمات این درگاه ذخیره شد.');
    }

    public function destroy(string $gatewayId, PaymentGatewayRegistry $registry)
    {
        $all = $registry->all();
        abort_unless(isset($all[$gatewayId]), 404);
        if (SubscriptionPayment::query()->where('status', 'pending')->where('raw_response->gateway_id', $gatewayId)->exists()) {
            return back()->with('error', 'این درگاه تراکنش در انتظار دارد؛ تا تعیین تکلیف تراکنش نمی‌توان آن را حذف کرد.');
        }
        unset($all[$gatewayId]);
        SiteSetting::set(self::KEY, $all, true);
        if ($gatewayId === 'legacy' || count($all) === 0) {
            SiteSetting::set(self::LEGACY_KEY, [], true);
        } elseif (isset($all['legacy'])) {
            SiteSetting::set(self::LEGACY_KEY, $all['legacy'], true);
        } else {
            $first = reset($all);
            SiteSetting::set(self::LEGACY_KEY, is_array($first) ? $first : [], true);
        }

        return redirect()->route('admin.payment-gateway.edit')->with('status', 'درگاه از فهرست تنظیمات حذف شد. تراکنش‌های ثبت‌شده قبلی دست‌نخورده باقی ماندند.');
    }

    public function validateConfiguration(PaymentGatewayRegistry $registry)
    {
        $active = array_filter($registry->all(), static fn ($item) => is_array($item) && ! empty($item['active']));
        if ($active === []) {
            return back()->with('warning', 'هیچ درگاه فعال و کامل پیکربندی نشده است.');
        }

        return back()->with('status', count($active).' درگاه فعال و دارای تنظیمات پایه است؛ این بررسی جایگزین تراکنش واقعی آزمایشی نیست.');
    }
}
