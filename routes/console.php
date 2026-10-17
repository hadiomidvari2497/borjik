<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// زمان‌بندی روزانه (ساعت‌ها به وقت UTC‌اند؛ ۰۶:۰۰ UTC ≈ ۰۹:۳۰ تهران). نیازمند اجرای هر دقیقه‌ی `php artisan schedule:run` است.
Schedule::command('rent:issue-invoices')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('charges:remind-expenses')->dailyAt('06:30')->withoutOverlapping();
Schedule::command('requests:notify-overdue')->dailyAt('07:00')->withoutOverlapping();
