<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AnalyticsCollectionController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        if (! Schema::hasTable('analytics_sessions') || ! Schema::hasTable('analytics_page_views')) {
            return response()->json(['ok' => false], 503);
        }

        $data = $request->validate([
            'visitor_id' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9-]+$/'],
            'path' => ['required', 'string', 'max:500', 'regex:/^\/(?!\/)[^?#]*$/'],
            'title' => ['nullable', 'string', 'max:255'],
            'active_seconds' => ['required', 'integer', 'min:0', 'max:30'],
            'screen_width' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'screen_height' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'viewport_width' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'viewport_height' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'pixel_ratio' => ['nullable', 'numeric', 'min:0.25', 'max:10'],
        ]);

        $sessionKey = (string) $request->session()->get('analytics_session_key', '');
        if ($sessionKey === '') {
            return response()->json(['ok' => false], 404);
        }
        $session = AnalyticsSession::query()->where('session_key', $sessionKey)->first();
        if (! $session) {
            return response()->json(['ok' => false], 404);
        }

        $path = mb_substr(rtrim($data['path'], '/') ?: '/', 0, 500);
        $delta = (int) $data['active_seconds'];
        $now = now();
        $session->forceFill([
            'visitor_id' => $data['visitor_id'] ?? $session->visitor_id,
            'user_id' => $request->user()?->getAuthIdentifier() ?? $session->user_id,
            'last_seen_at' => $now,
            'exit_page' => $path,
            'active_seconds' => min(2147483647, $session->active_seconds + $delta),
            'engaged' => $session->engaged || $delta >= 10 || $session->pageviews >= 2,
            'screen_width' => $data['screen_width'] ?? $session->screen_width,
            'screen_height' => $data['screen_height'] ?? $session->screen_height,
            'viewport_width' => $data['viewport_width'] ?? $session->viewport_width,
            'viewport_height' => $data['viewport_height'] ?? $session->viewport_height,
            'pixel_ratio' => $data['pixel_ratio'] ?? $session->pixel_ratio,
        ])->save();

        $view = AnalyticsPageView::query()->where('session_key', $sessionKey)->where('path', $path)->latest('id')->first();
        if ($view) {
            $view->forceFill([
                'title' => isset($data['title']) ? mb_substr($data['title'], 0, 255) : $view->title,
                'active_seconds' => min(2147483647, $view->active_seconds + $delta),
                'duration_seconds' => min(2147483647, $view->active_seconds + $delta),
                'is_engaged' => $view->is_engaged || $delta >= 10 || $session->pageviews >= 2,
            ])->save();
        }

        return response()->json(['ok' => true]);
    }

    public function event(Request $request): JsonResponse
    {
        if (! Schema::hasTable('analytics_events')) {
            return response()->json(['ok' => false], 503);
        }

        $data = $request->validate([
            'visitor_id' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9-]+$/'],
            'event_name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'category' => ['nullable', 'string', 'max:100'],
            'label' => ['nullable', 'string', 'max:190'],
            'path' => ['required', 'string', 'max:500', 'regex:/^\/(?!\/)[^?#]*$/'],
        ]);

        $sessionKey = (string) $request->session()->get('analytics_session_key', '');
        if ($sessionKey === '') {
            return response()->json(['ok' => false], 404);
        }

        AnalyticsEvent::query()->create([
            'session_key' => $sessionKey,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'event_name' => $data['event_name'],
            'category' => $data['category'] ?? null,
            'label' => $data['label'] ?? null,
            'path' => mb_substr(rtrim($data['path'], '/') ?: '/', 0, 500),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
