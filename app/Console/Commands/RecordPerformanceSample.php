<?php

namespace App\Console\Commands;

use App\Models\PerformanceSample;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RecordPerformanceSample extends Command
{
    protected $signature = 'borjik:performance-sample';
    protected $description = 'Measure and record basic application performance indicators';

    public function handle(): int
    {
        $metrics = [];
        $metrics['database'] = $this->measure(fn () => DB::select('SELECT 1'));
        $metrics['cache'] = $this->measure(function () {
            $key = 'performance-sample:'.Str::uuid();
            Cache::put($key, 'ok', 10);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);
            if (! $ok) {
                throw new \RuntimeException('Cache read-after-write check failed.');
            }
        });
        $metrics['storage'] = $this->measure(function () {
            $directory = storage_path('framework');
            if (! is_dir($directory) || ! is_writable($directory)) {
                throw new \RuntimeException('Application storage is not writable.');
            }
            $path = $directory.DIRECTORY_SEPARATOR.'.performance-'.Str::uuid();
            try {
                if (file_put_contents($path, 'borjik-performance-check', LOCK_EX) === false) {
                    throw new \RuntimeException('Storage write failed.');
                }
                if (file_get_contents($path) !== 'borjik-performance-check') {
                    throw new \RuntimeException('Storage read check failed.');
                }
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        });

        $total = @disk_total_space(base_path());
        $free = @disk_free_space(base_path());
        $metrics['disk'] = [
            'status' => $total && $free !== false ? 'ok' : 'unknown',
            'used_percent' => $total && $free !== false ? round((1 - ($free / $total)) * 100, 2) : null,
            'free_bytes' => $free !== false ? $free : null,
            'total_bytes' => $total ?: null,
        ];
        $metrics['runtime'] = [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'memory_used_bytes' => memory_get_usage(true),
            'memory_peak_bytes' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit') ?: null,
            'load_average' => function_exists('sys_getloadavg') ? (sys_getloadavg() ?: null) : null,
            'sampled_from' => app()->environment(),
        ];

        $statuses = collect($metrics)->pluck('status');
        $status = $statuses->contains('fail') ? 'warning' : 'ok';
        PerformanceSample::query()->create([
            'status' => $status,
            'metrics' => $metrics,
            'sampled_at' => now(),
        ]);

        $cutoff = now()->subDays(30);
        PerformanceSample::query()->where('sampled_at', '<', $cutoff)->delete();

        $this->info('Performance baseline recorded: '.$status);
        foreach (['database', 'cache', 'storage'] as $key) {
            $metric = $metrics[$key];
            $this->line($key.': '.($metric['latency_ms'] ?? '—').' ms ('.($metric['status'] ?? 'unknown').')');
        }

        return $status === 'ok' ? self::SUCCESS : self::FAILURE;
    }

    private function measure(callable $probe): array
    {
        $started = hrtime(true);
        try {
            $probe();
            return ['status' => 'ok', 'latency_ms' => round((hrtime(true) - $started) / 1_000_000, 2)];
        } catch (Throwable $exception) {
            report($exception);
            return [
                'status' => 'fail',
                'latency_ms' => round((hrtime(true) - $started) / 1_000_000, 2),
                'error' => mb_substr($exception::class, 0, 150),
            ];
        }
    }
}
