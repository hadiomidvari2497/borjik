<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Support\JalaliDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** آرشیو پیامک‌های ارسال‌شده به تفکیک تاریخ (بند ۴.۱۷ سند). */
class SmsLogController extends Controller
{
    private const EXPORT_LIMIT = 50000;

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $base = $this->query($filters);

        $statusCounts = (clone $base)->reorder()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $daily = (clone $base)->reorder()
            ->selectRaw("date(created_at) as d, count(*) as total, sum(case when status = 'sent' then 1 else 0 end) as sent, sum(case when status = 'failed' then 1 else 0 end) as failed")
            ->groupBy('d')->orderByDesc('d')->limit(31)->get();

        return view('admin.sms.logs.index', [
            'logs' => (clone $base)->latest('id')->paginate(30)->withQueryString(),
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'daily' => $daily,
            'templates' => SmsLog::query()->whereNotNull('template_key')->distinct()->orderBy('template_key')->pluck('template_key'),
            'gateways' => SmsLog::query()->whereNotNull('gateway')->distinct()->orderBy('gateway')->pluck('gateway'),
        ]);
    }

    public function clear(Request $request)
    {
        $data = $request->validate([
            'scope' => ['required', 'in:all,range'],
            'from' => ['required_if:scope,range', 'nullable', 'string', 'max:20'],
            'to' => ['required_if:scope,range', 'nullable', 'string', 'max:20'],
            'confirmation' => ['required', 'in:حذف'],
        ]);
        $query = SmsLog::query();
        if ($data['scope'] === 'range') {
            $from = JalaliDate::parse($data['from']);
            $to = JalaliDate::parse($data['to']);
            if (! $from || ! $to || $to->lt($from)) {
                return back()->withErrors(['to' => 'بازهٔ تاریخی واردشده معتبر نیست.'])->withInput();
            }
            $query->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);
        }
        $deleted = $query->delete();

        return redirect()->route('admin.sms.logs.index')
            ->with('status', number_format($deleted).' پیامک از آرشیو پاک شد.');
    }

    public function export(Request $request)
    {
        $query = $this->query($this->filters($request))->latest('id')->limit(self::EXPORT_LIMIT);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM برای نمایش درست فارسی در Excel
            fputcsv($out, ['تاریخ', 'شماره', 'قالب', 'وضعیت', 'اتصال', 'شناسه پیگیری', 'خطا', 'متن']);

            foreach ($query->cursor() as $log) {
                fputcsv($out, array_map([self::class, 'csvSafe'], [
                    JalaliDate::format($log->created_at).' '.$log->created_at->format('H:i'),
                    $log->mobile, $log->template_key, $log->status, $log->gateway, $log->provider_ref, $log->error, $log->body,
                ]));
            }
            fclose($out);
        }, 'sms-archive-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** جلوگیری از تزریق فرمول در Excel (سلول‌هایی که با = + - @ شروع شوند). */
    public static function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    /** @return array{from:?string,to:?string,status:string,q:string,template:string,gateway:string} */
    private function filters(Request $request): array
    {
        return [
            'from' => $request->query('from') ?: null,
            'to' => $request->query('to') ?: null,
            'status' => in_array($request->query('status'), ['queued', 'sent', 'failed'], true) ? $request->query('status') : '',
            'q' => trim((string) $request->query('q', '')),
            'template' => (string) $request->query('template', ''),
            'gateway' => (string) $request->query('gateway', ''),
        ];
    }

    private function query(array $f): Builder
    {
        $from = JalaliDate::parse($f['from']);
        $to = JalaliDate::parse($f['to']);
        $like = '%'.addcslashes(preg_replace('/\D/', '', \App\Support\MobileNumber::toLatinDigits($f['q'])), '%_\\').'%';

        return SmsLog::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->endOfDay()))
            ->when($f['status'] !== '', fn ($q) => $q->where('status', $f['status']))
            ->when($f['template'] !== '', fn ($q) => $q->where('template_key', $f['template']))
            ->when($f['gateway'] !== '', fn ($q) => $q->where('gateway', $f['gateway']))
            ->when(preg_replace('/\D/', '', $f['q']) !== '', fn ($q) => $q->where('mobile', 'like', $like));
    }
}
