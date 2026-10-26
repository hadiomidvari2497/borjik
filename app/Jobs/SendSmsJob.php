<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\Sms\SmsGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** فاصلهٔ تلاش مجدد (ثانیه) */
    public array $backoff = [10, 60];

    public function __construct(
        private readonly int $logId,
        private readonly string $mobile,
        private readonly string $body,
        private readonly ?string $templateKey = null,
        private readonly array $templateVars = [],
    ) {
    }

    public function handle(SmsGateway $gateway): void
    {
        $log = SmsLog::findOrFail($this->logId);

        $label = method_exists($gateway, 'label') ? $gateway->label() : class_basename($gateway);

        try {
            $ref = $gateway->send($this->mobile, $this->body, $log->template_key ?: $this->templateKey, $this->templateVars);
            $label = method_exists($gateway, 'label') ? $gateway->label() : $label;
            $log->update(['status' => 'sent', 'provider_ref' => $ref, 'gateway' => $label, 'error' => null, 'sent_at' => now()]);
        } catch (Throwable $e) {
            $label = method_exists($gateway, 'label') ? $gateway->label() : $label;
            $log->update(['status' => 'failed', 'gateway' => $label, 'error' => Str::limit($e->getMessage(), 250, '…')]);
            throw $e;
        }
    }
}
