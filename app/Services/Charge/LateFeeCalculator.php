<?php

namespace App\Services\Charge;

/**
 * جریمه‌ی دیرکرد (بند ۴.۱۵.۲). type = percent (درصد ماهانه‌ی صحیح از مبلغ معوق، روزشمار بر مبنای ۳۰ روز)
 * یا fixed (مبلغ ثابت روزانه). جریمه از روز startDay شروع می‌شود و با سقف cap محدود می‌شود.
 */
final class LateFeeCalculator
{
    public static function compute(int $overdueAmount, int $daysLate, string $type, int $value, ?int $cap = null, int $startDay = 1): int
    {
        if ($overdueAmount <= 0 || $daysLate < $startDay) {
            return 0;
        }

        $days = $daysLate - $startDay + 1;
        $fee = match ($type) {
            'percent' => intdiv($overdueAmount * $value * $days + 1500, 3000), // مبلغ × درصد/۱۰۰ × روز/۳۰
            'fixed' => $value * $days,
            default => 0,
        };

        return $cap !== null ? min($fee, $cap) : $fee;
    }
}
