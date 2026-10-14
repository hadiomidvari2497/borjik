<?php

namespace App\Support;

/** نرمال‌سازی شماره‌ی موبایل ایران به قالب 09xxxxxxxxx (ارقام فارسی و عربی، +98، 0098 و ... پشتیبانی می‌شود). */
final class MobileNumber
{
    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public static function toLatinDigits(string $value): string
    {
        return strtr($value, self::DIGITS);
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = preg_replace('/[\s\-\(\)]/u', '', self::toLatinDigits(trim($value)));

        if (str_starts_with($v, '+98')) {
            $v = '0'.substr($v, 3);
        } elseif (str_starts_with($v, '0098')) {
            $v = '0'.substr($v, 4);
        } elseif (strlen($v) === 12 && str_starts_with($v, '98')) {
            $v = '0'.substr($v, 2);
        } elseif (strlen($v) === 10 && $v[0] === '9') {
            $v = '0'.$v;
        }

        return preg_match('/^09\d{9}$/', $v) ? $v : null;
    }
}
