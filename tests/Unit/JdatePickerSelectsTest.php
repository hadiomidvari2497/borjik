<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** selectهای ماه/سال تقویم شمسی نباید توسط select جست‌وجوپذیر (ui.js) دوباره ساخته شوند. */
class JdatePickerSelectsTest extends TestCase
{
    public function test_picker_selects_opt_out_of_searchable_select_enhancer(): void
    {
        $root = dirname(__DIR__, 2).'/public/js/';
        $jdate = (string) file_get_contents($root.'jdate.js');
        $ui = (string) file_get_contents($root.'ui.js');

        $this->assertStringContainsString('<select class="jd-m" data-native-select', $jdate);
        $this->assertStringContainsString('<select class="jd-y" data-native-select', $jdate);
        $this->assertStringContainsString(".closest('.b-select, .jd-pop')", $ui);
    }
}
