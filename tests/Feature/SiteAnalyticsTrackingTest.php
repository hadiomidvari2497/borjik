<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackSiteAnalytics;
use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SiteAnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_html_page_view_is_recorded_and_tracking_script_is_added(): void
    {
        $session = app('session')->driver();
        $session->setId('analytics-probe-session');
        $session->start();

        $request = Request::create('/analytics-probe?private=not-stored', 'GET');
        $request->setLaravelSession($session);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/125.0.0.0 Safari/537.36');

        $response = new Response('<html><body>Probe</body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        $middleware = new TrackSiteAnalytics();
        $result = $middleware->handle($request, fn () => $response);

        $this->assertDatabaseHas('analytics_sessions', [
            'session_key' => 'analytics-probe-session',
            'landing_page' => '/analytics-probe',
            'browser' => 'Chrome',
            'os' => 'Windows',
        ]);
        $this->assertDatabaseHas('analytics_page_views', [
            'session_key' => 'analytics-probe-session',
            'path' => '/analytics-probe',
        ]);
        $this->assertStringNotContainsString('private=not-stored', (string) $result->getContent());
        $this->assertStringContainsString('/analytics/heartbeat', (string) $result->getContent());
    }

    public function test_admin_pages_are_excluded_from_site_analytics(): void
    {
        $request = Request::create('/admin/analytics', 'GET');
        $session = app('session')->driver();
        $session->setId('analytics-admin-session');
        $session->start();
        $request->setLaravelSession($session);
        $response = new Response('<html><body>Admin</body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);

        $result = (new TrackSiteAnalytics())->handle($request, fn () => $response);

        $this->assertSame('<html><body>Admin</body></html>', $result->getContent());
        $this->assertSame(0, AnalyticsSession::query()->count());
        $this->assertSame(0, AnalyticsPageView::query()->count());
    }
}
