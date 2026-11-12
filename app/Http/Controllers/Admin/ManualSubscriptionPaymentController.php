<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\CouponRedemption;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManualSubscriptionPaymentController extends Controller
{
    public function downloadProof(int $payment)
    {
        $record = SubscriptionPayment::query()->findOrFail($payment);
        abort_unless($record->gateway === 'card_to_card', 404);
        $path = (string) data_get($record->raw_response, 'receipt_path', '');
        abort_unless(str_starts_with($path, 'private/payment-proofs/') && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }

    public function approve(int $payment)
    {
        DB::transaction(function () use ($payment) {
            $record = SubscriptionPayment::query()->whereKey($payment)->lockForUpdate()->firstOrFail();
            abort_unless($record->gateway === 'card_to_card', 404);
            if ($record->status !== 'pending') {
                abort(422, 'این پرداخت قبلاً تعیین تکلیف شده است.');
            }

            $path = (string) data_get($record->raw_response, 'receipt_path', '');
            abort_unless(str_starts_with($path, 'private/payment-proofs/') && Storage::disk('local')->exists($path), 422, 'فایل رسید پرداخت پیدا نشد.');

            $subscription = Subscription::query()->whereKey($record->subscription_id)->lockForUpdate()->firstOrFail();
            if ($subscription->status !== 'pending') {
                abort(422, 'اشتراک در وضعیت انتظار تأیید نیست.');
            }

            Subscription::query()->where('building_id', $subscription->building_id)
                ->where('status', 'active')->where('id', '!=', $subscription->id)
                ->update(['status' => 'expired']);

            $duration = max(1, (int) ($subscription->plan?->duration_months ?? 1));
            $subscription->update(['status' => 'active', 'starts_at' => now(), 'expires_at' => now()->addMonths($duration)]);
            app(\App\Services\SubscriptionUnitAccess::class)->initializeForPlan($subscription);
            if ($subscription->building_id) {
                Building::query()->whereKey($subscription->building_id)->update(['subscription_id' => $subscription->id]);
            }

            CouponRedemption::query()->where('payment_id', $record->id)->where('status', 'reserved')->update(['status' => 'paid']);
            $record->update([
                'status' => 'paid',
                'paid_at' => now(),
                'ref_id' => trim((string) data_get($record->raw_response, 'transfer_tracking_code', '')) ?: ('MAN-'.$record->id),
                'raw_response' => array_merge($record->raw_response ?? [], [
                    'admin_approved_at' => now()->toIso8601String(),
                    'admin_approved_by' => auth('admin')->id(),
                ]),
            ]);
        });

        return back()->with('status', 'پرداخت تأیید شد؛ اشتراک فعال و فاکتور کاربر صادر شد.');
    }

    public function reject(Request $request, int $payment)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        DB::transaction(function () use ($payment, $data) {
            $record = SubscriptionPayment::query()->whereKey($payment)->lockForUpdate()->firstOrFail();
            abort_unless($record->gateway === 'card_to_card', 404);
            if ($record->status !== 'pending') {
                abort(422, 'این پرداخت قبلاً تعیین تکلیف شده است.');
            }

            $record->update([
                'status' => 'failed',
                'raw_response' => array_merge($record->raw_response ?? [], [
                    'admin_rejected_at' => now()->toIso8601String(),
                    'admin_rejected_by' => auth('admin')->id(),
                    'admin_rejection_reason' => trim((string) ($data['reason'] ?? '')),
                ]),
            ]);
            Subscription::query()->whereKey($record->subscription_id)->where('status', 'pending')->update(['status' => 'failed']);
            CouponRedemption::query()->where('payment_id', $record->id)->where('status', 'reserved')->update(['status' => 'released']);
        });

        return back()->with('status', 'پرداخت رد شد و اشتراک فعال نشد.');
    }
}
