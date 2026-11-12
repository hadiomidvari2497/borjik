<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Support\CurrentBuilding;
use App\Models\CouponRedemption;
use App\Models\DiscountCoupon;
use App\Models\SiteSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\BuildingService;
use App\Services\Payments\BitpayGateway;
use App\Services\Payments\PaymentGatewayRegistry;
use App\Services\Payments\PaystarGateway;
use App\Services\Payments\ZarinpalGateway;
use App\Support\SystemErrorReporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function show(Request $request, BuildingService $buildings)
    {
        $user = $request->user();
        $buildingId = app(CurrentBuilding::class)->id();
        $building = $buildingId ? Building::query()->whereHas('members', fn ($query) => $query
            ->where('user_id', $user->id)->where('status', 'active')
            ->whereHas('role', fn ($role) => $role->whereIn('key', ['owner', 'manager'])))->findOrFail($buildingId) : null;

        if (! $building) {
            return redirect()->route('buildings.create')->with('warning', 'ابتدا ساختمان موردنظر را ایجاد یا انتخاب کنید.');
        }

        if ($request->filled('checkout')) {
            $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail((int) $request->query('checkout'));
            $gateways = app(PaymentGatewayRegistry::class)->active();
            return view('subscription.checkout', [
                'plan' => $plan,
                'paymentGateways' => $gateways,
                'gatewayReady' => $gateways !== [],
                'user' => $user,
                'building' => $building,
            ]);
        }

        $current = Subscription::query()->where('building_id', $building->id)
            ->where('status', 'active')->where('expires_at', '>', now())
            ->with('plan')->latest('id')->first();
        if ($current) {
            $current->loadMissing('plan');
        }

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->orderBy('units_limit')
            ->orderBy('duration_months')
            ->get();

        $gateways = app(PaymentGatewayRegistry::class)->active();
        $gatewayReady = $gateways !== [];

        return view('subscription.show', [
            'subscription' => $current,
            'plans' => $plans,
            'gatewayReady' => $gatewayReady,
            'paymentGateways' => $gateways,
            'building' => $building,
            'recentPayments' => SubscriptionPayment::query()->with('subscription.plan')
                ->whereHas('subscription', fn ($query) => $query->where('building_id', $building->id))
                ->latest('id')->limit(10)->get(),
        ]);
    }

    /** بررسی زنده کد تخفیف پیش از شروع پرداخت؛ مبلغ نهایی فقط برای نمایش برگردانده می‌شود. */
    public function previewCoupon(Request $request, int $id)
    {
        $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail($id);
        $user = $request->user();
        $buildingId = app(CurrentBuilding::class)->id();
        $building = $buildingId ? Building::query()->whereHas('members', fn ($query) => $query
            ->where('user_id', $user->id)->where('status', 'active')
            ->whereHas('role', fn ($role) => $role->whereIn('key', ['owner', 'manager'])))->findOrFail($buildingId) : null;

        if (! $building) {
            return response()->json(['message' => 'ابتدا ساختمان موردنظر را ایجاد یا انتخاب کنید.'], 422);
        }

        $code = strtoupper(trim((string) $request->input('coupon_code', '')));
        $baseAmount = (int) $plan->effectivePrice();

        if ($code === '') {
            return response()->json(['message' => 'کد تخفیف را وارد کنید.'], 422);
        }
        if ($baseAmount <= 0) {
            return response()->json(['message' => 'برای این پلن مبلغی جهت اعمال تخفیف وجود ندارد.'], 422);
        }

        $coupon = DiscountCoupon::query()->where('code', $code)->first();
        if (! $coupon || ! $coupon->isAvailableFor($user->id, $plan->id, $baseAmount)) {
            return response()->json(['message' => 'کد تخفیف نامعتبر، منقضی یا غیرقابل استفاده برای این پلن است.'], 422);
        }

        $discountAmount = $coupon->discountFor($baseAmount);
        if ($discountAmount <= 0) {
            return response()->json(['message' => 'این کد تخفیف برای مبلغ این سفارش تخفیفی ایجاد نمی‌کند.'], 422);
        }

        return response()->json([
            'valid' => true,
            'coupon_code' => $coupon->code,
            'base_amount' => $baseAmount,
            'discount_amount' => $discountAmount,
            'payable_amount' => max(0, $baseAmount - $discountAmount),
            'message' => 'کد تخفیف با موفقیت اعمال شد.',
        ]);
    }

    /** پلن رایگان مستقیماً فعال می‌شود؛ پلن پولی فقط با شروع تراکنش واقعی. */
    public function subscribe(Request $request, int $id, ZarinpalGateway $zarinpal, BitpayGateway $bitpay, PaystarGateway $paystar, PaymentGatewayRegistry $registry)
    {
        $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail($id);
        $user = $request->user();
        $buildingId = app(CurrentBuilding::class)->id();
        $building = $buildingId ? Building::query()->whereHas('members', fn ($query) => $query
            ->where('user_id', $user->id)->where('status', 'active')
            ->whereHas('role', fn ($role) => $role->whereIn('key', ['owner', 'manager'])))->findOrFail($buildingId) : null;
        if (! $building) {
            return redirect()->route('buildings.create')->with('warning', 'ابتدا ساختمان موردنظر را ایجاد یا انتخاب کنید.');
        }

        $baseAmount = $plan->effectivePrice();
        $couponCode = strtoupper(trim((string) $request->input('coupon_code', '')));
        $discountAmount = 0;
        $amount = $baseAmount;

        if ($couponCode !== '' && $baseAmount <= 0) {
            return back()->withInput()->withErrors(['coupon_code' => 'کد تخفیف فقط برای پلن‌هایی که مبلغ قابل پرداخت دارند قابل استفاده است.']);
        }

        if ($couponCode !== '') {
            $coupon = DiscountCoupon::query()->where('code', $couponCode)->first();
            if (! $coupon || ! $coupon->isAvailableFor($user->id, $plan->id, $baseAmount)) {
                return back()->withInput()->withErrors(['coupon_code' => 'کد تخفیف نامعتبر، منقضی یا غیرقابل استفاده برای این پلن است.']);
            }
            $discountAmount = $coupon->discountFor($baseAmount);
            $amount = max(0, $baseAmount - $discountAmount);
        }

        if ($amount === 0) {
            try {
                DB::transaction(function () use ($user, $building, $plan, $couponCode, $baseAmount) {
                    $lockedCoupon = null;
                    $discount = 0;
                    if ($couponCode !== '') {
                        $lockedCoupon = DiscountCoupon::query()->where('code', $couponCode)->lockForUpdate()->first();
                        if (! $lockedCoupon || ! $lockedCoupon->isAvailableFor($user->id, $plan->id, $baseAmount)) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['coupon_code' => 'این کد تخفیف دیگر قابل استفاده نیست.']);
                        }
                        $discount = $lockedCoupon->discountFor($baseAmount);
                        if ($discount < $baseAmount) {
                            throw new \RuntimeException('Discount total changed during checkout.');
                        }
                    }

                    Subscription::where('building_id', $building->id)->where('status', 'active')->update(['status' => 'expired']);
                    $subscription = Subscription::create([
                        'user_id' => $user->id,
                        'building_id' => $building->id,
                        'plan_id' => $plan->id,
                        'units_limit' => $plan->units_limit,
                        'starts_at' => now(),
                        'expires_at' => now()->addMonths(max(1, (int) $plan->duration_months)),
                        'status' => 'active',
                    ]);
                    $building->update(['subscription_id' => $subscription->id]);
                    if ($lockedCoupon) {
                        CouponRedemption::create([
                            'coupon_id' => $lockedCoupon->id,
                            'user_id' => $user->id,
                            'discount_amount' => $discount,
                            'status' => 'paid',
                        ]);
                    }
                });
            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->withInput()->withErrors($e->errors());
            }

            return redirect()->route('dashboard')
                ->with('status', 'اشتراک «'.$plan->name.'» برای ساختمان «'.$building->name.'» با موفقیت فعال شد.');
        }

        $availableGateways = $registry->active();
        $gatewayId = trim((string) $request->input('gateway_id', ''));
        if ($gatewayId === '' && count($availableGateways) === 1) {
            $gatewayId = (string) array_key_first($availableGateways);
        }
        $settings = $availableGateways[$gatewayId] ?? null;
        $driver = (string) data_get($settings, 'driver', '');
        if (! is_array($settings) || ! in_array($driver, ['zarinpal', 'bitpay', 'paystar'], true)) {
            SystemErrorReporter::warning(
                $request,
                'payment.gateway',
                'کاربر برای خرید اشتراک اقدام کرد اما درگاه پرداخت فعال یا پیکربندی نشده بود.',
                "خرید اشتراک متوقف شد پیش از ایجاد تراکنش. تنظیم فعال بودن درگاه و انتخاب درایور زرین‌پال بررسی شود.",
                ['plan_id' => $plan->id, 'plan_price' => (int) $plan->price, 'gateway_active' => (bool) data_get($settings, 'active', false), 'gateway_driver' => (string) data_get($settings, 'driver', '')],
                422,
            );

            return back()->with('error', 'امکان پرداخت آنلاین در حال حاضر فراهم نیست. لطفاً کمی بعد دوباره تلاش کنید.');
        }

        try {
            $payment = DB::transaction(function () use ($user, $building, $plan, $couponCode, $baseAmount, $discountAmount, $amount, $driver, $gatewayId, $settings) {
                $lockedCoupon = null;
                if ($couponCode !== '') {
                    $lockedCoupon = DiscountCoupon::query()->where('code', $couponCode)->lockForUpdate()->first();
                    if (! $lockedCoupon || ! $lockedCoupon->isAvailableFor($user->id, $plan->id, $baseAmount)
                        || $lockedCoupon->discountFor($baseAmount) !== $discountAmount) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['coupon_code' => 'این کد تخفیف دیگر قابل استفاده نیست. دوباره تلاش کنید.']);
                    }
                }

                $pending = Subscription::create([
                    'user_id' => $user->id,
                    'building_id' => $building->id,
                    'plan_id' => $plan->id,
                    'units_limit' => $plan->units_limit,
                    'starts_at' => now(),
                    'expires_at' => now()->addMonths(max(1, (int) $plan->duration_months)),
                    'status' => 'pending',
                ]);

                $payment = SubscriptionPayment::create([
                    'subscription_id' => $pending->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'gateway' => $driver,
                    'status' => 'pending',
                    'raw_response' => [
                        'plan_id' => $plan->id,
                        'amount_rial' => $amount * 10,
                        'gateway_id' => $gatewayId,
                        'gateway_name' => (string) ($settings['provider_name'] ?? $driver),
                        'list_price' => (int) $plan->price,
                        'plan_discount_amount' => max(0, (int) $plan->price - $baseAmount),
                        'plan_discount_type' => $plan->discount_type,
                        'plan_discount_value' => (int) $plan->discount_value,
                        'base_amount' => $baseAmount,
                        'discount_amount' => $discountAmount,
                        'coupon_discount_amount' => $discountAmount,
                        'coupon_code' => $couponCode !== '' ? $couponCode : null,
                        'coupon_id' => $lockedCoupon?->id,
                    ],
                ]);

                if ($lockedCoupon) {
                    CouponRedemption::create([
                        'coupon_id' => $lockedCoupon->id,
                        'user_id' => $user->id,
                        'payment_id' => $payment->id,
                        'discount_amount' => $discountAmount,
                        'status' => 'reserved',
                    ]);
                }

                return $payment;
            });

            $requestSettings = $settings;
            $requestSettings['_gateway_id'] = $gatewayId;
            $result = match ($driver) {
                'bitpay' => $bitpay->request($amount * 10, $payment->id, $user, $requestSettings),
                'paystar' => $paystar->request($amount * 10, 'sub-'.$payment->id, $user, $requestSettings),
                default => $zarinpal->request($amount * 10, $payment->id, $user, $requestSettings),
            };
            $gatewayResponse = match ($driver) {
                'bitpay' => ['id_get' => $result['id_get'], 'request' => $result['response']],
                'paystar' => ['paystar_order_id' => $result['order_id'], 'ref_num' => $result['ref_num'], 'paystar_token' => $result['token'], 'request' => $result['response']],
                default => ['authority' => $result['authority'], 'request' => $result['response']],
            };
            $payment->update(['raw_response' => array_merge($payment->raw_response ?? [], $gatewayResponse)]);

            return redirect()->away($result['payment_url']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            if (isset($payment)) {
                DB::transaction(function () use ($payment, $e) {
                    $payment->update([
                        'status' => 'failed',
                        'raw_response' => array_merge($payment->raw_response ?? [], ['request_error' => mb_substr($e->getMessage(), 0, 500)]),
                    ]);
                    if ($payment->subscription_id) {
                        Subscription::whereKey($payment->subscription_id)->where('status', 'pending')->update(['status' => 'failed']);
                    }
                    CouponRedemption::where('payment_id', $payment->id)->where('status', 'reserved')->update(['status' => 'released']);
                });
            }
            SystemErrorReporter::caught(
                $request,
                $e,
                'payment.gateway',
                'ایجاد تراکنش خرید اشتراک ناموفق بود.',
                ['user_id' => $user->id, 'plan_id' => $plan->id, 'payment_id' => isset($payment) ? $payment->id : null],
            );
            Log::warning('Subscription payment request failed', ['user_id' => $user->id, 'plan_id' => $plan->id, 'exception' => $e::class]);

            return back()->with('error', 'ایجاد تراکنش پرداخت ناموفق بود. تنظیمات درگاه را بررسی کنید و دوباره تلاش کنید.');
        }
    }

    /** Callback عمومی است؛ فعال‌سازی فقط پس از تأیید سمت سرور با API زرین‌پال انجام می‌شود. */
    public function callback(Request $request, ZarinpalGateway $zarinpal, BitpayGateway $bitpay, PaystarGateway $paystar, PaymentGatewayRegistry $registry)
    {
        $authority = (string) $request->input('Authority', '');
        $status = strtoupper((string) $request->input('Status', ''));
        $idGet = trim((string) $request->input('id_get', ''));
        $transId = trim((string) $request->input('trans_id', ''));
        $refNum = trim((string) $request->input('ref_num', ''));
        $paystarOrderId = trim((string) $request->input('order_id', ''));
        $cardNumber = trim((string) $request->input('card_number', ''));
        $trackingCode = trim((string) $request->input('tracking_code', ''));
        $callbackGatewayId = trim((string) $request->input('gateway_id', ''));
        $isPaystar = $refNum !== '' || $paystarOrderId !== '';
        $isBitpay = ! $isPaystar && $idGet !== '' && $transId !== '';

        $payment = $isPaystar
            ? SubscriptionPayment::query()->where('gateway', 'paystar')->where('status', 'pending')
                ->where(function ($query) use ($refNum, $paystarOrderId) {
                    if ($refNum !== '') $query->where('raw_response->ref_num', $refNum);
                    if ($paystarOrderId !== '') $query->orWhere('raw_response->paystar_order_id', $paystarOrderId);
                })
                ->when($callbackGatewayId !== '', fn ($query) => $query->where('raw_response->gateway_id', $callbackGatewayId))->first()
            : ($isBitpay
                ? SubscriptionPayment::query()->where('gateway', 'bitpay')->where('status', 'pending')->where('raw_response->id_get', $idGet)
                    ->when($callbackGatewayId !== '', fn ($query) => $query->where('raw_response->gateway_id', $callbackGatewayId))->first()
                : SubscriptionPayment::query()->where('gateway', 'zarinpal')->where('status', 'pending')->where('raw_response->authority', $authority)
                    ->when($callbackGatewayId !== '', fn ($query) => $query->where('raw_response->gateway_id', $callbackGatewayId))->first());

        if (! $payment || ($isPaystar ? ($paystarOrderId === '' && $refNum === '') : ($isBitpay ? ($idGet === '' || $transId === '') : $authority === ''))) {
            return redirect()->route('subscription.show')->with('error', 'تراکنش معتبر یا در انتظار پرداختی برای این بازگشت پیدا نشد.');
        }

        if (! $isBitpay && ! $isPaystar && $status !== 'OK') {
            DB::transaction(function () use ($payment, $request) {
                $locked = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'pending') {
                    $locked->update(['status' => 'failed', 'raw_response' => array_merge($locked->raw_response ?? [], ['callback_status' => (string) $request->query('Status', '')])]);
                    Subscription::whereKey($locked->subscription_id)->where('status', 'pending')->update(['status' => 'failed']);
                    CouponRedemption::where('payment_id', $locked->id)->where('status', 'reserved')->update(['status' => 'released']);
                }
            });

            return redirect()->route('subscription.show')->with('error', 'پرداخت انجام نشد؛ اشتراک فعال نشده است.');
        }

        if ($isPaystar && ($refNum === '' || $cardNumber === '' || $trackingCode === '')) {
            DB::transaction(function () use ($payment, $request) {
                $locked = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'pending') {
                    $locked->update(['status' => 'failed', 'raw_response' => array_merge($locked->raw_response ?? [], ['paystar_callback' => $request->only(['status', 'order_id', 'ref_num'])])]);
                    Subscription::whereKey($locked->subscription_id)->where('status', 'pending')->update(['status' => 'failed']);
                    CouponRedemption::where('payment_id', $locked->id)->where('status', 'reserved')->update(['status' => 'released']);
                }
            });
            return redirect()->route('subscription.show')->with('error', 'پرداخت پی‌استار تکمیل نشد؛ اشتراک فعال نشده است.');
        }

        try {
            $gatewayId = (string) data_get($payment->raw_response, 'gateway_id', 'legacy');
            $gatewaySettings = $registry->find($gatewayId);
            if (! is_array($gatewaySettings) && $gatewayId === 'legacy') {
                $gatewaySettings = SiteSetting::get('payment.gateway', []);
            }
            if (! is_array($gatewaySettings)) {
                throw new \RuntimeException('Payment gateway configuration is no longer available.');
            }
            $amountRial = (int) data_get($payment->raw_response, 'amount_rial', 0);
            if ($amountRial <= 0) {
                throw new \RuntimeException('Invalid stored payment amount.');
            }

            $verification = $isPaystar
                ? $paystar->verify($refNum, $amountRial, $cardNumber, $trackingCode, $gatewaySettings)
                : ($isBitpay
                    ? $bitpay->verify($idGet, $transId, $gatewaySettings)
                    : $zarinpal->verify($authority, $amountRial, $gatewaySettings));
            $verified = $isPaystar
                ? (bool) data_get($verification, 'verified', false)
                : ($isBitpay
                    ? (int) data_get($verification, 'status', 0) === 1
                        && (int) data_get($verification, 'amount', 0) === $amountRial
                        && (string) data_get($verification, 'factorId', '') === (string) $payment->id
                    : in_array((int) data_get($verification, 'data.code', 0), [100, 101], true));
            if (! $verified) {
                DB::transaction(function () use ($payment, $verification) {
                    $locked = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                    if ($locked->status === 'pending') {
                        $locked->update(['status' => 'failed', 'raw_response' => array_merge($locked->raw_response ?? [], ['verification' => $verification])]);
                        Subscription::whereKey($locked->subscription_id)->where('status', 'pending')->update(['status' => 'failed']);
                        CouponRedemption::where('payment_id', $locked->id)->where('status', 'reserved')->update(['status' => 'released']);
                    }
                });

                return redirect()->route('subscription.show')->with('error', 'درگاه پرداخت را تأیید نکرد؛ اشتراک فعال نشده است.');
            }

            DB::transaction(function () use ($payment, $verification, $authority, $isBitpay, $isPaystar, $transId, $refNum) {
                $locked = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === 'paid') {
                    return;
                }
                if ($locked->status !== 'pending') {
                    throw new \RuntimeException('Payment is no longer pending.');
                }

                $subscription = Subscription::whereKey($locked->subscription_id)->lockForUpdate()->firstOrFail();
                if ($subscription->status !== 'pending') {
                    throw new \RuntimeException('Pending subscription not found.');
                }

                Subscription::where('building_id', $subscription->building_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $subscription->id)
                    ->update(['status' => 'expired']);

                $subscription->update(['status' => 'active', 'starts_at' => now()]);
                if ($subscription->building_id) {
                    Building::whereKey($subscription->building_id)->update(['subscription_id' => $subscription->id]);
                }

                CouponRedemption::where('payment_id', $locked->id)->where('status', 'reserved')->update(['status' => 'paid']);

                $locked->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'ref_id' => $isPaystar ? $refNum : ($isBitpay ? $transId : (string) data_get($verification, 'data.ref_id', $authority)),
                    'raw_response' => array_merge($locked->raw_response ?? [], ['verification' => $verification, 'paystar_callback' => $isPaystar ? $request->only(['order_id', 'ref_num', 'tracking_code']) : null]),
                ]);
            });

            return redirect()->route('subscription.payment.receipt', ['payment' => $payment->id])->with('status', 'پرداخت با موفقیت تأیید شد و اشتراک شما فعال شد.');
        } catch (\Throwable $e) {
            SystemErrorReporter::caught(
                $request,
                $e,
                'payment.callback',
                'تأیید تراکنش خرید اشتراک ناموفق بود.',
                ['payment_id' => $payment->id, 'subscription_id' => $payment->subscription_id],
            );
            Log::warning('Subscription payment verification failed', ['payment_id' => $payment->id, 'exception' => $e::class]);

            return redirect()->route('subscription.show')->with('error', 'تأیید پرداخت از سمت درگاه ممکن نشد؛ اشتراک تا زمان تأیید معتبر فعال نمی‌شود.');
        }
    }

    private function activateFreePlan(int $userId, int $buildingId, SubscriptionPlan $plan): void
    {
        DB::transaction(function () use ($userId, $buildingId, $plan) {
            Subscription::where('building_id', $buildingId)->where('status', 'active')->update(['status' => 'expired']);
            $subscription = Subscription::create([
                'user_id' => $userId,
                'building_id' => $buildingId,
                'plan_id' => $plan->id,
                'units_limit' => $plan->units_limit,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(max(1, (int) $plan->duration_months)),
                'status' => 'active',
            ]);

            Building::whereKey($buildingId)->update(['subscription_id' => $subscription->id]);
        });
    }
}
