<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\SmsLog;
use App\Models\Subscription;
use App\Models\SystemErrorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class SystemDiagnosticsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', Rule::in(['500', '503', '404', '403', '422', 'other'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $queryText = trim((string) ($filters['q'] ?? ''));
        $errors = SystemErrorLog::query()
            ->with(['user:id,first_name,last_name,company_name,person_type', 'admin:id,name,username'])
            ->when($queryText !== '', fn ($query) => $query->where(function ($search) use ($queryText) {
                $like = '%'.addcslashes($queryText, '%_\\\\').'%';
                $search->where('exception_class', 'like', $like)
                    ->orWhere('message', 'like', $like)
                    ->orWhere('path', 'like', $like)
                    ->orWhere('route_name', 'like', $like)
                    ->orWhere('file', 'like', $like)
                    ->orWhere('user_id', $queryText)
                    ->orWhere('admin_user_id', $queryText);
            }))
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                if ($filters['status'] === 'other') {
                    $query->whereNotIn('status_code', [403, 404, 422, 500, 503]);
                } else {
                    $query->where('status_code', (int) $filters['status']);
                }
            })
            ->when(! empty($filters['from']), fn ($query) => $query->where('created_at', '>=', $filters['from'].' 00:00:00'))
            ->when(! empty($filters['to']), fn ($query) => $query->where('created_at', '<=', $filters['to'].' 23:59:59'))
            ->orderByDesc('created_at');

        return view('admin.system-diagnostics.index', [
            'errors' => $errors->paginate(20)->withQueryString(),
            'filters' => array_merge(['q' => '', 'status' => '', 'from' => '', 'to' => ''], $filters),
            'health' => $this->healthChecks(),
            'incidents' => $this->recentIncidents(),
            'stats' => [
                'today' => SystemErrorLog::whereDate('created_at', today())->count(),
                'week' => SystemErrorLog::where('created_at', '>=', now()->subDays(7))->count(),
                'total' => SystemErrorLog::count(),
                'failed_sms' => SmsLog::where('status', 'failed')->where('created_at', '>=', now()->subDay())->count(),
            ],
        ]);
    }

    /**
     * صف رخدادهای قابل اقدام را از لاگ‌های موجود می‌سازد؛ هیچ جدول یا سامانهٔ موازی ایجاد نمی‌کند.
     *
     * @return list<array{title:string, detail:string, source:string, occurred_at:mixed, priority:string, priority_rank:int, url:string}>
     */
    private function recentIncidents(): array
    {
        $incidents = [];
        $add = function (string $title, string $detail, string $source, mixed $time, string $priority, int $rank, string $url) use (&$incidents): void {
            if (! $time) {
                return;
            }
            try {
                $occurredAt = $time instanceof \\DateTimeInterface ? $time : \\Illuminate\\Support\\Carbon::parse($time);
            } catch (Throwable) {
                return;
            }
            $incidents[] = [
                'title' => $title,
                'detail' => $detail,
                'source' => $source,
                'occurred_at' => $occurredAt,
                'timestamp' => $occurredAt->getTimestamp(),
                'priority' => $priority,
                'priority_rank' => $rank,
                'url' => $url,
            ];
        };

        if (Schema::hasTable('system_error_logs')) {
            foreach (DB::table('system_error_logs')->where('created_at', '>=', now()->subDays(7))->orderByDesc('created_at')->limit(12)->get(['id', 'created_at', 'severity', 'category', 'exception_class', 'message', 'status_code']) as $row) {
                $critical = (int) $row->status_code >= 500 || ($row->severity ?? '') === 'error';
                $add('خطای ثبت‌شده: '.class_basename((string) $row->exception_class), \\Illuminate\\Support\\Str::limit((string) $row->message, 180), 'مرکز خطا', $row->created_at, $critical ? 'بالا' : 'متوسط', $critical ? 0 : 2, route('admin.system-diagnostics.index', ['q' => (string) $row->id]));
            }
        }

        if (Schema::hasTable('backup_runs')) {
            foreach (DB::table('backup_runs')->where('status', 'failed')->orderByDesc('id')->limit(10)->get(['id', 'started_at', 'finished_at', 'filename', 'message']) as $row) {
                $add('بکاپ ناموفق', trim((string) ($row->filename ?: 'نسخهٔ پشتیبان')).' — '.\\Illuminate\\Support\\Str::limit((string) ($row->message ?? 'علت در مرکز عملیات بررسی شود.'), 150), 'مرکز عملیات', $row->finished_at ?: $row->started_at, 'بالا', 0, route('admin.operations.index'));
            }
        }

        if (Schema::hasTable('operations_health_checks')) {
            foreach (DB::table('operations_health_checks')->where('status', '!=', 'ok')->orderByDesc('id')->limit(8)->get(['id', 'created_at', 'status']) as $row) {
                $add('بررسی سلامت ناموفق', 'نتیجهٔ بررسی سلامت شماره '.$row->id.' نیازمند بازبینی است.', 'مرکز عملیات', $row->created_at, 'بالا', 1, route('admin.operations.index'));
            }
        }

        if (Schema::hasTable('cron_execution_logs')) {
            foreach (DB::table('cron_execution_logs')->where('status', 'failed')->orderByDesc('id')->limit(10)->get(['id', 'started_at', 'task_name', 'status']) as $row) {
                $add('اجرای Cron ناموفق', 'وظیفه: '.(string) $row->task_name, 'پایش Cron', $row->started_at, 'بالا', 1, route('admin.monitoring.cron', ['status' => 'failed', 'task' => (string) $row->task_name]));
            }
        }

        if (Schema::hasTable('sms_logs')) {
            foreach (DB::table('sms_logs')->where('status', 'failed')->orderByDesc('id')->limit(8)->get(['id', 'created_at', 'template_key', 'error']) as $row) {
                $add('ارسال پیامک ناموفق', (string) ($row->template_key ?: 'پیامک').' — '.\\Illuminate\\Support\\Str::limit((string) ($row->error ?? 'جزئیات در آرشیو پیامک'), 130), 'آرشیو پیامک', $row->created_at, 'متوسط', 2, route('admin.sms.logs.index', ['status' => 'failed']));
            }
        }

        if (Schema::hasTable('subscription_payments')) {
            foreach (DB::table('subscription_payments')->where('status', 'failed')->orderByDesc('id')->limit(8)->get(['id', 'created_at', 'amount', 'gateway', 'ref_id']) as $row) {
                $detail = 'تراکنش #'.$row->id.' · '.number_format((float) $row->amount).' تومان · '.(string) ($row->gateway ?: 'درگاه نامشخص');
                $add('پرداخت اشتراک ناموفق', $detail, 'گزارش پرداخت‌ها', $row->created_at, 'بالا', 1, route('admin.subscription-payments.index', ['status' => 'failed']));
            }
        }

        usort($incidents, fn (array $left, array $right) => ($left['priority_rank'] <=> $right['priority_rank']) ?: ($right['timestamp'] <=> $left['timestamp']));

        return array_slice($incidents, 0, 30);
    }

    private function healthChecks(): array
    {
        $checks = [];
        $checks[] = $this->check('برنامه', 'پیکربندی و اجرای فریم‌ورک', fn () => app()->version() !== '');
        $checks[] = $this->check('پایگاه داده', 'برقراری اتصال و اجرای پرس‌وجوی ساده', function () {
            DB::select('SELECT 1');
            return true;
        });
        $checks[] = $this->check('کش', 'خواندن و نوشتن مقدار آزمایشی', function () {
            $key = 'system-health:'.\Illuminate\Support\Str::uuid();
            Cache::put($key, 'ok', 10);
            $healthy = Cache::get($key) === 'ok';
            Cache::forget($key);
            return $healthy;
        });
        $checks[] = $this->check('فضای ذخیره‌سازی', 'قابل‌نوشتن بودن پوشه ذخیره‌سازی محلی', fn () => is_dir(storage_path('app')) && is_writable(storage_path('app')));
        $checks[] = $this->check('صف پردازش', 'بررسی تنظیمات درایور صف', fn () => (string) config('queue.default') !== '');
        $checks[] = $this->check('ثبت خطا', 'در دسترس بودن جدول تاریخچه خطاها', fn () => DB::getSchemaBuilder()->hasTable('system_error_logs'));

        try {
            $checks[] = [
                'name' => 'پایگاه داده کاربران و ساختمان‌ها',
                'detail' => 'قابل‌خواندن بودن جداول اصلی',
                'status' => (User::query()->limit(1)->count() >= 0 && Building::query()->limit(1)->count() >= 0) ? 'ok' : 'fail',
                'message' => 'جداول اصلی قابل دسترسی هستند.',
            ];
        } catch (Throwable $exception) {
            $checks[] = ['name' => 'پایگاه داده کاربران و ساختمان‌ها', 'detail' => 'قابل‌خواندن بودن جداول اصلی', 'status' => 'fail', 'message' => 'خواندن یکی از جداول اصلی ناموفق بود.'];
        }

        $modules = [
            'ورود و کاربران' => ['users', 'login_otps', 'roles', 'permissions', 'role_permission'],
            'ساختمان و عضویت' => ['buildings', 'building_user_roles', 'invitations', 'blocks', 'units', 'parkings', 'storages'],
            'اشتراک و پرداخت' => ['subscription_plans', 'subscriptions', 'subscription_payments', 'invoices', 'payment_receipts', 'payment_allocations'],
            'درخواست‌ها و تیکت‌ها' => ['tickets', 'ticket_assignees', 'ticket_comments', 'ticket_status_logs', 'ticket_ratings', 'ticket_attachments'],
            'شیفت حراست و ملزومات' => ['security_shifts', 'supplies', 'supply_movements'],
            'رویدادها و نظرسنجی‌ها' => ['events', 'calendar_events', 'polls', 'poll_options', 'poll_votes'],
            'اسناد و فایل‌ها' => ['documents', 'document_roles', 'files', 'file_links', 'contracts'],
            'پیامک و مخاطبان' => ['sms_templates', 'sms_logs', 'sms_providers', 'phonebook_entries'],
            'مدیریت و نظارت' => ['admin_users', 'admin_roles', 'admin_role_permissions', 'admin_user_role', 'admin_activity_logs', 'system_error_logs'],
            'تنظیمات و اعلان‌ها' => ['site_settings', 'notifications', 'sessions', 'cache', 'jobs'],
        ];

        foreach ($modules as $name => $tables) {
            $checks[] = $this->check($name, 'بررسی وجود جداول موردنیاز این بخش', function () use ($tables) {
                foreach ($tables as $table) {
                    if (! Schema::hasTable($table)) {
                        return false;
                    }
                }

                return true;
            });
        }

        return $checks;
    }

    private function check(string $name, string $detail, callable $test): array
    {
        try {
            $healthy = (bool) $test();

            return [
                'name' => $name,
                'detail' => $detail,
                'status' => $healthy ? 'ok' : 'fail',
                'message' => $healthy ? 'بررسی با موفقیت انجام شد.' : 'بررسی ناموفق بود.',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'name' => $name,
                'detail' => $detail,
                'status' => 'fail',
                'message' => 'بررسی ناموفق بود؛ جزئیات در گزارش خطاها قابل پیگیری است.',
            ];
        }
    }
}
