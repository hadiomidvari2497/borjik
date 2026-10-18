<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HostMonitoringController extends Controller
{
    public function host()
    {
        $checks = [];
        $checks[] = $this->check('PHP', PHP_VERSION, fn () => version_compare(PHP_VERSION, '8.2.0', '>='));
        $checks[] = $this->check('Laravel', app()->version(), fn () => app()->version() !== '');
        $checks[] = $this->check('پایگاه داده', (string) config('database.default'), function () {
            DB::select('SELECT 1');
            return true;
        });
        $checks[] = $this->check('فضای ذخیره‌سازی', storage_path(), fn () => is_dir(storage_path()) && is_writable(storage_path()));
        $checks[] = $this->check('پوشهٔ فایل‌های برنامه', storage_path('app'), fn () => is_dir(storage_path('app')) && is_writable(storage_path('app')));
        $checks[] = $this->check('کش', (string) config('cache.default'), function () {
            $key = 'host-monitor:'.\Illuminate\Support\Str::uuid();
            Cache::put($key, 'ok', 5);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);
            return $ok;
        });
        $checks[] = $this->check('جدول لاگ زمان‌بندی', 'cron_execution_logs', fn () => Schema::hasTable('cron_execution_logs'));

        $diskPath = base_path();
        $diskTotal = @disk_total_space($diskPath);
        $diskFree = @disk_free_space($diskPath);
        $diskUsedPercent = ($diskTotal && $diskFree !== false) ? round((1 - ($diskFree / $diskTotal)) * 100, 1) : null;
        $memory = $this->memoryInfo();
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
        $uptime = $this->hostUptime();

        return view('admin.monitoring.host', [
            'checks' => $checks,
            'disk' => ['total' => $diskTotal ?: null, 'free' => $diskFree !== false ? $diskFree : null, 'used_percent' => $diskUsedPercent],
            'memory' => $memory,
            'load' => is_array($load) ? $load : null,
            'uptime' => $uptime,
            'server' => [
                'os' => PHP_OS_FAMILY,
                'server' => (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'نامشخص'),
                'sapi' => PHP_SAPI,
                'timezone' => config('app.timezone'),
                'environment' => app()->environment(),
                'php_memory_limit' => ini_get('memory_limit') ?: 'نامشخص',
                'max_execution_time' => ini_get('max_execution_time') ?: 'نامشخص',
                'extensions' => ['openssl' => extension_loaded('openssl'), 'curl' => extension_loaded('curl'), 'mbstring' => extension_loaded('mbstring'), 'pdo' => extension_loaded('pdo')],
            ],
            'checkedAt' => now(),
        ]);
    }

    public function cron(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:success,failed,running'],
            'task' => ['nullable', 'string', 'max:190'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $tableExists = Schema::hasTable('cron_execution_logs');
        $query = $tableExists ? DB::table('cron_execution_logs') : null;
        if ($query) {
            $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when($filters['task'] ?? null, fn ($q, $task) => $q->where('task_name', 'like', '%'.addcslashes($task, '%_\\\\').'%'))
                ->when($filters['from'] ?? null, fn ($q, $date) => $q->where('started_at', '>=', $date.' 00:00:00'))
                ->when($filters['to'] ?? null, fn ($q, $date) => $q->where('started_at', '<=', $date.' 23:59:59'));
        }

        $logs = $query ? $query->orderByDesc('id')->paginate(30)->withQueryString() : null;
        $lastHeartbeat = $tableExists ? DB::table('cron_execution_logs')->where('task_name', 'scheduler:heartbeat')->where('status', 'success')->orderByDesc('finished_at')->value('finished_at') : null;
        $schedulerHealthy = $lastHeartbeat && \Illuminate\Support\Carbon::parse($lastHeartbeat)->greaterThan(now()->subMinutes(3));
        $stats = [
            'total' => $tableExists ? DB::table('cron_execution_logs')->count() : 0,
            'success' => $tableExists ? DB::table('cron_execution_logs')->where('status', 'success')->count() : 0,
            'failed' => $tableExists ? DB::table('cron_execution_logs')->where('status', 'failed')->count() : 0,
            'today' => $tableExists ? DB::table('cron_execution_logs')->where('started_at', '>=', today())->count() : 0,
        ];

        return view('admin.monitoring.cron', [
            'logs' => $logs,
            'filters' => array_merge(['status' => '', 'task' => '', 'from' => '', 'to' => ''], $filters),
            'tableExists' => $tableExists,
            'lastHeartbeat' => $lastHeartbeat,
            'schedulerHealthy' => (bool) $schedulerHealthy,
            'stats' => $stats,
            'checkedAt' => now(),
        ]);
    }

    private function check(string $name, string $detail, callable $test): array
    {
        try {
            $ok = (bool) $test();
            return ['name' => $name, 'detail' => $detail, 'status' => $ok ? 'ok' : 'fail', 'message' => $ok ? 'بررسی با موفقیت انجام شد.' : 'این مورد نیازمند بررسی است.'];
        } catch (Throwable $exception) {
            report($exception);
            return ['name' => $name, 'detail' => $detail, 'status' => 'fail', 'message' => 'بررسی ناموفق بود؛ لاگ خطاها را بررسی کنید.'];
        }
    }

    private function memoryInfo(): ?array
    {
        $path = '/proc/meminfo';
        if (! is_readable($path)) {
            return null;
        }
        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (preg_match('/^(MemTotal|MemAvailable):\s+(\d+)\s+kB$/', $line, $matches)) {
                $values[$matches[1]] = (int) $matches[2] * 1024;
            }
        }
        if (empty($values['MemTotal']) || ! isset($values['MemAvailable'])) {
            return null;
        }
        return ['total' => $values['MemTotal'], 'available' => $values['MemAvailable'], 'used_percent' => round((1 - ($values['MemAvailable'] / $values['MemTotal'])) * 100, 1)];
    }

    private function hostUptime(): ?string
    {
        if (! is_readable('/proc/uptime')) {
            return null;
        }
        $seconds = (int) floor((float) strtok((string) file_get_contents('/proc/uptime'), ' '));
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return ($days ? $days.' روز، ' : '').$hours.' ساعت و '.$minutes.' دقیقه';
    }
}
