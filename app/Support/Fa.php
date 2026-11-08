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

    /** مبلغ خلاصه به تومان؛ مثال: «۵ میلیارد تومان» یا «۲٫۵ میلیون تومان». */
    public static function short(int|float|null $n): string
    {
        $n = (float) ($n ?? 0);
        foreach ([[1_000_000_000, 'میلیارد'], [1_000_000, 'میلیون']] as [$unit, $name]) {
            if ($n >= $unit) {
                $v = rtrim(rtrim(number_format($n / $unit, 1, '.', ''), '0'), '.');

                return self::digits(str_replace('.', '٫', $v)).' '.$name.' تومان';
            }
        }

        return self::money($n);
    }

    /** مثال: «۱۸ مهر» با قالب j F. */
    public static function date(CarbonInterface $date, string $format = 'j F'): string
    {
        return self::digits(Jalalian::fromCarbon($date)->format($format));
    }

    /** برچسب کوتاه زمان برای لیست گفتگوها: ساعت (امروز)، «دیروز» یا تاریخ. */
    public static function stamp(CarbonInterface $date): string
    {
        $now = now($date->getTimezone());

        return match (true) {
            $date->isSameDay($now) => self::digits($date->format('H:i')),
            $date->isSameDay($now->copy()->subDay()) => 'دیروز',
            default => self::date($date, $date->year === $now->year ? 'j F' : 'Y/m/d'),
        };
    }

    /** برچسب روز برای جداکننده‌های گفتگو: «امروز»، «دیروز» یا تاریخ کامل. */
    public static function dayLabel(CarbonInterface $date): string
    {
        $now = now($date->getTimezone());

        return match (true) {
            $date->isSameDay($now) => 'امروز',
            $date->isSameDay($now->copy()->subDay()) => 'دیروز',
            default => self::date($date, 'Y/m/d'),
        };
    }
}
