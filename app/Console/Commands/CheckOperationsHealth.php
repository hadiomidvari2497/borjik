<?php

namespace App\Console\Commands;

use App\Models\OperationsHealthCheck;
use App\Models\OperationsSetting;
use App\Models\SmsLog;
use App\Models\SmsProvider;
use App\Services\Sms\HttpSmsGateway;
use App\Services\Sms\SmsException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckOperationsHealth extends Command
{
    protected $signature = 'borjik:health-check';
    protected $description = 'Record health checks and send SMS on incidents or recovery';

    public function handle(): int
    {
        $settingsModel = OperationsSetting::current();
        $settings = $settingsModel->values();
        $checks = [];
        $checks['database'] = $this->probe(fn () => DB::select('SELECT 1'), 'پایگاه داده');
        $checks['storage'] = $this->probe(fn () => is_dir(storage_path('app')) && is_writable(storage_path('app')), 'فضای ذخیره‌سازی');
        $checks['cache'] = $this->probe(function () {
            $key = 'operations-health:'.\Illuminate\Support\Str::uuid();
            Cache::put($key, 'ok', 10);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);
            return $ok;
        }, 'کش');
        $diskTotal = @disk_total_space(base_path());
        $diskFree = @disk_free_space(base_path());
        $diskPercent = $diskTotal && $diskFree !== false ? round((1 - ($diskFree / $diskTotal)) * 100, 1) : null;
        $checks['disk'] = [
            'name' => 'فضای دیسک',
            'status' => $diskPercent === null ? 'unknown' : ($diskPercent >= (int) $settings['disk_warning_percent'] ? 'warning' : 'ok'),
            'detail' => $diskPercent === null ? 'این محیط اطلاعات دیسک را ارائه نمی‌کند.' : 'مصرف دیسک: '.$diskPercent.'٪',
        ];
        $cronTable = Schema::hasTable('cron_execution_logs');
        $heartbeat = $cronTable ? DB::table('cron_execution_logs')->where('task_name', 'scheduler:heartbeat')->where('status', 'success')->orderByDesc('finished_at')->value('finished_at') : null;
        $cronStatus = ! $cronTable ? 'unknown' : (! $heartbeat ? 'warning' : (\Illuminate\Support\Carbon::parse($heartbeat)->lt(now()->subMinutes(3)) ? 'warning' : 'ok'));
        $checks['scheduler'] = ['name' => 'Cron / Scheduler', 'status' => $cronStatus, 'detail' => $heartbeat ? 'آخرین heartbeat: '.$heartbeat : 'heartbeat ثبت نشده است.'];

        $failed = collect($checks)->contains(fn ($check) => in_array($check['status'], ['fail', 'warning'], true));
        $status = $failed ? 'warning' : 'ok';
        $record = OperationsHealthCheck::create([
            'status' => $status,
            'checks' => $checks,
            'summary' => $failed ? 'حداقل یک بررسی نیازمند رسیدگی است.' : 'بررسی‌های قابل‌دسترسی موفق بودند.',
        ]);

        $previous = OperationsHealthCheck::query()->where('id', '<', $record->id)->latest('id')->first();
        $issueState = collect($checks)->filter(fn ($check) => in_array($check['status'], ['fail', 'warning'], true))
            ->map(fn ($check, $key) => $key.':'.$check['status'])->all();
        $previousIssueState = collect($previous?->checks ?? [])
            ->filter(fn ($check) => in_array($check['status'] ?? 'unknown', ['fail', 'warning'], true))
            ->map(fn ($check, $key) => $key.':'.$check['status'])->all();
        $issuesChanged = $previous && $issueState !== $previousIssueState;
        if ($settings['alert_enabled'] && $settings['alert_mobile'] && (($issuesChanged) || (! $previous && $failed))) {
            if ($failed) {
                $issueNames = collect($checks)->filter(fn ($check) => in_array($check['status'], ['fail', 'warning'], true))
                    ->pluck('name')->implode('، ');
                $message = 'هشدار صابخونه: نیازمند بررسی: '.$issueNames.'؛ پنل مدیریت > مرکز عملیات را بررسی کنید.';
                $templateKey = 'operations_alert';
                $templateVars = [$issueNames];
            } else {
                $message = 'صابخونه: بررسی سلامت سایت دوباره به وضعیت عادی برگشت.';
                $templateKey = 'operations_recovered';
                // This template has no placeholders; keep the argument list non-empty so the gateway sends an empty pattern value.
                $templateVars = [''];
            }
            $this->sendAlert((string) $settings['alert_mobile'], $message, $templateKey, $templateVars);
        }

        $this->info('Health check '.$status.'; record #'.$record->id);
        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function probe(callable $test, string $name): array
    {
        try {
            $result = $test();
            $ok = is_bool($result) ? $result : true;
            return ['name' => $name, 'status' => $ok ? 'ok' : 'fail', 'detail' => $ok ? 'بررسی موفق بود.' : 'بررسی ناموفق بود.'];
        } catch (Throwable $exception) {
            report($exception);
            return ['name' => $name, 'status' => 'fail', 'detail' => 'بررسی ناموفق بود؛ جزئیات در لاگ برنامه ثبت شد.'];
        }
    }

    private function sendAlert(string $mobile, string $message, string $templateKey, array $templateVars = []): void
    {
        $provider = SmsProvider::current();
        $log = SmsLog::create(['mobile' => $mobile, 'template_key' => $templateKey, 'body' => $message, 'status' => 'queued', 'gateway' => $provider?->name ?? config('sms.driver', 'log')]);
        try {
            if (! $provider) {
                throw new SmsException('اتصال پیامکی پیش‌فرض و فعالی تنظیم نشده است.');
            }
            $result = (new HttpSmsGateway($provider))->sendWithResponse($mobile, $message, $templateKey, $templateVars);
            $log->update(['status' => 'sent', 'provider_ref' => $result['ref'], 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $log->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 250)]);
            report($exception);
        }
    }
}
