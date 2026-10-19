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

class OperationsCenterController extends Controller
{
    public function index()
    {
        $setting = OperationsSetting::current();
        $values = $setting->values();
        $values['drive_client_secret_set'] = $values['drive_client_secret'] !== '';
        $values['drive_refresh_token_set'] = $values['drive_refresh_token'] !== '';
        unset($values['drive_client_secret'], $values['drive_refresh_token']);
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
            'alert_enabled' => ['nullable', 'boolean'],
            'alert_mobile' => ['nullable', 'string', 'max:30'],
            'disk_warning_percent' => ['required', 'integer', 'min:50', 'max:98'],
        ]);

        $setting = OperationsSetting::current();
        $values = $setting->values();
        foreach (['local_path', 'backup_frequency', 'keep_local', 'drive_client_id', 'drive_folder_id', 'disk_warning_percent'] as $key) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        foreach (['drive_client_secret', 'drive_refresh_token'] as $secret) {
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

    public function runBackup(BackupService $service)
    {
        $run = $service->run();
        return redirect()->route('admin.operations.index')->with(
            $run->status === 'success' ? 'status' : 'error',
            $run->status === 'success' ? 'بکاپ با موفقیت ثبت شد.' : 'بکاپ ناموفق بود: '.($run->message ?? 'خطای نامشخص')
        );
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
