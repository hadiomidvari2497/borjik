<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** اعتبارسنجی کد ملی (شخص حقیقی، ۱۰ رقم) یا شناسه‌ی ملی (شخص حقوقی، ۱۱ رقم) با رقم کنترل. */
class IranianNationalId implements ValidationRule
{
    public function __construct(private readonly string $type = 'real')
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = \App\Support\MobileNumber::toLatinDigits((string) $value);
        $ok = $this->type === 'legal' ? self::isValidLegalId($v) : self::isValidNationalCode($v);

        if (! $ok) {
            $fail($this->type === 'legal' ? 'شناسه‌ی ملی معتبر نیست.' : 'کد ملی معتبر نیست.');
        }
    }

    public static function isValidNationalCode(string $code): bool
    {
        if (! preg_match('/^\d{10}$/', $code) || count(array_unique(str_split($code))) === 1) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }
        $r = $sum % 11;

        return (int) $code[9] === ($r < 2 ? $r : 11 - $r);
    }

    public static function isValidLegalId(string $id): bool
    {
        if (! preg_match('/^\d{11}$/', $id) || count(array_unique(str_split($id))) === 1) {
            return false;
        }
        $d = array_map('intval', str_split($id));
        $ten = $d[9] + 2;
        $coef = [29, 27, 23, 19, 17, 29, 27, 23, 19, 17];
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ($d[$i] + $ten) * $coef[$i];
        }
        $r = $sum % 11;

        return ($r === 10 ? 0 : $r) === $d[10];
    }
}
