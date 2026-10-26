<?php

namespace App\Services\Operations;

use App\Models\BackupRun;
use App\Models\OperationsSetting;
use App\Models\SmsLog;
use App\Models\SmsProvider;
use App\Services\Sms\HttpSmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class BackupService
{
    public function run(): BackupRun
    {
        $run = BackupRun::create(['status' => 'running', 'started_at' => now()]);
        $tempDir = storage_path('app/private/backup-work/'.uniqid('run-', true));
        $archive = null;

        try {
            $settings = OperationsSetting::current()->values();
            $destination = $this->localDirectory((string) $settings['local_path']);
            if (! is_dir($tempDir) && ! mkdir($tempDir, 0700, true) && ! is_dir($tempDir)) {
                throw new RuntimeException('ساخت پوشهٔ موقت بکاپ ممکن نشد.');
            }

            $stamp = now()->format('Ymd-His');
            $sqlOrDb = $this->exportDatabase($tempDir);
            $archive = $tempDir.DIRECTORY_SEPARATOR.'borjik-'.$stamp.'.zip';
            if (! class_exists(ZipArchive::class)) {
                throw new RuntimeException('افزونه PHP zip فعال نیست؛ پیش از اجرای بکاپ آن را نصب یا فعال کن.');
            }
            $zip = new ZipArchive();
            if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('ساخت فایل ZIP بکاپ ناموفق بود.');
            }
            $zip->addFile($sqlOrDb, basename($sqlOrDb));
            $this->addDirectory($zip, storage_path('app/public'), 'storage/public');
            $this->addDirectory($zip, storage_path('app/private'), 'storage/private', [$tempDir, $destination]);
            $zip->addFromString('backup-manifest.json', json_encode([
                'application' => config('app.name'),
                'created_at' => now()->toIso8601String(),
                'database_driver' => config('database.default'),
                'includes' => ['database', 'storage/app/public', 'storage/app/private'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $zip->close();

            $filename = basename($archive);
            $localPath = $destination.DIRECTORY_SEPARATOR.$filename;
            if (! copy($archive, $localPath)) {
                throw new RuntimeException('ذخیرهٔ بکاپ در مسیر محلی ناموفق بود.');
            }
            @chmod($localPath, 0600);
            $run->update([
                'filename' => $filename,
                'local_path' => $localPath,
                'size_bytes' => filesize($localPath) ?: null,
                'message' => 'نسخهٔ محلی ساخته شد؛ ارسال ابری در حال انجام است.',
            ]);
            $cloudId = null;
            if ($settings['cloud_enabled']) {
                $cloudId = $this->uploadToOneDrive($localPath, $settings);
            }
            $this->prune($destination, max(1, min(365, (int) $settings['keep_local'])));
            $run->update([
                'status' => 'success',
                'filename' => $filename,
                'local_path' => $localPath,
                'cloud_file_id' => $cloudId,
                'size_bytes' => filesize($localPath) ?: null,
                'message' => $settings['cloud_enabled'] ? 'بکاپ محلی و ارسال ابری با موفقیت انجام شد.' : 'بکاپ محلی با موفقیت انجام شد.',
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $run->update([
                'status' => 'failed',
                'message' => mb_substr($exception->getMessage(), 0, 1500),
                'finished_at' => now(),
            ]);
            $this->notifyBackupFailure();
        } finally {
            if (is_dir($tempDir)) {
                $this->removeDirectory($tempDir);
            }
        }

        return $run->refresh();
    }

    private function localDirectory(string $path): string
    {
        $path = trim($path);
        if ($path === '' || ! $this->isAbsolutePath($path)) {
            throw new RuntimeException('مسیر بکاپ باید یک مسیر مطلق و معتبر باشد.');
        }
        $parent = dirname($path);
        if (! is_dir($parent) && ! mkdir($parent, 0700, true) && ! is_dir($parent)) {
            throw new RuntimeException('ساخت پوشهٔ مقصد بکاپ ممکن نشد.');
        }
        if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException('ساخت مسیر بکاپ ناموفق بود.');
        }
        $canonicalPath = realpath($path);
        $public = realpath(public_path());
        if (! $canonicalPath) {
            throw new RuntimeException('مسیر محلی بکاپ قابل تشخیص نیست.');
        }
        if ($public && (str_starts_with(strtolower($canonicalPath), strtolower(rtrim($public, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) || strtolower($canonicalPath) === strtolower($public))) {
            throw new RuntimeException('ذخیرهٔ بکاپ داخل پوشهٔ عمومی سایت مجاز نیست.');
        }
        if (! is_writable($canonicalPath)) {
            throw new RuntimeException('مسیر محلی بکاپ قابل نوشتن نیست.');
        }

        return $canonicalPath;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }

    private function exportDatabase(string $dir): string
    {
        $driver = config('database.default');
        $connection = config('database.connections.'.$driver, []);
        if ($driver === 'sqlite') {
            $database = $connection['database'] ?? null;
            if (! $database || ! is_file($database)) {
                throw new RuntimeException('فایل دیتابیس SQLite پیدا نشد.');
            }
            $target = $dir.DIRECTORY_SEPARATOR.'database.sqlite';
            if (! copy($database, $target)) {
                throw new RuntimeException('کپی دیتابیس SQLite ناموفق بود.');
            }
            return $target;
        }
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('برای این نوع دیتابیس، بکاپ خودکار هنوز پشتیبانی نمی‌شود: '.$driver);
        }

        $binary = $this->findDumpBinary();
        if (! $binary) {
            throw new RuntimeException('ابزار mysqldump پیدا نشد؛ روی Ubuntu بستهٔ mysql-client و روی XAMPP مسیر mysqldump را در PATH قرار بده.');
        }
        $defaults = $dir.DIRECTORY_SEPARATOR.'mysql.cnf';
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $user = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');
        $database = (string) ($connection['database'] ?? '');
        $escaped = fn ($value) => str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '', ''], (string) $value);
        file_put_contents($defaults, "[client]\nhost=\"".$escaped($host)."\"\nport=\"".$escaped($port)."\"\nuser=\"".$escaped($user)."\"\npassword=\"".$escaped($password)."\"\n");
        @chmod($defaults, 0600);
        $target = $dir.DIRECTORY_SEPARATOR.'database.sql';
        $process = new Process([$binary, '--defaults-extra-file='.$defaults, '--single-transaction', '--routines', '--triggers', '--hex-blob', $database]);
        $process->setTimeout(1800);
        $process->run(function ($type, $buffer) use ($target) {
            if ($type === Process::OUT) {
                file_put_contents($target, $buffer, FILE_APPEND);
            }
        });
        @unlink($defaults);
        if (! $process->isSuccessful() || ! is_file($target) || filesize($target) < 10) {
            throw new RuntimeException('خروجی mysqldump ناموفق بود: '.mb_substr($process->getErrorOutput(), 0, 500));
        }

        return $target;
    }

    private function findDumpBinary(): ?string
    {
        $candidates = ['mysqldump', 'mariadb-dump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe', 'C:\\xampp\\mysql\\bin\\mariadb-dump.exe'];
        foreach ($candidates as $candidate) {
            if (str_contains($candidate, DIRECTORY_SEPARATOR) || str_contains($candidate, '\\')) {
                if (is_file($candidate)) {
                    return $candidate;
                }
            } else {
                $process = new Process(['where', $candidate]);
                if (PHP_OS_FAMILY !== 'Windows') {
                    $process = new Process(['sh', '-lc', 'command -v '.escapeshellarg($candidate)]);
                }
                $process->run();
                if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
                    return trim(explode("\n", trim($process->getOutput()))[0]);
                }
            }
        }
        return null;
    }

    private function addDirectory(ZipArchive $zip, string $directory, string $prefix, array $excluded = []): void
    {
        if (! is_dir($directory)) {
            return;
        }
        $base = realpath($directory);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $real = realpath($file->getPathname());
            if (! $real) {
                continue;
            }
            foreach ($excluded as $exclude) {
                $excludeReal = realpath($exclude);
                if ($excludeReal && str_starts_with($real, $excludeReal.DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }
            $relative = ltrim(substr($real, strlen((string) $base)), DIRECTORY_SEPARATOR);
            if (str_contains($relative, DIRECTORY_SEPARATOR.'..')) {
                continue;
            }
            $zip->addFile($real, $prefix.'/'.str_replace(DIRECTORY_SEPARATOR, '/', $relative));
        }
    }

    private function uploadToOneDrive(string $path, array $settings): string
    {
        foreach (['onedrive_client_id', 'onedrive_client_secret', 'onedrive_refresh_token'] as $key) {
            if (trim((string) ($settings[$key] ?? '')) === '') {
                throw new RuntimeException('برای ارسال به OneDrive باید Client ID، Client Secret و اتصال حساب Microsoft تکمیل شوند.');
            }
        }

        $tokenResponse = Http::asForm()->timeout(30)->post('https://login.microsoftonline.com/consumers/oauth2/v2.0/token', [
            'client_id' => $settings['onedrive_client_id'],
            'client_secret' => $settings['onedrive_client_secret'],
            'refresh_token' => $settings['onedrive_refresh_token'],
            'grant_type' => 'refresh_token',
            'scope' => 'offline_access Files.ReadWrite User.Read',
        ]);
        if (! $tokenResponse->successful() || ! $tokenResponse->json('access_token')) {
            throw new RuntimeException('گرفتن توکن OneDrive ناموفق بود؛ اتصال حساب Microsoft را دوباره برقرار کن.');
        }

        $accessToken = (string) $tokenResponse->json('access_token');
        $rotatedRefreshToken = $tokenResponse->json('refresh_token');
        if (is_string($rotatedRefreshToken) && $rotatedRefreshToken !== '') {
            $record = OperationsSetting::current();
            $values = $record->values();
            $values['onedrive_refresh_token'] = $rotatedRefreshToken;
            $record->update(['settings' => $values]);
        }

        $filename = rawurlencode(basename($path));
        $session = Http::withToken($accessToken)->acceptJson()->timeout(30)->post(
            'https://graph.microsoft.com/v1.0/me/drive/root:/'.$filename.':/createUploadSession',
            ['item' => ['@microsoft.graph.conflictBehavior' => 'rename', 'name' => basename($path)]]
        );
        $uploadUrl = $session->json('uploadUrl');
        if (! $session->successful() || ! is_string($uploadUrl) || ! str_starts_with($uploadUrl, 'https://')) {
            throw new RuntimeException('ایجاد نشست آپلود OneDrive ناموفق بود (HTTP '.$session->status().').');
        }

        $size = filesize($path);
        if ($size === false || $size < 1) {
            throw new RuntimeException('اندازه فایل بکاپ برای ارسال به OneDrive معتبر نیست.');
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('بازکردن فایل بکاپ برای ارسال به OneDrive ناموفق بود.');
        }

        try {
            $chunkSize = 5 * 1024 * 1024; // Multiple of 320 KiB, as required by Microsoft Graph.
            $offset = 0;
            $uploaded = null;
            while ($offset < $size) {
                $length = min($chunkSize, $size - $offset);
                if (fseek($handle, $offset) !== 0) {
                    throw new RuntimeException('خواندن بخش بعدی فایل بکاپ ناموفق بود.');
                }
                $chunk = '';
                while (strlen($chunk) < $length && ! feof($handle)) {
                    $piece = fread($handle, min(1024 * 1024, $length - strlen($chunk)));
                    if ($piece === false || $piece === '') {
                        break;
                    }
                    $chunk .= $piece;
                }
                if (strlen($chunk) !== $length) {
                    throw new RuntimeException('خواندن کامل بخش فایل بکاپ ناموفق بود.');
                }

                // The preauthenticated upload URL must not receive the Graph bearer token.
                $response = Http::timeout(300)->withBody($chunk, 'application/octet-stream')->withHeaders([
                    'Content-Length' => (string) $length,
                    'Content-Range' => 'bytes '.$offset.'-'.($offset + $length - 1).'/'.$size,
                ])->put($uploadUrl);

                if (! in_array($response->status(), [200, 201, 202], true)) {
                    throw new RuntimeException('ارسال بخش فایل به OneDrive ناموفق بود (HTTP '.$response->status().').');
                }
                $uploaded = $response->json();
                $offset += $length;
            }
        } finally {
            fclose($handle);
        }

        if (! is_array($uploaded) || empty($uploaded['id'])) {
            throw new RuntimeException('OneDrive آپلود فایل را تأیید نکرد.');
        }

        return (string) $uploaded['id'];
    }

    private function notifyBackupFailure(): void
    {
        try {
            $settings = OperationsSetting::current()->values();
            if (! $settings['alert_enabled'] || ! $settings['alert_mobile']) {
                return;
            }
            $provider = SmsProvider::current();
            $message = 'هشدار صابخونه: ایجاد یا ارسال بکاپ ناموفق بود. مرکز عملیات را بررسی کنید.';
            $log = SmsLog::create([
                'mobile' => $settings['alert_mobile'],
                'template_key' => 'backup_failure',
                'body' => $message,
                'status' => 'queued',
                'gateway' => $provider?->name ?? config('sms.driver', 'log'),
            ]);
            if (! $provider) {
                $log->update(['status' => 'failed', 'error' => 'اتصال پیامکی پیش‌فرض فعال نیست.']);
                return;
            }
            // This template has no placeholders; pass one empty argument so it is not replaced with the full free-form message.
            $result = (new HttpSmsGateway($provider))->sendWithResponse((string) $settings['alert_mobile'], $message, 'backup_failure', ['']);
            $log->update(['status' => 'sent', 'provider_ref' => $result['ref'], 'sent_at' => now()]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function prune(string $directory, int $keep): void
    {
        $files = collect(glob($directory.DIRECTORY_SEPARATOR.'borjik-*.zip') ?: [])
            ->filter('is_file')->sortByDesc(fn ($path) => filemtime($path) ?: 0)->values();
        foreach ($files->slice($keep) as $file) {
            @unlink($file);
        }
    }

    private function removeDirectory(string $directory): void
    {
        foreach (new \FilesystemIterator($directory) as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                $this->removeDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($directory);
    }
}
