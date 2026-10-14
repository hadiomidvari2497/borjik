<?php

namespace Tests\Unit;

use App\Enums\ChargeMethod as M;
use App\Services\Charge\ChargeCalculator;
use App\Services\Charge\ChargeConfig;
use App\Services\Charge\ChargeException;
use App\Services\Charge\ExpenseLine;
use App\Services\Charge\FixedRule;
use App\Services\Charge\LateFeeCalculator;
use App\Services\Charge\UnitInput;
use PHPUnit\Framework\TestCase;

/** مثال‌های عددی سند نیازمندی‌ها (بند ۴.۱۵) به‌صورت تست. */
class ChargeCalculatorTest extends TestCase
{
    private function u(int $id, float $area, int $persons, string $status = 'sold', ?int $bedrooms = null, bool $parking = false, bool $storage = false): UnitInput
    {
        return new UnitInput($id, (int) round($area * 100), $persons, $status, $bedrooms, $parking, $storage);
    }

    private function calc(ChargeConfig $c, array $units): array
    {
        return (new ChargeCalculator())->calculate($c, $units)->amounts;
    }

    public function test_method1_fixed_rules_add_up(): void
    {
        $rules = [
            new FixedRule(2, null, null, 60000),
            new FixedRule(3, null, null, 80000),
            new FixedRule(null, false, true, 50000),
            new FixedRule(null, true, true, 70000),
        ];
        $c = new ChargeConfig(M::UnitFixed, rules: $rules);
        $r = $this->calc($c, [
            $this->u(1, 80, 2, bedrooms: 2, storage: true),
            $this->u(2, 120, 3, bedrooms: 3, parking: true, storage: true),
            $this->u(3, 80, 2, bedrooms: 2),
        ]);
        $this->assertSame([1 => 110000, 2 => 150000, 3 => 60000], $r);
    }

    public function test_method2_rate_and_ratio(): void
    {
        $this->assertSame([1 => 100000], $this->calc(new ChargeConfig(M::Area, areaRate: 1000), [$this->u(1, 100, 3)]));

        $c = new ChargeConfig(M::Area, mode: 'ratio', totalExpenses: 1000000);
        $this->assertSame([1 => 250000, 2 => 750000], $this->calc($c, [$this->u(1, 100, 1), $this->u(2, 300, 1)]));
    }

    public function test_method3_per_person(): void
    {
        $this->assertSame([1 => 60000], $this->calc(new ChargeConfig(M::PerPerson, personRate: 20000), [$this->u(1, 100, 3)]));
    }

    public function test_method4_rate_and_ratio(): void
    {
        $this->assertSame([1 => 230000], $this->calc(new ChargeConfig(M::PersonArea, personRate: 10000, areaRate: 2000), [$this->u(1, 100, 3)]));

        $units = array_map(fn ($i) => $this->u($i, 100, 3), range(1, 10));
        $c = new ChargeConfig(M::PersonArea, mode: 'ratio', totalExpenses: 1000000);
        $this->assertSame(array_fill_keys(range(1, 10), 100000), $this->calc($c, $units));
    }

    public function test_methods_5_6_8_9(): void
    {
        $unit = [$this->u(1, 100, 3)];
        $this->assertSame([1 => 110000], $this->calc(new ChargeConfig(M::FixedPlusArea, fixedAmount: 50000, areaRate: 600), $unit));
        $this->assertSame([1 => 110000], $this->calc(new ChargeConfig(M::FixedPlusPerson, fixedAmount: 50000, personRate: 20000), $unit));
        $this->assertSame([1 => 130000], $this->calc(new ChargeConfig(M::PersonPlusArea, areaRate: 1000, personRate: 10000), $unit));
        $this->assertSame([1 => 180000], $this->calc(new ChargeConfig(M::FixedAreaPerson, fixedAmount: 50000, areaRate: 1000, personRate: 10000), $unit));
    }

    public function test_method7_sum_equals_total(): void
    {
        $units = array_map(fn ($i) => $this->u($i, 100, 3), range(1, 10));
        $c = new ChargeConfig(M::FixedPlusPersonAreaRatio, fixedAmount: 50000, totalExpenses: 1500000);
        $r = $this->calc($c, $units);
        $this->assertSame(array_fill_keys(range(1, 10), 150000), $r);
        $this->assertSame(1500000, array_sum($r));
    }

    public function test_method10_actual_costs(): void
    {
        $c = new ChargeConfig(M::ActualCosts, expenses: [
            new ExpenseLine(1, 100000, 'persons'),
            new ExpenseLine(2, 200000, 'equal'),
            new ExpenseLine(3, 300000, 'area'),
        ]);
        $r = $this->calc($c, [$this->u(1, 100, 3), $this->u(2, 200, 2)]);
        $this->assertSame([1 => 260000, 2 => 340000], $r);
        $this->assertSame(600000, array_sum($r));
    }

    public function test_units_without_residents_are_skipped_in_person_methods(): void
    {
        $res = (new ChargeCalculator())->calculate(
            new ChargeConfig(M::PerPerson, personRate: 20000),
            [$this->u(1, 100, 3), $this->u(2, 100, 0, 'sold'), $this->u(3, 100, 0, 'vacant')]
        );
        $this->assertSame([1 => 60000, 3 => 0], $res->amounts);
        $this->assertSame([2 => 'missing_residents'], $res->skipped);
    }

    public function test_largest_remainder_keeps_the_total(): void
    {
        $r = $this->calc(new ChargeConfig(M::Area, mode: 'ratio', totalExpenses: 100), [$this->u(1, 10, 1), $this->u(2, 10, 1), $this->u(3, 10, 1)]);
        $this->assertSame(100, array_sum($r));
        $this->assertSame([1 => 34, 2 => 33, 3 => 33], $r);
    }

    public function test_ratio_must_sum_to_100(): void
    {
        $this->expectException(ChargeException::class);
        $this->calc(new ChargeConfig(M::PersonArea, personRatioPct: 30, areaRatioPct: 30), [$this->u(1, 100, 1)]);
    }

    public function test_late_fee(): void
    {
        $this->assertSame(0, LateFeeCalculator::compute(100000, 3, 'percent', 2, null, 5));
        $this->assertSame(2000, LateFeeCalculator::compute(100000, 30, 'percent', 2));       // ۲٪ در ماه
        $this->assertSame(15000, LateFeeCalculator::compute(100000, 10, 'fixed', 5000, 15000)); // سقف
    }
}
