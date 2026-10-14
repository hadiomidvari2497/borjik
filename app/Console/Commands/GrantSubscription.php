<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\MobileNumber;
use Illuminate\Console\Command;

/** تا زمانی که درگاه پرداخت اشتراک ساخته نشده، برای توسعه و تست اشتراک دستی می‌دهد. */
class GrantSubscription extends Command
{
    protected $signature = 'borjik:grant-subscription {mobile} {units=50} {months=12}';

    protected $description = 'اعطای اشتراک دستی به یک کاربر (برای توسعه)';

    public function handle(): int
    {
        $mobile = MobileNumber::normalize($this->argument('mobile'));
        $user = $mobile ? User::where('mobile', $mobile)->first() : null;
        if (! $user) {
            $this->error('کاربری با این شماره پیدا نشد؛ اول یک بار وارد سامانه شود.');

            return self::FAILURE;
        }

        $units = (int) $this->argument('units');
        $months = (int) $this->argument('months');
        $plan = SubscriptionPlan::firstOrCreate(
            ['name' => "دستی - {$units} واحد - {$months} ماه"],
            ['units_limit' => $units, 'duration_months' => $months, 'price' => 0, 'is_active' => false],
        );

        $sub = Subscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'units_limit' => $units,
            'starts_at' => now(), 'expires_at' => now()->addMonths($months), 'status' => 'active',
        ]);

        $this->info("اشتراک {$units} واحدی تا {$sub->expires_at->toDateString()} برای {$mobile} فعال شد.");

        return self::SUCCESS;
    }
}
