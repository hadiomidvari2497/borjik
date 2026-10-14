<?php

namespace App\Services\Charge;

final class ChargeResult
{
    /**
     * @param array<int,int>    $amounts  شناسه‌ی واحد ← شارژ (تومان)
     * @param array<int,string> $skipped  واحدهای محاسبه‌نشده ← دلیل (مثلاً missing_residents)
     * @param string[]          $warnings
     */
    public function __construct(
        public readonly array $amounts,
        public readonly array $skipped,
        public readonly array $warnings,
        public readonly int $expensesTotal,
    ) {
    }

    public function total(): int
    {
        return array_sum($this->amounts);
    }

    /** اختلاف مجموع شارژها با کل هزینه‌ها (در روش‌های نرخی معمولاً صفر نیست؛ گزارش آن در بند روش ۹ سند آمده). */
    public function difference(): int
    {
        return $this->total() - $this->expensesTotal;
    }
}
