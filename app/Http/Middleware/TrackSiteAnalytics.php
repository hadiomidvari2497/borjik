<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackSiteAnalytics
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldTrack($request)) {
            return $next($request);
        }

        $response = $next($request);

        if ($response->getStatusCode() < 400 && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            try {
                if (! Schema::hasTable('analytics_sessions')) {
                    return $response;
                }
                $this->recordPageView($request);
                $content = $response->getContent();
                if (is_string($content) && stripos($content, '</body>') !== false) {
                    $script = $this->trackingScript();
                    $response->setContent((string) preg_replace('/<\/body>/i', $script.'</body>', $content, 1));
                }
            } catch (Throwable $exception) {
                error_log('Site analytics tracking failed: '.$exception::class);
            }
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->is('admin', 'admin/*', 'analytics/*', 'up', 'storage/*', 'build/*', 'vendor/*')) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax() || ! $request->hasSession()) {
            return false;
        }

        return true;
    }

    private function recordPageView(Request $request): void
    {
        $sessionKey = (string) $request->session()->get('analytics_session_key', '');
        $existing = $sessionKey !== '' ? AnalyticsSession::query()->where('session_key', $sessionKey)->first() : null;
        if ($sessionKey === '' || ($existing && $existing->last_seen_at && $existing->last_seen_at->lt(now()->subMinutes(30)))) {
            $sessionKey = (string) Str::uuid();
            $request->session()->put('analytics_session_key', $sessionKey);
            $existing = null;
        }

        $now = now();
        $user = $request->user();
        [$browser, $browserVersion, $os, $osVersion, $deviceType, $brand] = $this->parseAgent((string) $request->userAgent());
        $referrer = (string) $request->headers->get('referer', '');
        $referrerHost = $referrer !== '' ? (string) parse_url($referrer, PHP_URL_HOST) : '';
        if ($referrerHost !== '' && strcasecmp($referrerHost, (string) $request->getHost()) === 0) {
            $referrerHost = '';
        }

        $source = $existing?->source ?: trim((string) $request->query('utm_source', ''));
        $medium = $existing?->medium ?: trim((string) $request->query('utm_medium', ''));
        if ($source === '' || $source === 'direct') {
            if ($referrerHost !== '') {
                $source = $referrerHost;
                $medium = preg_match('/(^|\\.)(google|bing|duckduckgo|yahoo|yandex|baidu|ecosia)\\./i', $referrerHost) ? 'organic' : 'referral';
            } else {
                $source = 'direct';
                $medium = 'none';
            }
        }

        $path = '/'.ltrim($request->path(), '/');
        $path = mb_substr($path === '/' ? '/' : rtrim($path, '/'), 0, 500);

        $session = AnalyticsSession::query()->updateOrCreate(
            ['session_key' => $sessionKey],
            [
                'visitor_id' => $request->cookie('borjik_visitor') ?: ($existing?->visitor_id),
                'user_id' => $user?->getAuthIdentifier(),
                'started_at' => $existing?->started_at ?: $now,
                'last_seen_at' => $now,
                'landing_page' => $existing?->landing_page ?: $path,
                'exit_page' => $path,
                'pageviews' => (int) ($existing?->pageviews ?? 0) + 1,
                'active_seconds' => (int) ($existing?->active_seconds ?? 0),
                'engaged' => (bool) ($existing?->engaged ?? false) || ((int) ($existing?->pageviews ?? 0) + 1 >= 2),
                'source' => mb_substr($existing?->source && $existing->source !== 'direct' ? $existing->source : ($source ?: 'direct'), 0, 190),
                'medium' => mb_substr($existing?->medium && $existing->medium !== 'none' ? $existing->medium : ($medium ?: 'none'), 0, 100),
                'campaign' => $existing?->campaign ?: $this->nullableQuery($request, 'utm_campaign'),
                'term' => $existing?->term ?: $this->nullableQuery($request, 'utm_term'),
                'content' => $existing?->content ?: $this->nullableQuery($request, 'utm_content'),
                'referrer_host' => $existing?->referrer_host ?: ($referrerHost !== '' ? mb_substr($referrerHost, 0, 255) : null),
                'device_type' => $existing && $existing->device_type !== 'unknown' ? $existing->device_type : $deviceType,
                'device_brand' => $existing && $existing->device_brand !== 'unknown' ? $existing->device_brand : $brand,
                'browser' => $existing && $existing->browser !== 'unknown' ? $existing->browser : $browser,
                'browser_version' => $existing?->browser_version ?: $browserVersion,
                'os' => $existing && $existing->os !== 'unknown' ? $existing->os : $os,
                'os_version' => $existing?->os_version ?: $osVersion,
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            ]
        );

        AnalyticsPageView::query()->create([
            'session_key' => $sessionKey,
            'user_id' => $user?->getAuthIdentifier(),
            'path' => $path,
            'referrer_host' => $referrerHost !== '' ? mb_substr($referrerHost, 0, 255) : null,
            'viewed_at' => $now,
        ]);
    }

    private function nullableQuery(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));

        return $value === '' ? null : mb_substr($value, 0, 190);
    }

    /** @return array{string, ?string, string, ?string, string, string} */
    private function parseAgent(string $ua): array
    {
        $browser = 'unknown';
        $browserVersion = null;
        foreach ([
            'Edge' => '/Edg(?:e|A|iOS)?\/([\d.]+)/',
            'Opera' => '/(?:OPR|Opera)\/([\d.]+)/',
            'Samsung Internet' => '/SamsungBrowser\/([\d.]+)/',
            'Chrome' => '/(?:Chrome|CriOS)\/([\d.]+)/',
            'Firefox' => '/(?:Firefox|FxiOS)\/([\d.]+)/',
            'Safari' => '/Version\/([\d.]+).*Safari/',
        ] as $name => $pattern) {
            if (preg_match($pattern, $ua, $match)) {
                $browser = $name;
                $browserVersion = $match[1];
                break;
            }
        }

        $os = 'unknown';
        $osVersion = null;
        if (preg_match('/Windows NT ([\d.]+)/', $ua, $m)) {
            $os = 'Windows';
            $osVersion = $m[1];
        } elseif (preg_match('/Android ([\d.]+)/', $ua, $m)) {
            $os = 'Android';
            $osVersion = $m[1];
        } elseif (preg_match('/(?:iPhone OS|CPU OS) ([\d_]+)/', $ua, $m)) {
            $os = 'iOS';
            $osVersion = str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Mac OS X ([\d_]+)/', $ua, $m)) {
            $os = 'macOS';
            $osVersion = str_replace('_', '.', $m[1]);
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        $deviceType = preg_match('/iPad|Tablet|PlayBook|Silk/i', $ua) || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)
            ? 'tablet'
            : (preg_match('/Mobile|Android|iPhone|iPod/i', $ua) ? 'mobile' : 'desktop');
        $brand = 'unknown';
        foreach (['Samsung' => '/Samsung|SM-[A-Z0-9]+/i', 'Apple' => '/iPhone|iPad|iPod|Macintosh/i', 'Google' => '/Pixel|Google\s?Phone/i', 'Huawei' => '/Huawei|Honor/i', 'Xiaomi' => '/Xiaomi|Redmi|POCO/i', 'OnePlus' => '/OnePlus/i', 'Motorola' => '/Moto\s|Motorola/i'] as $name => $pattern) {
            if (preg_match($pattern, $ua)) {
                $brand = $name;
                break;
            }
        }

        return [$browser, $browserVersion, $os, $osVersion, $deviceType, $brand];
    }

    private function trackingScript(): string
    {
        return <<<'HTML'
<script>
(function(){
  if (window.__borjikAnalyticsLoaded || location.pathname.indexOf('/admin') === 0) return;
  window.__borjikAnalyticsLoaded = true;
  var optOut = false;
  try { optOut = localStorage.getItem('borjik_analytics_opt_out') === '1'; } catch(e) {}
  if (optOut) return;
  var visitor;
  try {
    visitor = localStorage.getItem('borjik_visitor_id');
    if (!visitor) {
      visitor = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : ('v-' + Date.now().toString(36) + Math.random().toString(36).slice(2));
      localStorage.setItem('borjik_visitor_id', visitor);
    }
    document.cookie = 'borjik_visitor=' + encodeURIComponent(visitor) + '; Path=/; Max-Age=31536000; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
  } catch(e) { visitor = ''; }
  var csrf = document.querySelector('meta[name="csrf-token"]');
  var token = csrf ? csrf.content : '';
  var lastBeat = Date.now();
  var visible = function(){ return document.visibilityState === 'visible' && document.hasFocus(); };
  function send(path, data, keepalive) {
    if (!token) return;
    data.visitor_id = visitor || '';
    fetch(path, {method:'POST', credentials:'same-origin', keepalive:!!keepalive, headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token}, body:JSON.stringify(data)}).catch(function(){});
  }
  function beat() {
    var now = Date.now();
    var delta = Math.max(0, Math.min(30, Math.round((now-lastBeat)/1000)));
    lastBeat = now;
    if (!visible()) return;
    send('/analytics/heartbeat', {
      path: location.pathname, title: document.title,
      active_seconds: visible() ? delta : 0,
      screen_width: screen.width, screen_height: screen.height,
      viewport_width: window.innerWidth, viewport_height: window.innerHeight,
      pixel_ratio: window.devicePixelRatio || 1
    });
  }
  window.setInterval(beat, 15000);
  window.addEventListener('focus', function(){lastBeat=Date.now();});
  window.addEventListener('blur', function(){lastBeat=Date.now();});
  document.addEventListener('visibilitychange', function(){lastBeat=Date.now();});
  window.addEventListener('pagehide', function(){
    var now=Date.now(), delta=Math.max(0,Math.min(15,Math.round((now-lastBeat)/1000)));
    send('/analytics/heartbeat', {path:location.pathname,title:document.title,active_seconds:visible()?delta:0,screen_width:screen.width,screen_height:screen.height,viewport_width:innerWidth,viewport_height:innerHeight,pixel_ratio:devicePixelRatio||1}, true);
  });
  document.addEventListener('submit', function(event){
    var form = event.target;
    if (!form || form.tagName !== 'FORM') return;
    send('/analytics/event', {event_name:'form_submit',category:'form',label:(form.getAttribute('aria-label') || form.getAttribute('data-analytics-label') || '').slice(0,120),path:location.pathname});
  }, true);
  document.addEventListener('click', function(event){
    var el = event.target && event.target.closest ? event.target.closest('a,button,[data-analytics-event]') : null;
    if (!el) return;
    var name = el.getAttribute('data-analytics-event') || (el.tagName === 'A' ? (el.hostname && el.hostname !== location.hostname ? 'outbound_click' : 'link_click') : 'button_click');
    if (el.tagName === 'A' && el.hasAttribute('download')) name = 'file_download';
    var label = (el.getAttribute('aria-label') || el.getAttribute('data-analytics-label') || '').slice(0,120);
    send('/analytics/event', {event_name:name,category:el.tagName.toLowerCase(),label:label,path:location.pathname});
  });
})();
</script>
HTML;
    }
}
