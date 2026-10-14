<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** نمونه‌ی تعرفه‌ها؛ قیمت‌ها را بعداً ادمین از پنل تغییر می‌دهد (به تومان). */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        foreach ([
            ['۱۰ واحد - ماهانه', 10, 1, 150000],
            ['۵۰ واحد - ماهانه', 50, 1, 600000],
            ['۵۰ واحد - سالانه', 50, 12, 6000000],
            ['۲۰۰ واحد - سالانه', 200, 12, 18000000],
        ] as [$name, $units, $months, $price]) {
            DB::table('subscription_plans')->updateOrInsert(
                ['name' => $name],
                ['units_limit' => $units, 'duration_months' => $months, 'price' => $price, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
