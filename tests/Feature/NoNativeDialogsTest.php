<?php

namespace Tests\Feature;

use Tests\TestCase;

/** سیاست اعلان‌ها: دیالوگ بومی مرورگر ممنوع؛ سؤال‌ها با SweetAlert2 (data-confirm) و پیام‌ها با تاست. */
class NoNativeDialogsTest extends TestCase
{
    public function test_no_native_browser_dialogs_are_used(): void
    {
        $bad = [];
        foreach ($this->files([resource_path('views'), public_path('js')]) as $path) {
            if (str_contains($path, 'icons.blade.php')) {
                continue;
            }
            $content = file_get_contents($path);
            // confirm( / alert( / prompt( که قبلش نقطه یا حرف نباشد (Borjik.confirm و window.confirm مجازند)
            if (preg_match('/(?<![\w.$])(?:confirm|alert|prompt)\s*\(/', $content, $m)) {
                $bad[] = $path.' => '.trim($m[0]);
            }
        }

        $this->assertSame([], $bad, "دیالوگ بومی پیدا شد:\n".implode("\n", $bad));
    }

    public function test_every_delete_form_asks_for_confirmation(): void
    {
        $bad = [];
        $formWithDelete = '/<form\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>(?:\s|@csrf)*@method\(\s*[\'"]DELETE[\'"]\s*\)/s';

        foreach ($this->files([resource_path('views')]) as $path) {
            if (! str_ends_with($path, '.blade.php')) {
                continue;
            }
            if (preg_match_all($formWithDelete, file_get_contents($path), $matches)) {
                foreach ($matches[0] as $tag) {
                    if (! str_contains($tag, 'data-confirm')) {
                        $bad[] = $path.' => '.mb_substr(preg_replace('/\s+/', ' ', $tag), 0, 120);
                    }
                }
            }
        }

        $this->assertSame([], $bad, "فرم حذف بدون data-confirm:\n".implode("\n", $bad));
    }

    /** @return iterable<string> */
    private function files(array $roots): iterable
    {
        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && preg_match('/\.(php|js)$/', $file->getFilename())) {
                    yield $file->getPathname();
                }
            }
        }
    }
}
