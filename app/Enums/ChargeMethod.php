<?php

namespace App\Enums;

/** ۱۰ روش محاسبه‌ی شارژ (بند ۴.۱۵ سند نیازمندی‌ها). */
enum ChargeMethod: int
{
    case UnitFixed = 1;
    case Area = 2;
    case PerPerson = 3;
    case PersonArea = 4;
    case FixedPlusArea = 5;
    case FixedPlusPerson = 6;
    case FixedPlusPersonAreaRatio = 7;
    case PersonPlusArea = 8;
    case FixedAreaPerson = 9;
    case ActualCosts = 10;

    public function label(): string
    {
        return match ($this) {
            self::UnitFixed => 'شارژ واحدی',
            self::Area => 'شارژ متراژی',
            self::PerPerson => 'شارژ نفری',
            self::PersonArea => 'شارژ نفر-متراژی',
            self::FixedPlusArea => 'شارژ واحدی + متراژی',
            self::FixedPlusPerson => 'شارژ واحدی + نفری',
            self::FixedPlusPersonAreaRatio => 'شارژ واحدی + نفر-متراژی (نسبتی)',
            self::PersonPlusArea => 'شارژ نفری + متراژی',
            self::FixedAreaPerson => 'شارژ واحدی + متراژی + نفری',
            self::ActualCosts => 'شارژ بر اساس هزینه‌های واقعی',
        };
    }
}
