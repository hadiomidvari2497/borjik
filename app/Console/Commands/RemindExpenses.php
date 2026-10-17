<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\ChargePeriod;
use App\Models\ChargeSetting;
use App\Models\Expense;
use App\Models\SmsLog;
use App\Services\Notifier;
use App\Services\Sms\SmsSender;
use App\Support\CurrentBuilding;
use App\Support\Fa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

/**
 * یادآوری پیامکی به مالک و مدیر: اگر تا N روز (warn_days_before) مانده به تاریخ محاسبه‌ی شارژ هزینه‌های ماه ثبت نشده باشد.
 * هر نفر در هر ماه شمسی حداکثر یک پیامک می‌گیرد. در Scheduler روزانه اجرا شود.
 */
class RemindExpenses extends Command
{
    protected $signature = 'charges:remind-expenses';

    protected $description = 'یادآوری ثبت هزینه‌های ماه پیش از تاریخ محاسبه‌ی شارژ';

    public function handle(SmsSender $sms, Notifier $notifier, CurrentBuilding $current): int
    {
        $today = now()->startOfDay();
        $j = Jalalian::fromCarbon($today);
        $monthStart = (new Jalalian($j->getYear(), $j->getMonth(), 1))->toCarbon()->startOfDay();
        $sent = 0;

        Building::where('status', 'active')->get()->each(function (Building $building) use (&$sent, $sms, $notifier, $current, $today, $j, $monthStart) {
            $current->actingAs($building->id, function () use (&$sent, $sms, $notifier, $building, $today, $j, $monthStart) {
                $setting = ChargeSetting::first();
                if (! $setting) {
                    return;
                }

                $calc = (new Jalalian($j->getYear(), $j->getMonth(), min($setting->calc_day, 29)))->toCarbon()->startOfDay();
                if ($today->lt($calc->copy()->subDays($setting->warn_days_before)) || $today->gt($calc)) {
                    return;
                }

                $period = ChargePeriod::where('jalali_year', $j->getYear())->where('jalali_month', $j->getMonth())->first();
                if ($period && Expense::where('period_id', $period->id)->exists()) {
                    return;
                }

                $recipients = DB::table('building_user_roles as bur')
                    ->join('roles as r', 'r.id', '=', 'bur.role_id')->join('users as u', 'u.id', '=', 'bur.user_id')
                    ->where('bur.building_id', $building->id)->where('bur.status', 'active')->whereIn('r.key', ['owner', 'manager'])
                    ->whereNull('u.deleted_at')->distinct()->get(['u.id', 'u.mobile']);

                foreach ($recipients as $user) {
                    $already = SmsLog::where('building_id', $building->id)->where('user_id', $user->id)
                        ->where('template_key', 'expense_reminder')->where('created_at', '>=', $monthStart)->exists();
                    if ($already) {
                        continue;
                    }
                    $sms->sendTemplate('expense_reminder', $user->mobile, [
                        'month' => Fa::MONTHS[$j->getMonth() - 1], 'building' => $building->name, 'date' => Fa::date($calc),
                    ], $user->id, $building->id);
                    $notifier->send((int) $user->id, (int) $building->id, 'expense_reminder', 'یادآوری ثبت هزینه‌ها',
                        'هزینه‌های '.Fa::MONTHS[$j->getMonth() - 1].' هنوز ثبت نشده است؛ شارژ در '.Fa::date($calc).' محاسبه می‌شود.',
                        route('expenses.index'), ['level' => 'warning', 'category' => 'expense', 'key' => 'expense-reminder:'.$j->getYear().$j->getMonth(), 'label' => 'ثبت هزینه‌ها']);
                    $sent++;
                }
            });
        });

        $this->info("{$sent} پیامک یادآوری ارسال شد.");

        return self::SUCCESS;
    }
}
