<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** صفحه‌های پشتیبانی پنل مدیریت از استایل‌شیت مشترک استفاده می‌کنند و layout جای آن را دارد. */
class AdminSupportStylesTest extends TestCase
{
    public function test_support_pages_push_the_shared_stylesheet(): void
    {
        $root = dirname(__DIR__, 2);

        $this->assertFileExists($root.'/public/css/admin-support.css');
        $this->assertStringContainsString("@stack('styles')", (string) file_get_contents($root.'/resources/views/admin/layout.blade.php'));

        foreach (['index', 'reports', 'show'] as $view) {
            $this->assertStringContainsString(
                "css/admin-support.css",
                (string) file_get_contents($root."/resources/views/admin/support/{$view}.blade.php"),
                $view
            );
        }
    }
}
