<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * ثبت اجرای واقعی وظایف زمان‌بندی‌شده در دیتابیس؛ خروجی/خطا تا حدی که Event در دسترس قرار دهد ذخیره می‌شود.
 */
$tracked = function ($event, string $name) {
    $logId = null;
    $started = null;

    $event->before(function () use (&$logId, &$started, $name) {
        $started = microtime(true);
        try {
            $logId = DB::table('cron_execution_logs')->insertGetId([
                'task_name' => $name,
                'status' => 'running',
                'scheduled_at' => now(),
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    });

    $finish = function (string $status, ?string $error = null) use (&$logId, &$started, $event) {
        if (! $logId) {
            return;
        }
        try {
            $output = method_exists($event, 'getOutput') ? (string) $event->getOutput() : null;
            DB::table('cron_execution_logs')->where('id', $logId)->update([
                'status' => $status,
                'finished_at' => now(),
                'duration_ms' => $started ? (int) round((microtime(true) - $started) * 1000) : null,
                'output' => $output !== '' ? $output : null,
                'error' => $error,
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    };

    $event->onSuccess(fn () => $finish('success'));
    $event->onFailure(fn () => $finish('failed', 'دستور زمان‌بندی‌شده با خطا یا کد خروج غیرصفر پایان یافت.'));
    return $event;
};

// برای تشخیص خاموش‌شدن Cron، این heartbeat باید هر دقیقه توسط schedule:run اجرا شود.
$tracked(Schedule::call(static function () {
    // صرف اجرای این callback، heartbeat را در لاگ ثبت می‌کند.
})->everyMinute()->name('scheduler:heartbeat'), 'scheduler:heartbeat');

// زمان‌بندی روزانه (ساعت‌ها به وقت UTC‌اند؛ ۰۶:۰۰ UTC ≈ ۰۹:۳۰ تهران). نیازمند اجرای هر دقیقه‌ی php artisan schedule:run است.
$tracked(Schedule::command('rent:issue-invoices')->dailyAt('06:00')->withoutOverlapping(), 'rent:issue-invoices');
$tracked(Schedule::command('charges:remind-expenses')->dailyAt('06:30')->withoutOverlapping(), 'charges:remind-expenses');
$tracked(Schedule::command('charges:issue-late-fees')->dailyAt('07:30')->withoutOverlapping(), 'charges:issue-late-fees');
$tracked(Schedule::command('requests:notify-overdue')->dailyAt('07:00')->withoutOverlapping(), 'requests:notify-overdue');
$tracked(Schedule::command('calendar:send-reminders')->everyMinute()->withoutOverlapping(), 'calendar:send-reminders');
$tracked(Schedule::command('borjik:health-check')->everyFiveMinutes()->withoutOverlapping(10), 'borjik:health-check');
$tracked(Schedule::command('borjik:performance-sample')->everyFiveMinutes()->withoutOverlapping(10), 'borjik:performance-sample');
$tracked(Schedule::command('borjik:backup')->hourly()->withoutOverlapping(180), 'borjik:backup');
