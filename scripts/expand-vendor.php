<?php

declare(strict_types=1);

/**
 * Expands base64+gzip packed front-end vendor files into public/vendor.
 * Run: php scripts/expand-vendor.php
 * Also hooked from composer post-install/update.
 */

$root = dirname(__DIR__);

$packs = [
    [
        'pack' => $root.'/public/vendor/sweetalert2/sweetalert2.min.css.pack',
        'dest' => $root.'/public/vendor/sweetalert2/sweetalert2.min.css',
    ],
    [
        'pack' => $root.'/public/vendor/sweetalert2/sweetalert2.all.min.js.pack',
        'dest' => $root.'/public/vendor/sweetalert2/sweetalert2.all.min.js',
    ],
];

foreach ($packs as $item) {
    if (! is_file($item['pack'])) {
        fwrite(STDERR, "Missing pack: {$item['pack']}\n");
        exit(1);
    }
    $raw = base64_decode(trim((string) file_get_contents($item['pack'])), true);
    if ($raw === false) {
        fwrite(STDERR, "Invalid base64: {$item['pack']}\n");
        exit(1);
    }
    $data = gzdecode($raw);
    if ($data === false) {
        fwrite(STDERR, "Invalid gzip: {$item['pack']}\n");
        exit(1);
    }
    $dir = dirname($item['dest']);
    if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
        fwrite(STDERR, "Cannot create {$dir}\n");
        exit(1);
    }
    file_put_contents($item['dest'], $data);
    echo 'wrote '.$item['dest'].' ('.strlen($data)." bytes)\n";
}
