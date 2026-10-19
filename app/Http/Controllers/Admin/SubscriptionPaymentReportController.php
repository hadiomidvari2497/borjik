<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Support\JalaliDate;
use App\Support\MobileNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionPaymentReportController extends Controller
{
    private const EXPORT_LIMIT = 50000;

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $base = $this->paymentsQuery($filters);

        $summary = (clone $base)->reorder()->selectRaw("
            COUNT(*) AS total_count,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS paid_amount,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) AS pending_amount
        ")->first();

        $daily = (clone $base)->reorder()
            ->selectRaw("DATE(created_at) AS day, COUNT(*) AS total, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count, SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS revenue")
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(31)
            ->get();

        $payments = (clone $base)
            ->with(['user', 'subscription.plan'])
            ->latest('id')
            ->paginate(25, ['*'], 'payments_page')
            ->withQueryString();

        $subscriptions = Subscription::query()
            ->with(['user', 'plan'])
            ->when($filters['from'], fn (Builder $query) => $query->where('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn (Builder $query) => $query->where('created_at', '<=', $filters['to']))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '%_\\').'%';
                $query->whereHas('user', fn (Builder $user) => $user
                    ->where('mobile', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('company_name', 'like', $like));
            })
            ->latest('id')
            ->paginate(15, ['*'], 'subscriptions_page')
            ->withQueryString();

        $gateways = SubscriptionPayment::query()->whereNotNull('gateway')->distinct()->orderBy('gateway')->pluck('gateway');

        return view('admin.subscription-payments.index', compact('filters', 'summary', 'daily', 'payments', 'subscriptions', 'gateways'));
    }

    public function export(Request $request)
    {
        $query = $this->paymentsQuery($this->filters($request))
            ->with(['user', 'subscription.plan'])
            ->latest('id')
            ->limit(self::EXPORT_LIMIT);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['شناسه تراکنش', 'تاریخ ثبت', 'تاریخ پرداخت', 'کاربر', 'موبایل', 'پلن', 'مبلغ (تومان)', 'درگاه', 'وضعیت', 'شماره پیگیری']);

            $exported = 0;
            foreach ($query->lazyById(500) as $payment) {
                if ($exported >= self::EXPORT_LIMIT) {
                    break;
                }
                $exported++;
                fputcsv($out, array_map([self::class, 'csvSafe'], [
                    (string) $payment->id,
                    $payment->created_at ? JalaliDate::format($payment->created_at).' '.$payment->created_at->format('H:i') : '',
                    $payment->paid_at ? JalaliDate::format($payment->paid_at).' '.$payment->paid_at->format('H:i') : '',
                    $payment->user?->display_name ?? '',
                    $payment->user?->mobile ?? '',
                    $payment->subscription?->plan?->name ?? '',
                    (string) $payment->amount,
                    $payment->gateway,
                    $this->statusLabel($payment->status),
                    $payment->ref_id ?? '',
                ]));
            }

            fclose($out);
        }, 'subscription-payments-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function csvSafe(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    public function statusLabel(?string $status): string
    {
        return [
            'paid' => 'پرداخت موفق',
            'pending' => 'در انتظار پرداخت',
            'failed' => 'ناموفق',
        ][$status ?? ''] ?? ($status ?: 'نامشخص');
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'string', 'max:20'],
            'to' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:paid,pending,failed'],
            'gateway' => ['nullable', 'string', 'max:30'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $from = JalaliDate::parse($request->query('from'));
        $to = JalaliDate::parse($request->query('to'));
        if ($request->filled('from') && ! $from) {
            throw ValidationException::withMessages(['from' => 'تاریخ شروع شمسی معتبر نیست.']);
        }
        if ($request->filled('to') && ! $to) {
            throw ValidationException::withMessages(['to' => 'تاریخ پایان شمسی معتبر نیست.']);
        }
        if ($from && $to && $from->gt($to)) {
            throw ValidationException::withMessages(['to' => 'تاریخ پایان نباید قبل از تاریخ شروع باشد.']);
        }

        return [
            'from' => $from?->startOfDay(),
            'to' => $to?->endOfDay(),
            'from_input' => (string) $request->query('from', ''),
            'to_input' => (string) $request->query('to', ''),
            'status' => (string) $request->query('status', ''),
            'gateway' => (string) $request->query('gateway', ''),
            'q' => trim(MobileNumber::toLatinDigits((string) $request->query('q', ''))),
        ];
    }

    private function paymentsQuery(array $filters): Builder
    {
        $like = '%'.addcslashes($filters['q'], '%_\\').'%';

        return SubscriptionPayment::query()
            ->when($filters['from'], fn (Builder $query) => $query->where('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn (Builder $query) => $query->where('created_at', '<=', $filters['to']))
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['gateway'] !== '', fn (Builder $query) => $query->where('gateway', $filters['gateway']))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters, $like) {
                $query->where(function (Builder $where) use ($like) {
                    $where->where('ref_id', 'like', $like)
                        ->orWhere('id', is_numeric(trim($like, '%')) ? (int) trim($like, '%') : -1)
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('mobile', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('company_name', 'like', $like));
                });
            });
    }
}
