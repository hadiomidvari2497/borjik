<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\Unit;

/**
 * وضعیت مالی واحد ذخیره نمی‌شود و محاسبه می‌شود:
 * مانده = مانده‌ی اولیه + فیش‌های تأییدشده − صورت‌حساب‌های صادرشده (غیر باطل).
 * مثبت = بستانکار، منفی = بدهکار، صفر = تسویه. فیش‌های «در انتظار» حساب نمی‌شوند.
 */
class UnitFinance
{
    /** @param int[] $unitIds @return array<int,int> */
    public function balances(array $unitIds): array
    {
        if (! $unitIds) {
            return [];
        }

        $opening = Unit::whereIn('id', $unitIds)->pluck('opening_balance', 'id');
        $paid = PaymentReceipt::where('status', 'approved')->whereIn('unit_id', $unitIds)->selectRaw('unit_id, sum(amount) as s')->groupBy('unit_id')->pluck('s', 'unit_id');
        $billed = Invoice::where('status', '!=', 'void')->whereIn('unit_id', $unitIds)->selectRaw('unit_id, sum(amount) as s')->groupBy('unit_id')->pluck('s', 'unit_id');

        $out = [];
        foreach ($unitIds as $id) {
            $out[$id] = (int) ($opening[$id] ?? 0) + (int) ($paid[$id] ?? 0) - (int) ($billed[$id] ?? 0);
        }

        return $out;
    }

    public function balance(int $unitId): int
    {
        return $this->balances([$unitId])[$unitId];
    }
}
