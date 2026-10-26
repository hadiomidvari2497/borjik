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
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

class OperationsCenterController extends Controller
{
    public function index()
    {
        $setting = OperationsSetting::current();
        $values = $setting->values();
        $values['onedrive_client_secret_set'] = $values['onedrive_client_secret'] !== '';
        $values['onedrive_refresh_token_set'] = $values['onedrive_refresh_token'] !== '';
        unset($values['onedrive_client_secret'], $values['onedrive_refresh_token'], $values['drive_client_secret'], $values['drive_refresh_token']);
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
            'drive_client_id' => ['nullable', 'string', 'max:500'],
            'drive_client_secret' => ['nullable', 'string', 'max:2000'],
            'drive_refresh_token' => ['nullable', 'string', 'max:3000'],
            'drive_folder_id' => ['nullable', 'string', 'max:500'],
            'cloud_provider' => ['required', 'in:onedrive'],
            'onedrive_client_id' => ['nullable', 'string', 'max:500'],
            'onedrive_client_secret' => ['nullable', 'string', 'max:2000'],
            'alert_enabled' => ['nullable', 'boolean'],
            'alert_mobile' => ['nullable', 'string', 'max:30'],
            'disk_warning_percent' => ['required', 'integer', 'min:50', 'max:98'],
        ]);

        $setting = OperationsSetting::current();
        $values = $setting->values();
        foreach (['local_path', 'backup_frequency', 'keep_local', 'drive_client_id', 'drive_folder_id', 'cloud_provider', 'onedrive_client_id', 'disk_warning_percent'] as $key) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        foreach (['onedrive_client_secret'] as $secret) {
            if (! empty($data[$secret])) {
                $values[$secret] = $data[$secret];
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

    public function connectOneDrive(Request $request)
    {
        $values = OperationsSetting::current()->values();
        if (trim((string) ($values['onedrive_client_id'] ?? '')) === '' || trim((string) ($values['onedrive_client_secret'] ?? '')) === '') {
            return back()->with('error', 'ابتدا Client ID و Client Secret برنامهٔ Microsoft را ذخیره کن.');
        }

        $state = bin2hex(random_bytes(32));
        $request->session()->put('onedrive_oauth_state', $state);
        $redirectUri = route('admin.operations.onedrive.callback');
        $query = http_build_query([
            'client_id' => $values['onedrive_client_id'],
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => 'offline_access Files.ReadWrite User.Read',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect('https://login.microsoftonline.com/consumers/oauth2/v2.0/authorize?'.$query);
    }

    public function oneDriveCallback(Request $request)
    {
        $expectedState = (string) $request->session()->pull('onedrive_oauth_state', '');
        $providedState = (string) $request->query('state', '');
        if ($expectedState === '' || $providedState === '' || ! hash_equals($expectedState, $providedState)) {
            return redirect()->route('admin.operations.index')->with('error', 'اعتبار درخواست اتصال OneDrive تأیید نشد؛ دوباره تلاش کن.');
        }
        if ($request->filled('error')) {
            return redirect()->route('admin.operations.index')->with('error', 'اتصال OneDrive لغو شد یا مایکروسافت خطا داد: '.mb_substr((string) $request->query('error_description', $request->query('error')), 0, 300));
        }
        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('admin.operations.index')->with('error', 'کد تأیید از Microsoft دریافت نشد.');
        }

        $record = OperationsSetting::current();
        $values = $record->values();
        $response = Http::asForm()->timeout(30)->post('https://login.microsoftonline.com/consumers/oauth2/v2.0/token', [
            'client_id' => $values['onedrive_client_id'] ?? '',
            'client_secret' => $values['onedrive_client_secret'] ?? '',
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('admin.operations.onedrive.callback'),
            'scope' => 'offline_access Files.ReadWrite User.Read',
        ]);

        if (! $response->successful() || ! $response->json('refresh_token')) {
            report(new \RuntimeException('Microsoft OneDrive OAuth token exchange failed: HTTP '.$response->status()));
            return redirect()->route('admin.operations.index')->with('error', 'دریافت مجوز پایدار OneDrive ناموفق بود؛ نوع حساب و Redirect URI را در Microsoft Entra بررسی کن.');
        }

        $values['onedrive_refresh_token'] = (string) $response->json('refresh_token');
        $record->update(['settings' => $values]);

        return redirect()->route('admin.operations.index')->with('status', 'حساب OneDrive متصل شد؛ حالا ارسال ابری را فعال و یک بکاپ آزمایشی اجرا کن.');
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
