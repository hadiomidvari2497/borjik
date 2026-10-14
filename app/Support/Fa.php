<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Morilog\Jalali\Jalalian;

/** قالب‌بندی فارسی: ارقام فارسی، جداکننده‌ی هزارگان، مبلغ به تومان و تاریخ شمسی. */
final class Fa
{
    public const MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public static function digits(string|int|float $value): string
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    public static function num(int|float|null $n): string
    {
        return self::digits(str_replace(',', '٬', number_format((float) ($n ?? 0), 0, '.', ',')));
    }

    public static function money(int|float|null $n): string
    {
        return self::num($n).' تومان';
    }

    /** مثال: «۱۸ مهر» با قالب j F. */
    public static function date(CarbonInterface $date, string $format = 'j F'): string
    {
        return self::digits(Jalalian::fromCarbon($date)->format($format));
    }
}
