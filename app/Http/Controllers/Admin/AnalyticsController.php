<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(['7', '30', '90'])],
        ]);
        $days = (int) ($filters['period'] ?? 30);
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now();

        $periodSessions = AnalyticsSession::query()->whereBetween('started_at', [$start, $end]);
        $sessionsCount = (clone $periodSessions)->count();
        $pageViewsCount = AnalyticsPageView::query()->whereBetween('viewed_at', [$start, $end])->count();
        $engagedCount = (clone $periodSessions)->where('engaged', true)->count();
        $bounceCount = max(0, $sessionsCount - $engagedCount);
        $visitorKeys = (clone $periodSessions)->get(['visitor_id', 'session_key'])->map(fn ($row) => $row->visitor_id ?: $row->session_key)->unique()->count();
        $knownVisitorIds = (clone $periodSessions)->whereNotNull('visitor_id')->distinct()->pluck('visitor_id');
        $returningCount = $knownVisitorIds->isEmpty() ? 0 : AnalyticsSession::query()
            ->whereNotNull('visitor_id')
            ->where('started_at', '<', $start)
            ->whereIn('visitor_id', $knownVisitorIds)
            ->distinct('visitor_id')->count('visitor_id');
        $newCount = max(0, $visitorKeys - $returningCount);
        $onlineSessions = AnalyticsSession::query()
            ->with('user')
            ->where('last_seen_at', '>=', now()->subMinutes(2))
            ->orderByDesc('last_seen_at')
            ->limit(100)
            ->get();
        $livePageViews = $onlineSessions->isEmpty() ? collect() : AnalyticsPageView::query()
            ->whereIn('session_key', $onlineSessions->pluck('session_key'))
            ->orderByDesc('id')
            ->get()
            ->unique('session_key')
            ->keyBy('session_key');

        $dailySessions = (clone $periodSessions)->selectRaw('DATE(started_at) as day, COUNT(*) as total')
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');
        $dailyViews = AnalyticsPageView::query()->whereBetween('viewed_at', [$start, $end])
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as total')
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');
        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $trend[] = [
                'day' => $day,
                'label' => \App\Support\Fa::date($start->copy()->addDays($i), 'm/d'),
                'sessions' => (int) ($dailySessions[$day]->total ?? 0),
                'pageviews' => (int) ($dailyViews[$day]->total ?? 0),
            ];
        }

        $topUsers = AnalyticsSession::query()->with('user:id,first_name,last_name,company_name,person_type')
            ->whereBetween('started_at', [$start, $end])->whereNotNull('user_id')
            ->select('user_id', DB::raw('SUM(active_seconds) as total_active_seconds'), DB::raw('COUNT(*) as total_sessions'), DB::raw('SUM(pageviews) as total_pageviews'))
            ->groupBy('user_id')->orderByDesc('total_active_seconds')->limit(10)->get();

        $topPages = AnalyticsPageView::query()->whereBetween('viewed_at', [$start, $end])
            ->select('path', DB::raw('COUNT(*) as views'), DB::raw('AVG(active_seconds) as avg_active_seconds'), DB::raw('COUNT(DISTINCT session_key) as sessions'))
            ->groupBy('path')->orderByDesc('views')->limit(12)->get();

        $landingPages = (clone $periodSessions)->whereNotNull('landing_page')
            ->select('landing_page as path', DB::raw('COUNT(*) as sessions'))->groupBy('landing_page')->orderByDesc('sessions')->limit(8)->get();
        $exitPages = (clone $periodSessions)->whereNotNull('exit_page')
            ->select('exit_page as path', DB::raw('COUNT(*) as sessions'))->groupBy('exit_page')->orderByDesc('sessions')->limit(8)->get();

        $sources = (clone $periodSessions)->select('source', 'medium', DB::raw('COUNT(*) as sessions'))
            ->groupBy('source', 'medium')->orderByDesc('sessions')->limit(12)->get();
        $devices = $this->breakdown($periodSessions, 'device_type');
        $brands = $this->breakdown($periodSessions, 'device_brand');
        $browsers = $this->breakdown($periodSessions, 'browser');
        $browserVersions = $this->breakdown($periodSessions, 'browser_version');
        $systems = $this->breakdown($periodSessions, 'os');
        $systemVersions = $this->breakdown($periodSessions, 'os_version');
        $campaigns = (clone $periodSessions)->whereNotNull('campaign')
            ->select('campaign', 'source', 'medium', DB::raw('COUNT(*) as sessions'))
            ->groupBy('campaign', 'source', 'medium')->orderByDesc('sessions')->limit(10)->get();
        $events = AnalyticsEvent::query()->whereBetween('created_at', [$start, $end])
            ->select('event_name', DB::raw('COUNT(*) as total'))->groupBy('event_name')->orderByDesc('total')->limit(10)->get();

        $resolutionCounts = (clone $periodSessions)->whereNotNull('screen_width')->whereNotNull('screen_height')
            ->get(['screen_width', 'screen_height'])->map(fn ($row) => $row->screen_width.' × '.$row->screen_height)
            ->countBy()->sortDesc()->take(10);
        $viewportCounts = (clone $periodSessions)->whereNotNull('viewport_width')->whereNotNull('viewport_height')
            ->get(['viewport_width', 'viewport_height'])->map(fn ($row) => $row->viewport_width.' × '.$row->viewport_height)
            ->countBy()->sortDesc()->take(6);
        $pixelRatios = (clone $periodSessions)->whereNotNull('pixel_ratio')->pluck('pixel_ratio')
            ->map(fn ($ratio) => number_format((float) $ratio, 2).'×')->countBy()->sortDesc()->take(6);

        return view('admin.analytics.index', [
            'days' => $days,
            'stats' => [
                'online' => AnalyticsSession::query()->where('last_seen_at', '>=', now()->subMinutes(2))->count(),
                'sessions' => $sessionsCount,
                'visitors' => $visitorKeys,
                'pageviews' => $pageViewsCount,
                'avg_active' => $sessionsCount > 0 ? (int) round((clone $periodSessions)->avg('active_seconds')) : 0,
                'bounce_rate' => $sessionsCount > 0 ? round(($bounceCount / $sessionsCount) * 100, 1) : 0,
                'engagement_rate' => $sessionsCount > 0 ? round(($engagedCount / $sessionsCount) * 100, 1) : 0,
                'pages_per_session' => $sessionsCount > 0 ? round($pageViewsCount / $sessionsCount, 2) : 0,
                'new_visitors' => $newCount,
                'returning_visitors' => $returningCount,
            ],
            'liveSessions' => $onlineSessions,
            'livePageViews' => $livePageViews,
            'trend' => $trend,
            'topPages' => $topPages,
            'topUsers' => $topUsers,
            'sources' => $sources,
            'landingPages' => $landingPages,
            'exitPages' => $exitPages,
            'devices' => $devices,
            'brands' => $brands,
            'browsers' => $browsers,
            'browserVersions' => $browserVersions,
            'systems' => $systems,
            'systemVersions' => $systemVersions,
            'campaigns' => $campaigns,
            'events' => $events,
            'resolutions' => $resolutionCounts,
            'viewports' => $viewportCounts,
            'pixelRatios' => $pixelRatios,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $request->validate(['period' => ['nullable', Rule::in(['7', '30', '90'])]]);
        $days = (int) ($filters['period'] ?? 30);
        $start = now()->subDays($days - 1)->startOfDay();
        $sessions = AnalyticsSession::query()->with('user')->where('started_at', '>=', $start)->orderByDesc('started_at')->cursor();

        return response()->streamDownload(function () use ($sessions): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['started_at', 'visitor_id', 'user_id', 'user_name', 'source', 'medium', 'campaign', 'landing_page', 'exit_page', 'pageviews', 'active_seconds', 'device_type', 'device_brand', 'browser', 'browser_version', 'os', 'os_version', 'screen_width', 'screen_height']);
            foreach ($sessions as $session) {
                fputcsv($output, [
                    $session->started_at?->toDateTimeString(),
                    $session->visitor_id,
                    $session->user_id,
                    $session->user?->display_name ?? '',
                    $session->source,
                    $session->medium,
                    $session->campaign,
                    $session->landing_page,
                    $session->exit_page,
                    $session->pageviews,
                    $session->active_seconds,
                    $session->device_type,
                    $session->device_brand,
                    $session->browser,
                    $session->browser_version,
                    $session->os,
                    $session->os_version,
                    $session->screen_width,
                    $session->screen_height,
                ]);
            }
            fclose($output);
        }, 'borjik-analytics-'.$days.'-days.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function breakdown($query, string $column)
    {
        $allowed = ['device_type', 'device_brand', 'browser', 'browser_version', 'os', 'os_version'];
        abort_unless(in_array($column, $allowed, true), 400);

        return (clone $query)->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)->orderByDesc('total')->limit(10)->get();
    }
}
