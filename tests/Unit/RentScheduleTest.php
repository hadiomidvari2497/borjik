<?php

namespace Tests\Unit;

use App\Support\RentSchedule;
use Morilog\Jalali\CalendarUtils;
use PHPUnit\Framework\TestCase;

/** زمان‌بندی اجاره‌ی ماهانه (بدون دیتابیس): هر ماه شمسی در همان روز ماهِ تاریخ عقد، با رعایت ماه‌های کوتاه و کبیسه. */
class RentScheduleTest extends TestCase
{
    private function g(int $y, int $m, int $d): string
    {
        return sprintf('%04d-%02d-%02d', ...CalendarUtils::toGregorian($y, $m, $d));
    }

    private function j(string $gregorian): string
    {
        [$y, $m, $d] = array_map('intval', explode('-', $gregorian));

        return implode('/', CalendarUtils::toJalali($y, $m, $d));
    }

    public function test_first_invoice_is_on_the_signing_day(): void
    {
        $signed = $this->g(1405, 7, 16);
        $slots = RentSchedule::issueDates($signed, $signed);

        $this->assertCount(1, $slots);
        $this->assertSame($signed, $slots[0]['date']);
        $this->assertSame(1, $slots[0]['seq']);
        $this->assertSame(7, $slots[0]['jm']);
        $this->assertSame([], RentSchedule::issueDates($signed, $this->g(1405, 7, 15)));
    }

    public function test_next_invoice_is_issued_on_the_same_day_of_next_jalali_month(): void
    {
        $signed = $this->g(1405, 7, 16);

        $this->assertCount(1, RentSchedule::issueDates($signed, $this->g(1405, 8, 15)));

        $slots = RentSchedule::issueDates($signed, $this->g(1405, 8, 16));
        $this->assertCount(2, $slots);
        $this->assertSame('1405/8/16', $this->j($slots[1]['date']));
    }

    public function test_it_wraps_the_year(): void
    {
        $slots = RentSchedule::issueDates($this->g(1405, 7, 16), $this->g(1406, 6, 30));

        $this->assertCount(12, $slots);
        $this->assertSame(12, $slots[5]['jm']);
        $this->assertSame(1, $slots[6]['jm']);
        $this->assertSame(1406, $slots[6]['jy']);
        $this->assertSame('1406/6/16', $this->j($slots[11]['date']));
    }

    public function test_no_invoice_on_or_after_the_end_date(): void
    {
        $slots = RentSchedule::issueDates($this->g(1405, 7, 16), $this->g(1406, 12, 29), $this->g(1405, 9, 16));

        $this->assertCount(2, $slots); // مهر و آبان؛ ۱۶ آذر روزِ پایان است و صادر نمی‌شود
        $this->assertSame('1405/8/16', $this->j($slots[1]['date']));
    }

    public function test_day_31_is_clamped_in_shorter_months(): void
    {
        $slots = RentSchedule::issueDates($this->g(1405, 6, 31), $this->g(1406, 1, 31));
        $days = array_map(fn ($s) => implode('/', array_reverse(array_slice(explode('/', $this->j($s['date'])), 1))), $slots);

        // شهریور ۳۱، مهر تا بهمن ۳۰، اسفند ۱۴۰۵ (غیرکبیسه) ۲۹، فروردین ۳۱
        $this->assertSame(['31/6', '30/7', '30/8', '30/9', '30/10', '30/11', '29/12', '31/1'], $days);
    }

    public function test_leap_year_esfand_has_30_days(): void
    {
        $slots = RentSchedule::issueDates($this->g(1403, 11, 30), $this->g(1403, 12, 30));

        $this->assertCount(2, $slots);
        $this->assertSame('1403/12/30', $this->j($slots[1]['date']));
    }

    public function test_slots_are_unique_consecutive_and_increasing(): void
    {
        $slots = RentSchedule::issueDates($this->g(1404, 1, 1), $this->g(1407, 12, 29));

        $this->assertCount(48, $slots);
        $this->assertSame(range(1, 48), array_column($slots, 'seq'));
        $this->assertCount(48, array_unique(array_map(fn ($s) => $s['jy'].'-'.$s['jm'], $slots)));

        $dates = array_column($slots, 'date');
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates);
    }
}
