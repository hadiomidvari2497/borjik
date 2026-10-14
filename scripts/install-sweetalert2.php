<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$dir = $root . '/public/vendor/sweetalert2';
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create $dir\n");
    exit(1);
}

function expand_pack(string $packPath, string $dest): void
{
    if (!is_file($packPath)) {
        fwrite(STDERR, "Missing pack: $packPath\n");
        exit(1);
    }
    $b64 = preg_replace('/\s+/', '', (string) file_get_contents($packPath));
    $bin = base64_decode($b64, true);
    if ($bin === false) {
        fwrite(STDERR, "Invalid base64: $packPath\n");
        exit(1);
    }
    $data = gzdecode($bin);
    if ($data === false) {
        fwrite(STDERR, "Invalid gzip: $packPath\n");
        exit(1);
    }
    file_put_contents($dest, $data);
    echo "wrote $dest (" . strlen($data) . " bytes)\n";
}

$cssPack = $dir . '/sweetalert2.min.css.pack';
$jsPack = $dir . '/sweetalert2.all.min.js.pack';

// Assemble JS pack from numbered parts if present
$part0 = $dir . '/sweetalert2.all.min.js.pack.0';
if (is_file($part0)) {
    $parts = [];
    for ($i = 0; $i < 16; $i++) {
        $p = $dir . '/sweetalert2.all.min.js.pack.' . $i;
        if (!is_file($p)) {
            break;
        }
        $parts[] = preg_replace('/\s+/', '', (string) file_get_contents($p));
    }
    file_put_contents($jsPack, implode('', $parts));
    echo 'assembled ' . $jsPack . ' from ' . count($parts) . " parts\n";
}

expand_pack($cssPack, $dir . '/sweetalert2.min.css');
expand_pack($jsPack, $dir . '/sweetalert2.all.min.js');
