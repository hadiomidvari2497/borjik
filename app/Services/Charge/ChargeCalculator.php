<?php

namespace App\Services\Charge;

use App\Enums\ChargeMethod;

/**
 * موتور محاسبه‌ی شارژ (بند ۴.۱۵ سند). کاملاً خالص است: ورودی‌ها آرایه‌ی UnitInput و ChargeConfig، خروجی ChargeResult.
 *
 * - همه‌ی مبالغ عدد صحیح (تومان) هستند.
 * - روش‌های تقسیمی (نسبتی، روش ۷ و ۱۰) با «بزرگ‌ترین باقی‌مانده» پخش می‌شوند تا جمع دقیقاً حفظ شود.
 * - واحدِ فروخته‌شده یا اجاره‌داده‌شده بدون ساکن، در روش‌های نفری محاسبه نمی‌شود و در skipped می‌آید (بند ۴.۱۵).
 */
final class ChargeCalculator
{
    /** @param UnitInput[] $units */
    public function calculate(ChargeConfig $c, array $units): ChargeResult
    {
        if ($c->personRatioPct + $c->areaRatioPct !== 100) {
            throw new ChargeException('مجموع نسبت نفری و متراژی باید ۱۰۰ باشد.');
        }

        $needsPersons = $this->needsPersons($c);
        $active = [];
        $skipped = [];
        foreach ($units as $u) {
            if ($needsPersons && $u->persons <= 0 && in_array($u->status, ['sold', 'rented'], true)) {
                $skipped[$u->id] = 'missing_residents';
                continue;
            }
            $active[$u->id] = $u;
        }

        $warnings = [];
        $ratio = $c->mode === 'ratio';
        $total = $c->expensesTotal();

        $amounts = match ($c->method) {
            ChargeMethod::UnitFixed => $this->each($active, fn (UnitInput $u) => $this->fixedFor($c, $u)),
            ChargeMethod::Area => $ratio
                ? $this->distribute($total, $this->weights($active, 'area'), $warnings)
                : $this->each($active, fn (UnitInput $u) => $this->areaPart($u, $c)),
            ChargeMethod::PerPerson => $this->each($active, fn (UnitInput $u) => $this->personPart($u, $c)),
            ChargeMethod::PersonArea => $ratio
                ? $this->ratioSplit($c, $active, $total, $warnings)
                : $this->each($active, fn (UnitInput $u) => $this->personPart($u, $c) + $this->areaPart($u, $c)),
            ChargeMethod::FixedPlusArea => $this->each($active, fn (UnitInput $u) => $c->fixedAmount + $this->areaPart($u, $c)),
            ChargeMethod::FixedPlusPerson => $this->each($active, fn (UnitInput $u) => $c->fixedAmount + $this->personPart($u, $c)),
            ChargeMethod::FixedPlusPersonAreaRatio => $this->fixedPlusRatio($c, $active, $total, $warnings),
            ChargeMethod::PersonPlusArea => $this->each($active, fn (UnitInput $u) => $this->personPart($u, $c) + $this->areaPart($u, $c)),
            ChargeMethod::FixedAreaPerson => $this->each($active, fn (UnitInput $u) => $c->fixedAmount + $this->areaPart($u, $c) + $this->personPart($u, $c)),
            ChargeMethod::ActualCosts => $this->actualCosts($c, $active, $warnings),
        };

        if ($c->roundTo > 1 && ! $this->distributesTotal($c)) {
            $half = intdiv($c->roundTo, 2);
            $amounts = array_map(fn (int $v) => intdiv($v + $half, $c->roundTo) * $c->roundTo, $amounts);
        }

        return new ChargeResult($amounts, $skipped, $warnings, $total);
    }

    // ---------- بخش‌های نرخی

    private function fixedFor(ChargeConfig $c, UnitInput $u): int
    {
        $sum = 0;
        $matched = false;
        foreach ($c->rules as $rule) {
            if ($rule->matches($u)) {
                $sum += $rule->amount;
                $matched = true;
            }
        }

        return $matched ? $sum : $c->fixedAmount;
    }

    private function areaPart(UnitInput $u, ChargeConfig $c): int
    {
        return intdiv($u->area100 * $c->areaRate + 50, 100); // متراژ × نرخ، گرد به نزدیک‌ترین تومان
    }

    private function personPart(UnitInput $u, ChargeConfig $c): int
    {
        return $u->persons * $c->personRate;
    }

    // ---------- بخش‌های تقسیمی

    private function fixedPlusRatio(ChargeConfig $c, array $units, int $total, array &$warnings): array
    {
        $fixedTotal = $c->fixedAmount * count($units);
        $rest = $total - $fixedTotal;
        if ($rest < 0) {
            $warnings[] = 'fixed_exceeds_total';
            $rest = 0;
        }
        $split = $this->ratioSplit($c, $units, $rest, $warnings);

        return $this->each($units, fn (UnitInput $u) => $c->fixedAmount + $split[$u->id]);
    }

    /** تقسیم یک استخر بین بخش نفری و متراژی با نسبت‌های ن و م؛ اگر یک بخش وزن ندارد سهمش به بخش دیگر می‌رود. */
    private function ratioSplit(ChargeConfig $c, array $units, int $pool, array &$warnings): array
    {
        $personPool = intdiv($pool * $c->personRatioPct, 100);
        $areaPool = $pool - $personPool;
        $pw = $this->weights($units, 'persons');
        $aw = $this->weights($units, 'area');

        if (array_sum($pw) === 0) {
            $areaPool += $personPool;
            $personPool = 0;
        }
        if (array_sum($aw) === 0) {
            $personPool += $areaPool;
            $areaPool = 0;
        }

        $byPerson = $this->distribute($personPool, $pw, $warnings);
        $byArea = $this->distribute($areaPool, $aw, $warnings);
        foreach ($byPerson as $id => $v) {
            $byPerson[$id] = $v + $byArea[$id];
        }

        return $byPerson;
    }

    private function actualCosts(ChargeConfig $c, array $units, array &$warnings): array
    {
        $out = array_fill_keys(array_keys($units), 0);
        foreach ($c->expenses as $line) {
            $part = $this->distribute($line->amount, $this->weights($units, $line->basis, $line->weights), $warnings);
            foreach ($part as $id => $v) {
                $out[$id] += $v;
            }
        }

        return $out;
    }

    /** پخش عدد صحیح به نسبت وزن‌ها با روش بزرگ‌ترین باقی‌مانده؛ جمع خروجی همیشه برابر pool است. */
    private function distribute(int $pool, array $weights, array &$warnings): array
    {
        $out = array_fill_keys(array_keys($weights), 0);
        if ($pool <= 0) {
            return $out;
        }
        $sum = array_sum($weights);
        if ($sum <= 0) {
            $warnings[] = 'undistributed:'.$pool;

            return $out;
        }

        $given = 0;
        $rems = [];
        foreach ($weights as $id => $w) {
            $num = $pool * $w;
            $out[$id] = intdiv($num, $sum);
            $given += $out[$id];
            $rems[] = ['id' => $id, 'rem' => $num % $sum];
        }
        usort($rems, fn ($a, $b) => [$b['rem'], $a['id']] <=> [$a['rem'], $b['id']]);
        for ($i = 0, $left = $pool - $given; $i < $left; $i++) {
            $out[$rems[$i]['id']]++;
        }

        return $out;
    }

    private function weights(array $units, string $basis, ?array $custom = null): array
    {
        $w = [];
        foreach ($units as $id => $u) {
            $w[$id] = match ($basis) {
                'area' => $u->area100,
                'persons' => $u->persons,
                'equal' => 1,
                'custom' => $this->customWeight($custom, $id),
                default => throw new ChargeException("مبنای تقسیم نامعتبر: {$basis}"),
            };
        }

        return $w;
    }

    private function customWeight(?array $custom, int $id): int
    {
        if ($custom === null || ! array_key_exists($id, $custom)) {
            throw new ChargeException("وزن سفارشی برای واحد {$id} تعریف نشده است.");
        }

        return (int) round($custom[$id] * 100);
    }

    // ---------- کمکی

    private function each(array $units, callable $fn): array
    {
        $out = [];
        foreach ($units as $id => $u) {
            $out[$id] = $fn($u);
        }

        return $out;
    }

    private function needsPersons(ChargeConfig $c): bool
    {
        if ($c->method === ChargeMethod::ActualCosts) {
            foreach ($c->expenses as $e) {
                if ($e->basis === 'persons') {
                    return true;
                }
            }

            return false;
        }

        return in_array($c->method, [
            ChargeMethod::PerPerson, ChargeMethod::PersonArea, ChargeMethod::FixedPlusPerson,
            ChargeMethod::FixedPlusPersonAreaRatio, ChargeMethod::PersonPlusArea, ChargeMethod::FixedAreaPerson,
        ], true);
    }

    private function distributesTotal(ChargeConfig $c): bool
    {
        return in_array($c->method, [ChargeMethod::FixedPlusPersonAreaRatio, ChargeMethod::ActualCosts], true)
            || ($c->mode === 'ratio' && in_array($c->method, [ChargeMethod::Area, ChargeMethod::PersonArea], true));
    }
}
