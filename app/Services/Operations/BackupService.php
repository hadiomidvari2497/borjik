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
                $cloudId = $this->uploadToDrive($localPath, $settings);
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

    private function uploadToDrive(string $path, array $settings): string
    {
        foreach (['drive_client_id', 'drive_client_secret', 'drive_refresh_token'] as $key) {
            if (trim((string) ($settings[$key] ?? '')) === '') {
                throw new RuntimeException('برای ارسال به Google Drive باید Client ID، Client Secret و Refresh Token تنظیم شوند.');
            }
        }
        $tokenResponse = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $settings['drive_client_id'],
            'client_secret' => $settings['drive_client_secret'],
            'refresh_token' => $settings['drive_refresh_token'],
            'grant_type' => 'refresh_token',
        ]);
        if (! $tokenResponse->successful() || ! $tokenResponse->json('access_token')) {
            throw new RuntimeException('گرفتن توکن دسترسی Google Drive ناموفق بود؛ اعتبار OAuth را بررسی کن.');
        }

        $metadata = ['name' => basename($path)];
        if (trim((string) ($settings['drive_folder_id'] ?? '')) !== '') {
            $metadata['parents'] = [trim($settings['drive_folder_id'])];
        }
        $size = filesize($path);
        $session = Http::withToken($tokenResponse->json('access_token'))
            ->withHeaders([
                'X-Upload-Content-Type' => 'application/zip',
                'X-Upload-Content-Length' => (string) $size,
            ])->timeout(30)->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id,name,size', $metadata);
        $location = $session->header('Location');
        if (! $session->successful() || ! $location) {
            throw new RuntimeException('شروع نشست بارگذاری Google Drive ناموفق بود (HTTP '.$session->status().').');
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('خواندن فایل بکاپ برای ارسال ابری ناموفق بود.');
        }
        $upload = Http::withToken($tokenResponse->json('access_token'))->timeout(900)
            ->withBody($contents, 'application/zip')
            ->withHeaders(['Content-Length' => (string) strlen($contents)])
            ->put($location);
        if (! $upload->successful() || ! $upload->json('id')) {
            throw new RuntimeException('ارسال فایل به Google Drive ناموفق بود (HTTP '.$upload->status().').');
        }

        return (string) $upload->json('id');
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
            $result = (new HttpSmsGateway($provider))->sendWithResponse((string) $settings['alert_mobile'], $message);
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
