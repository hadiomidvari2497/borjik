<?php

use Carbon\\Carbon;
use Cron\\CronExpression;
use Illuminate\\Foundation\\Inspiring;
use Illuminate\\Support\\Facades\\Artisan;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schedule;
use Throwable;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * لاگ اجرای وظایف زمان‌بندی‌شده.
 *
 * خروجی فرمان‌ها در فایل موقت هر وظیفه جمع‌آوری و پس از پایان به دیتابیس منتقل می‌شود؛
 * فایل پیش از هر اجرا خالی می‌شود تا خروجی اجراهای قبلی با اجرای فعلی قاطی نشود.
 */
$tracked = function ($event, string $name) {
    $logId = null;
    $started = null;
    $outputPath = storage_path('logs/cron-'.sha1($name).'.log');

    if (method_exists($event, 'sendOutputTo')) {
        $event->sendOutputTo($outputPath);
    }

    $event->before(function () use (&$logId, &$started, $name, $outputPath, $event) {
        $started = microtime(true);
        $now = now();
        $scheduledAt = null;

        try {
            // زمان برنامه‌ریزی‌شده را از عبارت cron می‌گیریم، نه از زمان شروع واقعی.
            $timezone = $event->timezone ?? config('app.timezone', 'UTC');
            $scheduledAt = Carbon::instance(
                CronExpression::factory($event->expression)->getPreviousRunDate($now->copy()->addSecond(), 0, true, $timezone)
            )->toDateTimeString();
        } catch (Throwable $exception) {
            report($exception);
        }

        // خطای فایل خروجی نباید جلوی اجرای خود وظیفه را بگیرد.
        try {
            if (is_dir(dirname($outputPath)) && is_writable(dirname($outputPath))) {
                file_put_contents($outputPath, '', LOCK_EX);
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $logId = DB::table('cron_execution_logs')->insertGetId([
                'task_name' => $name,
                'status' => 'running',
                'scheduled_at' => $scheduledAt,
                'started_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    });

    $finish = function (string $status, ?string $error = null) use (&$logId, &$started, $outputPath) {
        if (! $logId) {
            return;
        }

        try {
            $output = null;
            if (is_file($outputPath) && is_readable($outputPath)) {
                $contents = file_get_contents($outputPath);
                if (is_string($contents) && trim($contents) !== '') {
                    // از رشد نامحدود رکوردهای دیتابیس جلوگیری می‌کنیم.
                    $output = mb_substr($contents, 0, 100000);
                }
            }

            DB::table('cron_execution_logs')->where('id', $logId)->update([
                'status' => $status,
                'finished_at' => now(),
                'duration_ms' => $started ? (int) round((microtime(true) - $started) * 1000) : null,
                'output' => $output,
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

// این heartbeat فقط ثابت می‌کند Scheduler در حال اجراست؛ برای کارکردش باید schedule:run هر دقیقه فراخوانی شود.
$tracked(
    Schedule::call(static function () {
        // ثبت اجرای موفق از طریق callbackهای Event انجام می‌شود.
    })->everyMinute()->name('scheduler:heartbeat')->withoutOverlapping(2),
    'scheduler:heartbeat'
);

// config/app.php منطقهٔ زمانی UTC است؛ ساعت‌ها بر همین مبنا اجرا می‌شوند.
$tracked(Schedule::command('rent:issue-invoices')->dailyAt('06:00')->withoutOverlapping(180), 'rent:issue-invoices');
$tracked(Schedule::command('charges:remind-expenses')->dailyAt('06:30')->withoutOverlapping(180), 'charges:remind-expenses');
$tracked(Schedule::command('requests:notify-overdue')->dailyAt('07:00')->withoutOverlapping(180), 'requests:notify-overdue');
$tracked(Schedule::command('charges:issue-late-fees')->dailyAt('07:30')->withoutOverlapping(180), 'charges:issue-late-fees');
$tracked(Schedule::command('calendar:send-reminders')->everyMinute()->withoutOverlapping(2), 'calendar:send-reminders');
