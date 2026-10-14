<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Morilog\Jalali\Jalalian;

/** ورودی و خروجی تاریخ شمسی برای فرم‌ها (قالب ۱۴۰۵/۰۷/۱۶). */
final class JalaliDate
{
    /** رشته‌ی شمسی (با ارقام فارسی یا لاتین) را به Carbon میلادی تبدیل می‌کند؛ تاریخ نامعتبر ← null. */
    public static function parse(?string $value): ?Carbon
    {
        $v = MobileNumber::toLatinDigits(trim((string) $value));
        if (! preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $v, $m)) {
            return null;
        }
        [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];

        try {
            $c = (new Jalalian($y, $mo, $d))->toCarbon()->startOfDay();
            $back = Jalalian::fromCarbon($c);

            return ($back->getYear() === $y && $back->getMonth() === $mo && $back->getDay() === $d) ? $c : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function format(?CarbonInterface $date): string
    {
        return $date ? Jalalian::fromCarbon($date)->format('Y/m/d') : '';
    }
}
