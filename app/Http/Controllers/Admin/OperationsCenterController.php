<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupRun;
use App\Models\OperationsHealthCheck;
use App\Models\OperationsSetting;
use App\Models\SmsLog;
use App\Models\SmsProvider;
use App\Services\Operations\BackupService;
use App\Services\Sms\HttpSmsGateway;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

class OperationsCenterController extends Controller
{
    public function index()
    {
        $setting = OperationsSetting::current();
        $values = $setting->values();
        unset($values['onedrive_client_secret'], $values['onedrive_refresh_token'], $values['drive_client_secret'], $values['drive_refresh_token']);
        $rcloneConfig = (string) config('services.rclone.config', '/etc/rclone/sabkhoneh.conf');
        $values['rclone_remote'] = (string) config('services.rclone.remote', 'sabkhoneh-onedrive:Sabkhoneh-Backups');
        $values['rclone_config_path'] = $rcloneConfig;
        $values['rclone_configured'] = $rcloneConfig !== '' && is_file($rcloneConfig) && is_readable($rcloneConfig);
        return view('admin.operations.index', [
            'settings' => $values,
            'backups' => BackupRun::query()->latest('id')->paginate(10, ['*'], 'backups_page'),
            'health' => OperationsHealthCheck::query()->latest('id')->paginate(10, ['*'], 'health_page'),
            'lastBackup' => BackupRun::query()->latest('id')->first(),
            'lastHealth' => OperationsHealthCheck::query()->latest('id')->first(),
            'smsReady' => SmsProvider::current() !== null,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'local_path' => ['required', 'string', 'max:1000'],
            'backup_enabled' => ['nullable', 'boolean'],
            'backup_frequency' => ['required', 'in:twice_daily,daily,weekly'],
            'keep_local' => ['required', 'integer', 'min:1', 'max:365'],
            'cloud_enabled' => ['nullable', 'boolean'],
            'alert_enabled' => ['nullable', 'boolean'],
            'alert_mobile' => ['nullable', 'string', 'max:30'],
            'disk_warning_percent' => ['required', 'integer', 'min:50', 'max:98'],
        ]);

        $setting = OperationsSetting::current();
        $values = $setting->values();
        foreach (['local_path', 'backup_frequency', 'keep_local', 'disk_warning_percent'] as $key) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        foreach (['backup_enabled', 'cloud_enabled', 'alert_enabled'] as $key) {
            $values[$key] = $request->boolean($key);
        }
        $mobile = trim((string) ($data['alert_mobile'] ?? ''));
        if ($mobile !== '') {
            $mobile = MobileNumber::normalize($mobile);
            if (! $mobile) {
                throw ValidationException::withMessages(['alert_mobile' => 'شمارهٔ موبایل معتبر نیست.']);
            }
        }
        $values['alert_mobile'] = $mobile;
        $setting->update(['settings' => $values]);

        return back()->with('status', 'تنظیمات مرکز عملیات ذخیره شد. مقادیر محرمانه به‌صورت رمزنگاری‌شده نگهداری می‌شوند.');
    }

    public function runBackup(BackupService $service)
    {
        $run = $service->run();
        return redirect()->route('admin.operations.index')->with(
            $run->status === 'success' ? 'status' : 'error',
            $run->status === 'success' ? 'بکاپ با موفقیت ثبت شد.' : 'بکاپ ناموفق بود: '.($run->message ?? 'خطای نامشخص')
        );
    }

    /**
     * Rehearsal غیرمخرب: یکپارچگی ZIP و وجود manifest و dump دیتابیس را می‌سنجد.
     * هیچ فایلی استخراج نمی‌شود و دیتابیس فعال هرگز بازنویسی نمی‌شود.
     */
    public function verifyBackup(int $backupRun)
    {
        $run = BackupRun::query()->findOrFail($backupRun);
        $path = (string) $run->local_path;

        try {
            if (! class_exists(ZipArchive::class)) {
                throw new \RuntimeException('افزونه PHP zip فعال نیست.');
            }
            if ($run->status !== 'success' || $path === '' || ! is_file($path) || ! is_readable($path)) {
                throw new \RuntimeException('فایل محلی بکاپ موفق پیدا نشد یا قابل خواندن نیست.');
            }
            $public = realpath(public_path());
            $archivePath = realpath($path);
            if (! $archivePath || ($public && ($archivePath === $public || str_starts_with(strtolower($archivePath), strtolower(rtrim($public, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))))) {
                throw new \RuntimeException('مسیر فایل بکاپ معتبر یا امن نیست.');
            }
            if ($run->size_bytes && filesize($archivePath) !== (int) $run->size_bytes) {
                throw new \RuntimeException('اندازه فایل با اندازه ثبت‌شده هنگام ساخت بکاپ یکسان نیست.');
            }

            $zip = new ZipArchive();
            $opened = $zip->open($archivePath, ZipArchive::CHECKCONS);
            if ($opened !== true) {
                throw new \RuntimeException('ساختار آرشیو ZIP معتبر نیست یا فایل آسیب دیده است.');
            }

            try {
                $manifestJson = $zip->getFromName('backup-manifest.json');
                if ($manifestJson === false) {
                    throw new \RuntimeException('فایل manifest در بکاپ وجود ندارد.');
                }
                $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($manifest) || empty($manifest['created_at']) || empty($manifest['database_driver'])) {
                    throw new \RuntimeException('اطلاعات manifest ناقص یا نامعتبر است.');
                }

                $hasDatabase = $zip->locateName('database.sqlite') !== false || $zip->locateName('database.sql') !== false;
                if (! $hasDatabase) {
                    throw new \RuntimeException('فایل بکاپ دیتابیس در آرشیو پیدا نشد.');
                }

                $verifiedFiles = 0;
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $stat = $zip->statIndex($index);
                    $name = $stat['name'] ?? '';
                    if ($name === '' || str_ends_with($name, '/')) {
                        continue;
                    }
                    $stream = $zip->getStream($name);
                    if (! is_resource($stream)) {
                        throw new \RuntimeException('خواندن یکی از فایل‌های آرشیو ناموفق بود.');
                    }
                    $hash = hash_init('sha256');
                    while (! feof($stream)) {
                        $chunk = fread($stream, 1024 * 1024);
                        if ($chunk === false) {
                            fclose($stream);
                            throw new \RuntimeException('خواندن محتوای یکی از فایل‌های آرشیو ناقص ماند.');
                        }
                        if ($chunk !== '') {
                            hash_update($hash, $chunk);
                        }
                    }
                    fclose($stream);
                    hash_final($hash);
                    $verifiedFiles++;
                }
            } finally {
                $zip->close();
            }

            return back()->with('status', 'آزمایش غیرمخرب بکاپ شماره '.$run->id.' موفق بود: ساختار ZIP، manifest و فایل دیتابیس بررسی شدند و محتوای '.$verifiedFiles.' فایل خوانده شد. هیچ داده‌ای بازیابی یا بازنویسی نشد؛ برای اطمینان نهایی، بازیابی واقعی را در محیط جداگانه انجام بده.');
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', 'آزمایش سلامت بکاپ شماره '.$run->id.' ناموفق بود: '.$exception->getMessage());
        }
    }

    public function checkNow()
    {
        $exit = Artisan::call('borjik:health-check');
        return back()->with($exit === 0 ? 'status' : 'error', trim(Artisan::output()) ?: 'بررسی سلامت اجرا شد.');
    }

    public function testSms(Request $request)
    {
        $data = $request->validate(['mobile' => ['required', 'string', 'max:30']]);
        $mobile = MobileNumber::normalize($data['mobile']);
        if (! $mobile) {
            throw ValidationException::withMessages(['mobile' => 'شمارهٔ موبایل معتبر نیست.']);
        }
        $provider = SmsProvider::current();
        if (! $provider) {
            return back()->with('error', 'ابتدا یک اتصال پیامکی فعال را به‌عنوان پیش‌فرض انتخاب کن.');
        }
        $message = 'تست هشدارهای مرکز عملیات برجیک';
        $log = SmsLog::create(['mobile' => $mobile, 'template_key' => 'operations_alert_test', 'body' => $message, 'status' => 'queued', 'gateway' => $provider->name]);
        try {
            $result = (new HttpSmsGateway($provider))->sendWithResponse($mobile, $message);
            $log->update(['status' => 'sent', 'provider_ref' => $result['ref'], 'sent_at' => now()]);
            return back()->with('status', 'پیامک آزمایشی ارسال شد؛ شناسهٔ پیگیری: '.$result['ref']);
        } catch (Throwable $exception) {
            $log->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 250)]);
            report($exception);
            return back()->with('error', 'ارسال پیامک ناموفق بود. اتصال پیامکی و آرشیو پیامک را بررسی کن.');
        }
    }
}
