<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** همه‌ی بخش‌های «سوالات متداول» صفحه‌های اصلی از partial مشترک (آکاردئون کشویی) استفاده می‌کنند. */
class MarketingFaqAccordionTest extends TestCase
{
    private const PAGES = ['home', 'subscription-plans', 'building-charges', 'building-management', 'charge-management-guide', 'seo-page'];

    public function test_marketing_pages_use_the_shared_faq_partials(): void
    {
        $root = dirname(__DIR__, 2).'/resources/views/marketing/';

        foreach (self::PAGES as $page) {
            $view = (string) file_get_contents($root.$page.'.blade.php');

            $this->assertStringContainsString("@include('marketing.partials.faq-list'", $view, $page);
            $this->assertStringContainsString("@include('marketing.partials.faq-styles')", $view, $page);
            $this->assertStringContainsString("@include('marketing.partials.faq-script')", $view, $page);
            $this->assertStringNotContainsString('<details>', $view, "$page هنوز <details> دستی دارد");
        }
    }

    public function test_script_respects_reduced_motion_and_missing_animate_api(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/marketing/partials/faq-script.blade.php');

        $this->assertStringContainsString('prefers-reduced-motion', $script);
        $this->assertStringContainsString("typeof Element.prototype.animate !== 'function'", $script);
    }
}
