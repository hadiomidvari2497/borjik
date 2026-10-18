<?php

namespace App\Services\Sms;

use App\Jobs\SendSmsJob;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use Throwable;

/** رندر قالب ← ثبت لاگ ← ارسال فوری به‌صورت پیش‌فرض؛ صف اختیاری است. */
class SmsSender
{
    public function sendTemplate(string $key, string $mobile, array $vars = [], ?int $userId = null, ?int $buildingId = null): SmsLog
    {
        $body = $this->render($key, $vars, $buildingId);
        $sensitive = in_array($key, config('sms.sensitive', []), true);

        $log = SmsLog::create([
            'building_id' => $buildingId,
            'user_id' => $userId,
            'mobile' => $mobile,
            'template_key' => $key,
            'body' => $sensitive ? '[محرمانه]' : $body,
            'status' => 'queued',
        ]);

        if (config('sms.queue', false)) {
            SendSmsJob::dispatch($log->id, $mobile, $body);

            return $log;
        }

        // ارسال هم‌زمان باعث می‌شود کارکرد ارسال به اجرای Queue Worker وابسته نباشد.
        try {
            SendSmsJob::dispatchSync($log->id, $mobile, $body);
        } catch (Throwable $e) {
            // خطا باید در آرشیو دیده شود، اما ارسال پیامک نباید کل درخواست برنامه را با 500 متوقف کند.
            $log->refresh();
            if ($log->status === 'queued') {
                $log->update([
                    'status' => 'failed',
                    'gateway' => 'unknown',
                    'error' => 'ارسال اجرا نشد: '.\Illuminate\Support\Str::limit($e->getMessage(), 200, '…'),
                ]);
            }
        }

        return $log->fresh();
    }

    private function render(string $key, array $vars, ?int $buildingId): string
    {
        $row = SmsTemplate::where('key', $key)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('building_id', $buildingId)->orWhereNull('building_id'))
            ->orderByRaw('building_id is null')
            ->first();

        $text = $row?->body ?? config("sms.templates.{$key}");
        if ($text === null) {
            throw new \InvalidArgumentException("قالب پیامک «{$key}» تعریف نشده است.");
        }

        $pairs = [];
        foreach ($vars as $k => $v) {
            $pairs['{'.$k.'}'] = (string) $v;
        }

        return strtr($text, $pairs);
    }
}
