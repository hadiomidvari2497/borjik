<?php

namespace App\Services\Charge;

use App\Enums\ChargeMethod;
use App\Models\ChargeSetting;

final class ChargeConfig
{
    /**
     * @param FixedRule[]   $rules
     * @param ExpenseLine[] $expenses
     * @param int           $roundTo گرد کردن شارژِ روش‌های نرخی به نزدیک‌ترین مضرب (مثلاً ۱۰۰۰). روی روش‌های تقسیمی اعمال نمی‌شود تا جمع حفظ شود.
     */
    public function __construct(
        public readonly ChargeMethod $method,
        public readonly string $mode = 'rate',
        public readonly int $fixedAmount = 0,
        public readonly int $areaRate = 0,
        public readonly int $personRate = 0,
        public readonly int $personRatioPct = 20,
        public readonly int $areaRatioPct = 80,
        public readonly array $rules = [],
        public readonly array $expenses = [],
        public readonly ?int $totalExpenses = null,
        public readonly int $roundTo = 1,
    ) {
    }

    public function expensesTotal(): int
    {
        return $this->totalExpenses ?? array_sum(array_map(fn (ExpenseLine $e) => $e->amount, $this->expenses));
    }

    public static function fromSettings(ChargeSetting $s, array $rules, array $expenses, int $roundTo = 1): self
    {
        return new self(
            $s->method, $s->mode, (int) $s->fixed_amount, (int) $s->area_rate, (int) $s->person_rate,
            (int) $s->person_ratio_pct, (int) $s->area_ratio_pct, $rules, $expenses, null, $roundTo,
        );
    }
}
