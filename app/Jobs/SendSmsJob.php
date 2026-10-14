<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\Sms\SmsGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $logId,
        private readonly string $mobile,
        private readonly string $body,
    ) {
    }

    public function handle(SmsGateway $gateway): void
    {
        $log = SmsLog::findOrFail($this->logId);

        try {
            $ref = $gateway->send($this->mobile, $this->body);
            $log->update(['status' => 'sent', 'provider_ref' => $ref, 'sent_at' => now()]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed']);
            throw $e;
        }
    }
}
