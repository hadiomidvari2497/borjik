<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** تم پیش‌فرض «روشن» است؛ تیره فقط با انتخاب صریح کاربر (data-theme="dark") فعال می‌شود، نه با تنظیم سیستم‌عامل. */
class ThemeDefaultTest extends TestCase
{
    private function root(string $path): string
    {
        return dirname(__DIR__, 2).'/'.$path;
    }

    public function test_assets_do_not_follow_os_dark_mode(): void
    {
        $files = array_merge(
            glob($this->root('public/css/*.css')),
            glob($this->root('public/js/{app,admin}.js'), GLOB_BRACE),
        );

        $bad = [];
        foreach ($files as $file) {
            if (str_contains((string) file_get_contents($file), 'prefers-color-scheme')) {
                $bad[] = basename($file);
            }
        }

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root('resources/views'), \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')
                && str_contains((string) file_get_contents($file->getPathname()), 'prefers-color-scheme:dark')) {
                $bad[] = $file->getFilename();
            }
        }

        $this->assertSame([], $bad, "پیروی خودکار از تم تیره‌ی سیستم‌عامل پیدا شد:\n".implode("\n", $bad));
    }

    public function test_root_tokens_are_light_and_dark_is_explicit(): void
    {
        $css = (string) file_get_contents($this->root('public/css/app.css'));

        $this->assertStringContainsString('color-scheme:light', $css);
        $this->assertStringContainsString(':root[data-theme="dark"]{', $css);
        $this->assertStringNotContainsString(':root:not([data-theme="light"])', $css);
    }

    public function test_layouts_default_to_light_unless_dark_is_stored(): void
    {
        foreach (['resources/views/layouts/app.blade.php', 'resources/views/admin/layout.blade.php'] as $layout) {
            $this->assertStringContainsString("localStorage.getItem('borjik-theme')==='dark'?'dark':'light'", (string) file_get_contents($this->root($layout)), $layout);
        }
    }
}
