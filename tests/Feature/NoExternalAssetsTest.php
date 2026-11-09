<?php

namespace Tests\Feature;

use Tests\TestCase;

/** سیاست آفلاین: هیچ CSS/JS/فونت/تصویری نباید از اینترنت بارگذاری شود. */
class NoExternalAssetsTest extends TestCase
{
    public function test_views_and_static_assets_do_not_load_external_resources(): void
    {
        $roots = [resource_path('views'), public_path('css'), public_path('js'), public_path('vendor'), public_path('sw.js'), public_path('offline.html')];
        $patterns = [
            '/<(?:link|script|img|source|iframe|video|audio)\b[^>]*\b(?:src|href)\s*=\s*["\']?(?:https?:)?\/\//i',
            '/url\(\s*["\']?(?:https?:)?\/\//i',
            '/@import\s+(?:url\()?\s*["\']?(?:https?:)?\/\//i',
        ];

        $bad = [];
        foreach ($roots as $root) {
            $files = is_dir($root)
                ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS))
                : [new \SplFileInfo($root)];

            foreach ($files as $file) {
                if (! $file->isFile() || ! preg_match('/\.(php|css|js|html)$/', $file->getFilename())) {
                    continue;
                }
                $content = file_get_contents($file->getPathname());
                // Neshan's map SDK is an intentional, narrowly scoped exception: the interactive
                // marketplace map requires its provider-hosted SDK; all other external assets remain forbidden.
                $content = str_replace([
                    'https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.css',
                    'https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.umd.js',
                ], '', $content);
                foreach ($patterns as $p) {
                    if (preg_match($p, $content, $m)) {
                        $bad[] = $file->getPathname().' => '.trim($m[0]);
                    }
                }
            }
        }

        $this->assertSame([], $bad, "منبع خارجی پیدا شد:\n".implode("\n", $bad));
    }
}
