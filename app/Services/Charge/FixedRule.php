<?php

namespace App\Services\Charge;

/**
 * یک قاعده‌ی شارژ ثابت (روش ۱). معیارِ null یعنی «مهم نیست».
 * مبلغ همه‌ی قاعده‌هایی که با واحد جور باشند با هم جمع می‌شود (مبلغ پایه + اضافه‌ی انبار/پارکینگ).
 */
final class FixedRule
{
    public function __construct(
        public readonly ?int $bedrooms,
        public readonly ?bool $hasParking,
        public readonly ?bool $hasStorage,
        public readonly int $amount,
    ) {
    }

    public function matches(UnitInput $u): bool
    {
        return ($this->bedrooms === null || $this->bedrooms === $u->bedrooms)
            && ($this->hasParking === null || $this->hasParking === $u->hasParking)
            && ($this->hasStorage === null || $this->hasStorage === $u->hasStorage);
    }
}
