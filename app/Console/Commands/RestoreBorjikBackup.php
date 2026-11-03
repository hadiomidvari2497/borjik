<?php

namespace App\Console\Commands;

use App\Models\BackupRun;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class RestoreBorjikBackup extends Command
{
    protected $signature = 'borjik:restore {backupRun : شناسه نسخه پشتیبان موفق} {--target-database= : نام دیتابیس خالی مقصد} {--force : تأیید صریح عملیات بازیابی}';
    protected $description = 'Restore a backup into a separate empty MySQL/MariaDB database and stage storage files privately';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('برای جلوگیری از بازیابی ناخواسته، گزینه --force الزامی است.');
            return self::FAILURE;
        }

        $target = trim((string) $this->option('target-database'));
        if ($target === '' || ! preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $target)) {
            $this->error('نام دیتابیس مقصد معتبر نیست؛ فقط حروف انگلیسی، عدد، _ و - مجاز است.');
            return self::FAILURE;
        }

        $run = BackupRun::query()->find($this->argument('backupRun'));
        if (! $run || $run->status !== 'success' || ! $run->local_path || ! is_file($run->local_path) || ! is_readable($run->local_path)) {
            $this->error('نسخه پشتیبان موفق و قابل‌خواندن پیدا نشد.');
            return self::FAILURE;
        }

        $archivePath = realpath((string) $run->local_path);
        $publicPath = realpath(public_path());
        if (! $archivePath || ($publicPath && ($archivePath === $publicPath || str_starts_with(strtolower($archivePath), strtolower(rtrim($publicPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))))) {
            $this->error('مسیر بکاپ معتبر نیست یا داخل پوشه عمومی سایت قرار دارد.');
            return self::FAILURE;
        }
        if ($run->size_bytes && filesize($archivePath) !== (int) $run->size_bytes) {
            $this->error('اندازه فایل بکاپ با مقدار ثبت‌شده مطابقت ندارد.');
            return self::FAILURE;
        }

        $driver = (string) config('database.default');
        $connection = (array) config('database.connections.'.$driver, []);
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->error('بازیابی خودکار فعلاً فقط برای MySQL و MariaDB پشتیبانی می‌شود.');
            return self::FAILURE;
        }
        if (hash_equals((string) ($connection['database'] ?? ''), $target)) {
            $this->error('بازیابی روی دیتابیس فعال برنامه ممنوع است. یک دیتابیس جداگانه و خالی انتخاب کن.');
            return self::FAILURE;
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CHECKCONS) !== true) {
            $this->error('آرشیو بکاپ خراب یا غیرقابل‌خواندن است.');
            return self::FAILURE;
        }

        $work = storage_path('app/private/restore-work/'.uniqid('restore-', true));
        $defaults = $work.DIRECTORY_SEPARATOR.'mysql.cnf';
        try {
            $manifestJson = $zip->getFromName('backup-manifest.json');
            $manifest = $manifestJson === false ? null : json_decode($manifestJson, true);
            if (! is_array($manifest) || ($manifest['database_driver'] ?? null) !== $driver || $zip->locateName('database.sql') === false) {
                throw new \RuntimeException('نوع دیتابیس یا ساختار بکاپ با محیط فعلی سازگار نیست.');
            }

            if (! is_dir($work) && ! mkdir($work, 0700, true) && ! is_dir($work)) {
                throw new \RuntimeException('ساخت پوشه موقت بازیابی ناموفق بود.');
            }
            $binary = $this->findMysqlBinary();
            if (! $binary) {
                throw new \RuntimeException('ابزار mysql client پیدا نشد؛ بسته mysql-client را نصب کن.');
            }

            $escape = fn ($value) => str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '', ''], (string) $value);
            file_put_contents($defaults, "[client]\nhost=\"".$escape($connection['host'] ?? '127.0.0.1')."\"\nport=\"".$escape($connection['port'] ?? 3306)."\"\nuser=\"".$escape($connection['username'] ?? '')."\"\npassword=\"".$escape($connection['password'] ?? '')."\"\n");
            @chmod($defaults, 0600);

            $check = new Process([$binary, '--defaults-extra-file='.$defaults, '--batch', '--skip-column-names', '--execute=SHOW TABLES', $target]);
            $check->setTimeout(60);
            $check->run();
            if (! $check->isSuccessful()) {
                throw new \RuntimeException('اتصال به دیتابیس مقصد ناموفق بود یا دیتابیس وجود ندارد: '.mb_substr($check->getErrorOutput(), 0, 400));
            }
            if (trim($check->getOutput()) !== '') {
                throw new \RuntimeException('دیتابیس مقصد خالی نیست؛ برای جلوگیری از ادغام یا حذف ناخواسته، فقط دیتابیس خالی پذیرفته می‌شود.');
            }

            $sql = $zip->getStream('database.sql');
            if (! is_resource($sql)) {
                throw new \RuntimeException('فایل SQL از آرشیو قابل استخراج نیست.');
            }
            try {
                $restore = new Process([$binary, '--defaults-extra-file='.$defaults, $target]);
                $restore->setTimeout(3600);
                $restore->setInput($sql);
                $restore->run();
                if (! $restore->isSuccessful()) {
                    throw new \RuntimeException('بازیابی دیتابیس ناموفق بود: '.mb_substr($restore->getErrorOutput(), 0, 700));
                }
            } finally {
                fclose($sql);
            }

            $staging = storage_path('app/private/restore-staging/backup-'.$run->id.'-'.now()->format('YmdHis'));
            $this->stageStorageFiles($zip, $staging);
            $this->info('بازیابی دیتابیس در مقصد جداگانه «'.$target.'» انجام شد.');
            $this->line('فایل‌های storage برای بازبینی در مسیر خصوصی زیر آماده شده‌اند: '.$staging.'/storage/app');
            $this->warn('این دستور دیتابیس فعال سایت را تغییر نمی‌دهد. پس از بررسی نسخه آزمایشی، فایل‌ها و تنظیم اتصال محیط مقصد را جداگانه تنظیم کن.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());
            return self::FAILURE;
        } finally {
            @unlink($defaults);
            $zip->close();
            if (is_dir($work)) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($work, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
                    $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
                }
                @rmdir($work);
            }
        }
    }

    private function findMysqlBinary(): ?string
    {
        foreach (['mysql', 'mariadb'] as $candidate) {
            $process = PHP_OS_FAMILY === 'Windows'
                ? new Process(['where', $candidate])
                : new Process(['sh', '-lc', 'command -v '.escapeshellarg($candidate)]);
            $process->run();
            if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
                return trim(explode("\n", trim($process->getOutput()))[0]);
            }
        }
        return null;
    }

    private function stageStorageFiles(ZipArchive $zip, string $staging): void
    {
        $base = $staging.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if (! str_starts_with($name, 'storage/public/') && ! str_starts_with($name, 'storage/private/')) {
                continue;
            }
            $relative = substr($name, strlen('storage/'));
            $parts = explode('/', $relative);
            if (in_array('..', $parts, true) || str_starts_with($relative, '/') || $relative === '') {
                throw new \RuntimeException('مسیر غیرمجاز در آرشیو بکاپ شناسایی شد.');
            }
            $opsys = 0;
            $attributes = 0;
            $zip->getExternalAttributesIndex($i, $opsys, $attributes);
            if ((($attributes >> 16) & 0170000) === 0120000) {
                throw new \RuntimeException('لینک نمادین در فایل بکاپ مجاز نیست.');
            }
            $destination = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $directory = dirname($destination);
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new \RuntimeException('ساخت پوشه فایل‌های بازیابی ناموفق بود.');
            }
            $input = $zip->getStream($name);
            if (! is_resource($input)) {
                throw new \RuntimeException('خواندن فایل '.$name.' از بکاپ ناموفق بود.');
            }
            $output = fopen($destination, 'xb');
            if (! is_resource($output)) {
                fclose($input);
                throw new \RuntimeException('نوشتن فایل بازیابی ناموفق بود.');
            }
            stream_copy_to_stream($input, $output);
            fclose($input);
            fclose($output);
            @chmod($destination, 0600);
        }
    }
}
