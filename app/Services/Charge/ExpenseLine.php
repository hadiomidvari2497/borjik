<?php

namespace App\Services\Charge;

/** یک هزینه‌ی ثبت‌شده برای روش ۱۰. basis: equal / area / persons / custom (با weights: شناسه‌ی واحد ← وزن). */
final class ExpenseLine
{
    public function __construct(
        public readonly int $categoryId,
        public readonly int $amount,
        public readonly string $basis = 'equal',
        public readonly ?array $weights = null,
    ) {
    }
}
