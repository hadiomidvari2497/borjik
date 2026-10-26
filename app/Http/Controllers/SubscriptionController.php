<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\CouponRedemption;
use App\Models\DiscountCoupon;
use App\Models\SiteSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\BuildingService;
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
        $current = $buildings->activeSubscription($user);
        if ($current) {
            $current->loadMissing('plan');
        }

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->orderBy('units_limit')
            ->orderBy('duration_months')
            ->get();

        $gateway = SiteSetting::get('payment.gateway', []);
        $gatewayReady = is_array($gateway)
            && ! empty($gateway['active'])
            && ($gateway['driver'] ?? '') === 'zarinpal'
            && ! empty($gateway['merchant_id']);

        return view('subscription.show', [
            'subscription' => $current,
            'plans' => $plans,
            'gatewayReady' => $gatewayReady,
        ]);
    }

    /** پلن رایگان مستقیماً فعال می‌شود؛ پلن پولی فقط با شروع تراکنش واقعی. */
    public function subscribe(Request $request, int $id, ZarinpalGateway $gateway)
    {
        $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail($id);
        $user = $request->user();

        if ((int) $plan->price === 0) {
            $this->activateFreePlan($user->id, $plan);

            return redirect()->route('buildings.create')
                ->with('status', 'اشتراک رایگان «'.$plan->name.'» فعال شد. حالا می‌توانید ساختمان بسازید.');
        }

        $settings = SiteSetting::get('payment.gateway', []);
        if (! is_array($settings) || empty($settings['active']) || ($settings['driver'] ?? '') !== 'zarinpal') {
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
            $payment = DB::transaction(function () use ($user, $plan, $couponCode, $baseAmount, $discountAmount, $amount) {
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
                    'gateway' => 'zarinpal',
                    'status' => 'pending',
                    'raw_response' => [
                        'plan_id' => $plan->id,
                        'amount_rial' => $amount * 10,
                        'base_amount' => $baseAmount,
                        'discount_amount' => $discountAmount,
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

            $result = $gateway->request($amount * 10, $payment->id, $user);
            $payment->update([
                'raw_response' => array_merge($payment->raw_response ?? [], [
                    'authority' => $result['authority'],
                    'request' => $result['response'],
                ]),
            ]);

            return redirect()->away($result['payment_url']);
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
    public function callback(Request $request, ZarinpalGateway $gateway)
    {
        $authority = (string) $request->query('Authority', '');
        $status = strtoupper((string) $request->query('Status', ''));

        $payment = SubscriptionPayment::query()
            ->where('gateway', 'zarinpal')
            ->where('status', 'pending')
            ->where('raw_response->authority', $authority)
            ->first();

        if (! $payment || $authority === '') {
            return redirect()->route('subscription.show')->with('error', 'تراکنش معتبر یا در انتظار پرداختی برای این بازگشت پیدا نشد.');
        }

        if ($status !== 'OK') {
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

        try {
            $amountRial = (int) data_get($payment->raw_response, 'amount_rial', 0);
            if ($amountRial <= 0) {
                throw new \RuntimeException('Invalid stored payment amount.');
            }

            $verification = $gateway->verify($authority, $amountRial);
            $code = (int) data_get($verification, 'data.code', 0);
            if (! in_array($code, [100, 101], true)) {
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

            DB::transaction(function () use ($payment, $verification, $authority) {
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

                Subscription::where('user_id', $locked->user_id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired']);

                $subscription->update(['status' => 'active', 'starts_at' => now()]);
                $previousIds = Subscription::where('user_id', $locked->user_id)
                    ->where('id', '!=', $subscription->id)
                    ->pluck('id');
                if ($previousIds->isNotEmpty()) {
                    Building::whereIn('subscription_id', $previousIds)->get()
                        ->each(fn (Building $building) => $building->update(['subscription_id' => $subscription->id]));
                }

                CouponRedemption::where('payment_id', $locked->id)->where('status', 'reserved')->update(['status' => 'paid']);

                $locked->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'ref_id' => (string) data_get($verification, 'data.ref_id', $authority),
                    'raw_response' => array_merge($locked->raw_response ?? [], ['verification' => $verification]),
                ]);
            });

            return redirect()->route('subscription.show')->with('status', 'پرداخت با موفقیت تأیید شد و اشتراک شما فعال شد.');
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

    private function activateFreePlan(int $userId, SubscriptionPlan $plan): void
    {
        DB::transaction(function () use ($userId, $plan) {
            Subscription::where('user_id', $userId)->where('status', 'active')->update(['status' => 'expired']);
            $subscription = Subscription::create([
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'units_limit' => $plan->units_limit,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(max(1, (int) $plan->duration_months)),
                'status' => 'active',
            ]);

            $previousIds = Subscription::where('user_id', $userId)->where('id', '!=', $subscription->id)->pluck('id');
            if ($previousIds->isNotEmpty()) {
                Building::whereIn('subscription_id', $previousIds)->get()
                    ->each(fn (Building $building) => $building->update(['subscription_id' => $subscription->id]));
            }
        });
    }
}
