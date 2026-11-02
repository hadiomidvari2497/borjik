<?php

namespace App\Console\Commands;

use App\Models\OperationsHealthCheck;
use App\Models\BackupRun;
use App\Models\AdminUser;
use App\Services\Payments\PaymentGatewayRegistry;
use App\Models\OperationsSetting;
use App\Models\SmsLog;
use App\Models\SmsProvider;
use App\Services\Sms\HttpSmsGateway;
use App\Services\Sms\SmsException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
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

        $mailer = (string) config('mail.default', 'log');
        $mailConfig = (array) config('mail.mailers.'.$mailer, []);
        $transport = (string) ($mailConfig['transport'] ?? $mailer);
        if ($transport === 'smtp') {
            $host = (string) ($mailConfig['host'] ?? '');
            $port = (int) ($mailConfig['port'] ?? 0);
            $socket = false;
            if ($host !== '' && $port > 0) {
                $socket = @stream_socket_client('tcp://'.$host.':'.$port, $socketCode, $socketError, 3);
            }
            $connected = is_resource($socket);
            if ($connected) {
                fclose($socket);
            }
            $checks['email'] = [
                'name' => 'ایمیل / SMTP',
                'status' => $connected ? 'warning' : 'fail',
                'detail' => $connected
                    ? 'اتصال شبکه به SMTP برقرار است؛ احراز هویت و تحویل واقعی ایمیل آزمایش نشده است.'
                    : 'اتصال شبکه به SMTP برقرار نشد؛ میزبان، پورت و دسترسی شبکه را بررسی کن.',
            ];
        } else {
            $checks['email'] = [
                'name' => 'ایمیل',
                'status' => in_array($transport, ['log', 'array'], true) ? 'warning' : 'unknown',
                'detail' => 'Mailer فعال: '.$mailer.'؛ ارسال و تحویل واقعی ایمیل از این درایور قابل تأیید نیست.',
            ];
        }

        $smsProvider = SmsProvider::current();
        $checks['sms'] = [
            'name' => 'درگاه پیامک',
            'status' => $smsProvider ? 'warning' : 'fail',
            'detail' => $smsProvider
                ? 'اتصال پیش‌فرض فعال است؛ ارسال واقعی در این بررسی انجام نشده و نیازمند پیامک آزمایشی است.'
                : 'اتصال پیامکی پیش‌فرض فعال نیست.',
        ];

        try {
            $activeGateways = app(PaymentGatewayRegistry::class)->active();
            $checks['payment_gateway'] = [
                'name' => 'درگاه پرداخت',
                'status' => $activeGateways !== [] ? 'warning' : 'fail',
                'detail' => $activeGateways !== []
                    ? count($activeGateways).' درگاه فعال شناسایی شد؛ اعتبارسنجی تراکنش واقعی/آزمایشی انجام نشده است.'
                    : 'درگاه پرداخت فعال و کامل شناسایی نشد.',
            ];
        } catch (Throwable $exception) {
            report($exception);
            $checks['payment_gateway'] = ['name' => 'درگاه پرداخت', 'status' => 'fail', 'detail' => 'خواندن تنظیمات درگاه پرداخت ناموفق بود.'];
        }

        $queueDriver = (string) config('queue.default', '');
        if ($queueDriver === 'database') {
            $queueTable = (string) config('queue.connections.database.table', 'jobs');
            $queueReady = Schema::hasTable($queueTable);
            $queuedJobs = $queueReady ? (int) DB::table($queueTable)->count() : 0;
            $workerStatus = 'unknown';
            $workerRunning = false;
            if (PHP_OS_FAMILY !== 'Windows') {
                try {
                    $process = new Process(['ps', '-eo', 'args']);
                    $process->setTimeout(3);
                    $process->run();
                    if ($process->isSuccessful()) {
                        $workerRunning = collect(explode("\n", $process->getOutput()))->contains(
                            fn ($line) => (bool) preg_match('/artisan\\s+(queue:work|queue:listen|horizon)(\\s|$)/', trim($line))
                        );
                        $workerStatus = $workerRunning ? 'ok' : 'warning';
                    }
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
            $queueStatus = ! $queueReady ? 'fail' : (! $workerRunning ? $workerStatus : ($queuedJobs > 100 ? 'warning' : 'ok'));
            $queueDetail = ! $queueReady
                ? 'جدول صف دیتابیس وجود ندارد.'
                : 'درایور database؛ '.$queuedJobs.' کار در انتظار؛ '.($workerRunning ? 'پردازشگر صف فعال شناسایی شد.' : 'پردازشگر فعال صف شناسایی نشد یا وضعیت آن قابل سنجش نیست.');
            if ($queueReady && $queuedJobs > 100) {
                $queueDetail .= ' تعداد کارهای منتظر از آستانه ۱۰۰ بیشتر است.';
            }
            $checks['queue'] = ['name' => 'صف پردازش', 'status' => $queueStatus, 'detail' => $queueDetail];
        } else {
            $checks['queue'] = [
                'name' => 'صف پردازش',
                'status' => $queueDriver === 'sync' ? 'warning' : ($queueDriver !== '' ? 'unknown' : 'fail'),
                'detail' => $queueDriver === 'sync'
                    ? 'درایور sync صف پس‌زمینه ایجاد نمی‌کند؛ اجرای پردازشگر مستقل تأییدشدنی نیست.'
                    : 'درایور صف: '.($queueDriver ?: 'تنظیم نشده').'. وضعیت پردازشگر از این درایور قابل تأیید نیست.',
            ];
        }

        $latestSuccessfulBackup = BackupRun::query()->where('status', 'success')->latest('id')->first();
        $backupEnabled = (bool) ($settings['backup_enabled'] ?? false);
        $cloudEnabled = (bool) ($settings['cloud_enabled'] ?? false);
        if (! $backupEnabled) {
            $checks['backup'] = [
                'name' => 'پشتیبان‌گیری',
                'status' => 'unknown',
                'detail' => 'بکاپ خودکار در تنظیمات غیرفعال است؛ اجرای دستی و بازیابی نسخه‌ها جداگانه بررسی شود.',
            ];
        } elseif (! $latestSuccessfulBackup) {
            $checks['backup'] = [
                'name' => 'پشتیبان‌گیری',
                'status' => 'warning',
                'detail' => 'هنوز بکاپ موفقی ثبت نشده است.',
            ];
        } else {
            $maxAgeHours = match ($settings['backup_frequency'] ?? 'daily') {
                'twice_daily' => 18,
                'weekly' => 24 * 8,
                default => 36,
            };
            $backupAgeHours = $latestSuccessfulBackup->finished_at
                ? $latestSuccessfulBackup->finished_at->diffInHours(now())
                : PHP_INT_MAX;
            $backupFresh = $backupAgeHours <= $maxAgeHours;
            $cloudUploaded = ! $cloudEnabled || filled($latestSuccessfulBackup->cloud_file_id);
            $backupStatus = ! $backupFresh || ! $cloudUploaded ? 'warning' : 'ok';
            $backupDetail = 'آخرین بکاپ موفق: '.($latestSuccessfulBackup->finished_at?->toDateTimeString() ?? 'زمان پایان ثبت نشده').'.';
            if (! $backupFresh) {
                $backupDetail .= ' از بازهٔ مورد انتظار برای تناوب انتخاب‌شده قدیمی‌تر است.';
            }
            if ($cloudEnabled && ! $cloudUploaded) {
                $backupDetail .= ' ارسال ابری در آخرین اجرای موفق تأیید نشده است.';
            }
            $backupDetail .= ' صحت بازیابی کامل در محیط جداگانه با این بررسی اثبات نمی‌شود.';
            $checks['backup'] = ['name' => 'پشتیبان‌گیری', 'status' => $backupStatus, 'detail' => $backupDetail];
        }

        $activeAdmins = AdminUser::query()->where('status', 'active');
        $activeAdminCount = (clone $activeAdmins)->count();
        $unprotectedAdmins = (clone $activeAdmins)->where('two_factor_enabled', false)->count();
        $checks['admin_security'] = ['name' => 'امنیت پنل مدیریت', 'status' => $unprotectedAdmins > 0 ? 'warning' : 'ok', 'detail' => 'مدیر فعال: '.$activeAdminCount.'؛ مدیر فعال بدون تأیید دومرحله‌ای: '.$unprotectedAdmins.'.'];

        // هر نتیجه‌ای غیر از ok باید در وضعیت کلی دیده شود؛ unknown هرگز معادل سلامت نیست.
        $needsAttention = collect($checks)->contains(fn ($check) => ($check['status'] ?? 'unknown') !== 'ok');
        $hasFailure = collect($checks)->contains(fn ($check) => ($check['status'] ?? 'unknown') === 'fail');
        $status = $hasFailure ? 'fail' : ($needsAttention ? 'warning' : 'ok');
        $record = OperationsHealthCheck::create([
            'status' => $status,
            'checks' => $checks,
            'summary' => $hasFailure
                ? 'حداقل یک بررسی با خطای واقعی مواجه شده است.'
                : ($needsAttention ? 'حداقل یک سرویس هشدار دارد یا عملکرد آن هنوز تأیید نشده است.' : 'تمام بررسی‌های قابل تأیید موفق بودند.'),
        ]);

        $previous = OperationsHealthCheck::query()->where('id', '<', $record->id)->latest('id')->first();
        $issueState = collect($checks)->filter(fn ($check) => ($check['status'] ?? 'unknown') !== 'ok')
            ->map(fn ($check, $key) => $key.':'.$check['status'])->all();
        $previousIssueState = collect($previous?->checks ?? [])
            ->filter(fn ($check) => ($check['status'] ?? 'unknown') !== 'ok')
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
