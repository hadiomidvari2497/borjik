<?php

namespace App\Support;

use Morilog\Jalali\CalendarUtils;

/**
 * زمان‌بندی اجاره‌ی ماهانه: هر ماه شمسی، در همان روزِ ماهِ تاریخ عقد صورت‌حساب صادر می‌شود
 * (نخستین صورت‌حساب در خود تاریخ عقد). در ماه‌های کوتاه‌تر، آخرین روز ماه جایگزین می‌شود.
 * کلاس فقط تاریخ (رشته‌ی Y-m-d میلادی) می‌گیرد و برمی‌گرداند تا بدون دیتابیس قابل تست باشد.
 */
final class RentSchedule
{
    private const MAX_MONTHS = 600;

    /**
     * @param  string       $signedAt  تاریخ عقد (Y-m-d میلادی)
     * @param  string       $until     تا این روز (شامل) — معمولاً امروز
     * @param  string|null  $endDate   تاریخ پایان قرارداد؛ در این روز یا بعد از آن صورت‌حسابی صادر نمی‌شود
     * @return array<int, array{seq:int, date:string, jy:int, jm:int}>
     */
    public static function issueDates(string $signedAt, string $until, ?string $endDate = null): array
    {
        [$gy, $gm, $gd] = array_map('intval', explode('-', $signedAt));
        [$jy, $jm, $jd] = CalendarUtils::toJalali($gy, $gm, $gd);

        $out = [];
        for ($k = 0; $k < self::MAX_MONTHS; $k++) {
            $idx = ($jm - 1) + $k;
            $y = $jy + intdiv($idx, 12);
            $m = ($idx % 12) + 1;
            $d = min($jd, CalendarUtils::jalaliMonthLength($y, $m));

            [$cy, $cm, $cd] = CalendarUtils::toGregorian($y, $m, $d);
            $date = sprintf('%04d-%02d-%02d', $cy, $cm, $cd);

            if ($date > $until || ($endDate !== null && $date >= $endDate)) {
                break;
            }
            $out[] = ['seq' => $k + 1, 'date' => $date, 'jy' => $y, 'jm' => $m];
        }

        return $out;
    }
}
