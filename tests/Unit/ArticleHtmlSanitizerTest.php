<?php

namespace Tests\Unit;

use App\Support\ArticleHtmlSanitizer;
use PHPUnit\Framework\TestCase;

class ArticleHtmlSanitizerTest extends TestCase
{
    public function test_it_keeps_basic_formatting_and_safe_alignment(): void
    {
        $html = ArticleHtmlSanitizer::sanitize('<h2>راهنما</h2><p style="text-align: center" onclick="alert(1)">متن <strong>مهم</strong></p>');

        $this->assertStringContainsString('<h2>راهنما</h2>', $html);
        $this->assertStringContainsString('<strong>مهم</strong>', $html);
        $this->assertStringContainsString('text-align: center;', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_it_removes_scripts_and_unsafe_links(): void
    {
        $html = ArticleHtmlSanitizer::sanitize('<p>امن</p><script>alert(1)</script><a href="javascript:alert(1)">لینک</a>');

        $this->assertStringContainsString('<p>امن</p>', $html);
        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertStringNotContainsString('javascript:', strtolower($html));
        $this->assertStringContainsString('لینک', $html);
    }
}
