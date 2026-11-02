<?php

namespace App\Console\Commands;

use App\Models\OperationsHealthCheck;
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
            $checks['email'] = ['name' => 'ایمیل / SMTP', 'status' => $connected ? 'ok' : 'fail', 'detail' => $connected ? 'اتصال شبکه برقرار است؛ ارسال واقعی ایمیل در این بررسی انجام نمی‌شود.' : 'اتصال SMTP برقرار نشد؛ میزبان، پورت و دسترسی شبکه را بررسی کن.'];
        } else {
            $checks['email'] = ['name' => 'ایمیل', 'status' => in_array($transport, ['log', 'array'], true) ? 'warning' : 'unknown', 'detail' => 'Mailer فعال: '.$mailer.'؛ اتصال واقعی از این درایور قابل سنجش نیست.'];
        }

        $smsProvider = SmsProvider::current();
        $checks['sms'] = ['name' => 'درگاه پیامک', 'status' => $smsProvider ? 'ok' : 'warning', 'detail' => $smsProvider ? 'اتصال پیش‌فرض فعال است؛ برای اطمینان از ارسال، پیامک آزمایشی بفرست.' : 'اتصال پیامکی پیش‌فرض فعال نیست.'];

        try {
            $activeGateways = app(PaymentGatewayRegistry::class)->active();
            $checks['payment_gateway'] = ['name' => 'درگاه پرداخت', 'status' => $activeGateways !== [] ? 'ok' : 'warning', 'detail' => $activeGateways !== [] ? count($activeGateways).' درگاه فعال با اطلاعات پایه شناسایی شد؛ تراکنش آزمایشی انجام نشده است.' : 'درگاه پرداخت فعال و کامل شناسایی نشد.'];
        } catch (Throwable $exception) {
            report($exception);
            $checks['payment_gateway'] = ['name' => 'درگاه پرداخت', 'status' => 'fail', 'detail' => 'خواندن تنظیمات درگاه پرداخت ناموفق بود.'];
        }

        $queueDriver = (string) config('queue.default', '');
        if ($queueDriver === 'database') {
            $queueTable = (string) config('queue.connections.database.table', 'jobs');
            $queueReady = Schema::hasTable($queueTable);
            $queuedJobs = $queueReady ? (int) DB::table($queueTable)->count() : 0;
            $checks['queue'] = ['name' => 'صف پردازش', 'status' => ! $queueReady ? 'fail' : ($queuedJobs > 100 ? 'warning' : 'ok'), 'detail' => ! $queueReady ? 'جدول صف دیتابیس وجود ندارد.' : 'درایور database؛ '.$queuedJobs.' کار در انتظار. زنده‌بودن worker از داخل برنامه قابل اثبات نیست.'];
        } else {
            $checks['queue'] = ['name' => 'صف پردازش', 'status' => $queueDriver === 'sync' ? 'warning' : ($queueDriver !== '' ? 'unknown' : 'fail'), 'detail' => $queueDriver === 'sync' ? 'درایور sync صف پس‌زمینه ایجاد نمی‌کند.' : 'درایور صف: '.($queueDriver ?: 'تنظیم نشده').'.'];
        }

        $activeAdmins = AdminUser::query()->where('status', 'active');
        $activeAdminCount = (clone $activeAdmins)->count();
        $unprotectedSuperAdmins = (clone $activeAdmins)->where('is_super', true)->where('two_factor_enabled', false)->count();
        $checks['admin_security'] = ['name' => 'امنیت پنل مدیریت', 'status' => $unprotectedSuperAdmins > 0 ? 'warning' : 'ok', 'detail' => 'مدیر فعال: '.$activeAdminCount.'؛ سوپرادمین فعال بدون تأیید دومرحله‌ای: '.$unprotectedSuperAdmins.'.'];

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
