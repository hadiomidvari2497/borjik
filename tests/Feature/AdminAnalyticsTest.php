<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_dashboard_shows_sessions_pages_and_traffic_sources(): void
    {
        $admin = AdminUser::create([
            'username' => 'analytics-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);

        AnalyticsSession::query()->create([
            'session_key' => 'analytics-session-1',
            'visitor_id' => 'visitor-test-1',
            'started_at' => now()->subMinutes(5),
            'last_seen_at' => now()->subSeconds(20),
            'landing_page' => '/articles',
            'exit_page' => '/articles',
            'pageviews' => 1,
            'active_seconds' => 25,
            'engaged' => true,
            'source' => 'google.com',
            'medium' => 'organic',
            'device_type' => 'desktop',
            'device_brand' => 'Apple',
            'browser' => 'Chrome',
            'browser_version' => '155.0',
            'os' => 'Windows',
            'os_version' => '10.0',
            'screen_width' => 1920,
            'screen_height' => 1080,
        ]);

        AnalyticsPageView::query()->create([
            'session_key' => 'analytics-session-1',
            'path' => '/articles',
            'title' => 'Articles',
            'viewed_at' => now()->subMinutes(5),
            'active_seconds' => 25,
            'duration_seconds' => 25,
            'is_engaged' => true,
        ]);

        AnalyticsEvent::query()->create([
            'session_key' => 'analytics-session-1',
            'event_name' => 'link_click',
            'category' => 'a',
            'label' => 'Read article',
            'path' => '/articles',
            'created_at' => now()->subMinutes(4),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.analytics.index', ['period' => 7]))
            ->assertOk()
            ->assertSee('تحلیل رفتار کاربران')
            ->assertSee('نمای زنده کاربران')
            ->assertSee('/articles')
            ->assertSee('google.com')
            ->assertSee('organic')
            ->assertSee('Chrome')
            ->assertSee('رزولوشن نمایشگر (پیکسل)')
            ->assertSee('link_click');
    }

    public function test_analytics_dashboard_requires_logs_view_permission(): void
    {
        $admin = AdminUser::create([
            'username' => 'analytics-limited',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => false,
        ]);
        $role = \App\Models\AdminRole::create(['name' => 'analytics-limited-role']);
        $role->syncPermissions(['users.view']);
        $admin->roles()->attach($role->id);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.analytics.index'))
            ->assertForbidden();
    }
}
