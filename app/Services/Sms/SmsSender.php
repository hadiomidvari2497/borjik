<?php

namespace App\Services\Sms;

use App\Jobs\SendSmsJob;
use App\Models\SmsLog;
use App\Models\SmsTemplate;

/** رندر قالب (اول قالب ساختمان، بعد سیستمی، بعد پیش‌فرض config) ← ثبت در sms_logs ← ارسال در صف. */
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

        SendSmsJob::dispatch($log->id, $mobile, $body);

        return $log;
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
