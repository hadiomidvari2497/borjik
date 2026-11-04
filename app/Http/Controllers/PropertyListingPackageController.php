<?php

namespace App\\Http\\Controllers;

use App\\Models\\PropertyListingPackageOrder;
use App\\Models\\PropertyListingCreditTransaction;
use App\\Models\\SiteSetting;
use App\\Models\\User;
use App\\Services\\Payments\\BitpayGateway;
use App\\Services\\Payments\\PaymentGatewayRegistry;
use App\\Services\\Payments\\ZarinpalGateway;
use App\\Services\\PropertyListingQuota;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;
use RuntimeException;

class PropertyListingPackageController extends Controller
{
    public function buy(Request $request, PaymentGatewayRegistry $registry, ZarinpalGateway $zarinpal, BitpayGateway $bitpay)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'gateway_id' => ['nullable', 'string', 'max:100'],
        ]);

        $packages = SiteSetting::get('property_listings.packages', []);
        $package = collect(is_array($packages) ? $packages : [])->first(fn ($item) =>
            is_array($item) && ! empty($item['active']) && (int) ($item['quantity'] ?? 0) === (int) $data['quantity']
        );

        if (! $package || (int) ($package['price'] ?? 0) <= 0) {
            return back()->withErrors(['quantity' => 'این بسته در حال حاضر قابل خرید نیست.']);
        }

        $gateways = $registry->active();
        $gatewayId = trim((string) ($data['gateway_id'] ?? ''));
        if ($gatewayId === '' && count($gateways) === 1) {
            $gatewayId = (string) array_key_first($gateways);
        }
        $settings = $gateways[$gatewayId] ?? null;
        $driver = (string) data_get($settings, 'driver', '');

        if (! is_array($settings) || ! in_array($driver, ['zarinpal', 'bitpay'], true)) {
            return back()->with('error', 'درگاه پرداخت فعال و معتبری برای خرید بسته وجود ندارد.');
        }

        $order = PropertyListingPackageOrder::create([
            'user_id' => $request->user()->id,
            'quantity' => (int) $package['quantity'],
            'amount' => (int) $package['price'],
            'gateway' => $driver,
            'gateway_id' => $gatewayId,
            'status' => 'pending',
            'raw_response' => ['gateway_name' => (string) ($settings['provider_name'] ?? $driver)],
        ]);

        try {
            $requestSettings = $settings;
            $requestSettings['_gateway_id'] = $gatewayId;
            $callbackUrl = route('properties.packages.callback', ['gateway_id' => $gatewayId]);
            $result = $driver === 'bitpay'
                ? $bitpay->request($order->amount * 10, $order->id, $request->user(), $requestSettings, $callbackUrl)
                : $zarinpal->request($order->amount * 10, $order->id, $request->user(), $requestSettings, $callbackUrl);

            $order->update([
                'authority' => $driver === 'zarinpal' ? $result['authority'] : null,
                'id_get' => $driver === 'bitpay' ? $result['id_get'] : null,
                'raw_response' => array_merge($order->raw_response ?? [], [
                    'request' => $result['response'],
                    'amount_rial' => $order->amount * 10,
                ]),
            ]);

            return redirect()->away($result['payment_url']);
        } catch (\\Throwable $e) {
            $order->update([
                'status' => 'failed',
                'raw_response' => array_merge($order->raw_response ?? [], ['request_error' => mb_substr($e->getMessage(), 0, 500)]),
            ]);
            Log::warning('Property listing package payment request failed', [
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'exception' => $e::class,
            ]);

            return back()->with('error', 'شروع پرداخت بسته آگهی ناموفق بود. لطفاً تنظیمات درگاه را بررسی و دوباره تلاش کنید.');
        }
    }

    /** Callback is public; credits are granted only after server-side gateway verification. */
    public function callback(Request $request, PaymentGatewayRegistry $registry, ZarinpalGateway $zarinpal, BitpayGateway $bitpay, PropertyListingQuota $quota)
    {
        $authority = (string) $request->query('Authority', '');
        $status = strtoupper((string) $request->query('Status', ''));
        $idGet = trim((string) $request->query('id_get', ''));
        $transId = trim((string) $request->query('trans_id', ''));
        $gatewayId = trim((string) $request->query('gateway_id', ''));
        $isBitpay = $idGet !== '' && $transId !== '';

        $order = $isBitpay
            ? PropertyListingPackageOrder::query()->where('gateway', 'bitpay')->where('status', 'pending')->where('id_get', $idGet)->first()
            : PropertyListingPackageOrder::query()->where('gateway', 'zarinpal')->where('status', 'pending')->where('authority', $authority)
                ->when($gatewayId !== '', fn ($query) => $query->where('gateway_id', $gatewayId))->first();

        if (! $order || ($isBitpay ? ($idGet === '' || $transId === '') : $authority === '')) {
            return redirect()->route('properties.mine')->with('error', 'تراکنش معتبری برای خرید بسته آگهی پیدا نشد.');
        }

        if (! $isBitpay && $status !== 'OK') {
            $this->failPendingOrder($order->id, ['callback_status' => $status]);
            return redirect()->route('properties.mine')->with('error', 'پرداخت انجام نشد و اعتباری به حساب شما اضافه نشد.');
        }

        try {
            $settings = $registry->find($order->gateway_id);
            if (! is_array($settings)) {
                throw new RuntimeException('The configured payment gateway is no longer available.');
            }

            $amountRial = (int) data_get($order->raw_response, 'amount_rial', 0);
            if ($amountRial !== ((int) $order->amount * 10) || $amountRial <= 0) {
                throw new RuntimeException('Stored package payment amount is invalid.');
            }

            $verification = $isBitpay
                ? $bitpay->verify($idGet, $transId, $settings)
                : $zarinpal->verify($authority, $amountRial, $settings);

            $verified = $isBitpay
                ? (int) data_get($verification, 'status', 0) === 1
                    && (int) data_get($verification, 'amount', 0) === $amountRial
                    && (string) data_get($verification, 'factorId', '') === (string) $order->id
                : in_array((int) data_get($verification, 'data.code', 0), [100, 101], true);

            if (! $verified) {
                $this->failPendingOrder($order->id, ['verification' => $verification]);
                return redirect()->route('properties.mine')->with('error', 'درگاه پرداخت را تأیید نکرد؛ هیچ اعتباری اضافه نشد.');
            }

            DB::transaction(function () use ($order, $verification, $authority, $transId, $isBitpay, $quota) {
                $locked = PropertyListingPackageOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'paid') {
                    return;
                }
                if ($locked->status !== 'pending') {
                    throw new RuntimeException('Package order is no longer pending.');
                }

                $locked->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'ref_id' => $isBitpay ? $transId : (string) data_get($verification, 'data.ref_id', $authority),
                    'raw_response' => array_merge($locked->raw_response ?? [], ['verification' => $verification]),
                ]);

                $quota->grantPaidOrder($locked->id);
            });

            return redirect()->route('properties.mine')->with('status', 'پرداخت تأیید شد؛ '.$order->quantity.' اعتبار آگهی به حساب شما اضافه شد.');
        } catch (\\Throwable $e) {
            Log::warning('Property listing package payment verification failed', [
                'order_id' => $order->id,
                'exception' => $e::class,
            ]);

            return redirect()->route('properties.mine')->with('error', 'تأیید پرداخت از سمت درگاه ممکن نشد؛ تا زمان تأیید، اعتباری به حساب شما اضافه نمی‌شود.');
        }
    }

    private function failPendingOrder(int $orderId, array $details): void
    {
        DB::transaction(function () use ($orderId, $details) {
            $order = PropertyListingPackageOrder::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->status === 'pending') {
                $order->update([
                    'status' => 'failed',
                    'raw_response' => array_merge($order->raw_response ?? [], $details),
                ]);
            }
        });
    }
}
