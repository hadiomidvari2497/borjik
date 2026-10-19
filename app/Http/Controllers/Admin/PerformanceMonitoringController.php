<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PerformanceSample;
use Illuminate\Support\Facades\Artisan;

class PerformanceMonitoringController extends Controller
{
    public function index()
    {
        $latest = PerformanceSample::query()->latest('sampled_at')->first();
        $samples = PerformanceSample::query()->latest('sampled_at')->paginate(30);
        $since = PerformanceSample::query()->where('sampled_at', '>=', now()->subDay())->get();
        $average = [];
        foreach (['database', 'cache', 'storage'] as $key) {
            $values = $since->map(fn ($sample) => data_get($sample->metrics, $key.'.latency_ms'))->filter(fn ($value) => is_numeric($value));
            $average[$key] = $values->isNotEmpty() ? round($values->avg(), 2) : null;
        }

        return view('admin.monitoring.performance', [
            'latest' => $latest,
            'samples' => $samples,
            'average' => $average,
            'sampleCount' => $since->count(),
            'checkedAt' => now(),
        ]);
    }

    public function sampleNow()
    {
        $exit = Artisan::call('borjik:performance-sample');

        return back()->with(
            $exit === 0 ? 'status' : 'error',
            trim(Artisan::output()) ?: 'اندازه‌گیری عملکرد اجرا شد.'
        );
    }
}
