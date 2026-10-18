@extends('admin.layout')
@section('title', 'تحلیل رفتار کاربران')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $maxSessions = max(1, collect($trend)->max('sessions'));
  $maxViews = max(1, collect($trend)->max('pageviews'));
  $duration = fn ($seconds) => floor((int) $seconds / 60).' دقیقه '.((int) $seconds % 60).' ثانیه';
  $percent = fn ($part, $whole) => $whole > 0 ? round(($part / $whole) * 100, 1) : 0;
@endphp
<style>
.an-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}.an-head p{max-width:760px;color:var(--muted);font-size:12px;line-height:2;margin:8px 0 0}.an-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.an-period{display:flex;gap:7px;align-items:center}.an-period select{min-width:125px}
.an-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:18px 0}.an-stat{border:1px solid var(--line);border-radius:14px;padding:15px;min-width:0}.an-stat small{display:block;color:var(--muted);font-size:12px;margin-bottom:9px}.an-stat strong{font-size:25px;font-weight:850;letter-spacing:-.3px}.an-stat .an-sub{display:block;color:var(--muted);font-size:11px;margin-top:6px;line-height:1.8}.an-live{display:flex;align-items:center;gap:7px;font-size:12px;color:#16804a;font-weight:800}.an-dot{width:8px;height:8px;border-radius:50%;background:#22a06b;box-shadow:0 0 0 4px rgba(34,160,107,.12)}
.an-panels{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:16px}.an-panel{border:1px solid var(--line);border-radius:15px;padding:15px;min-width:0}.an-panel h3{font-size:15px;margin:0 0 12px}.an-muted{color:var(--muted);font-size:11px;line-height:1.8}.an-table-wrap{overflow:auto}.an-table{width:100%;border-collapse:collapse;font-size:12px}.an-table th,.an-table td{text-align:right;padding:10px 8px;border-bottom:1px solid var(--line);vertical-align:top;white-space:nowrap}.an-table th{color:var(--muted);font-weight:700}.an-table td.wrap{white-space:normal;overflow-wrap:anywhere;min-width:140px}.an-table tr:last-child td{border-bottom:0}
.an-bars{display:flex;align-items:stretch;gap:5px;overflow-x:auto;padding:10px 2px 5px;min-height:150px}.an-bar-day{flex:0 0 28px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:6px;min-height:130px}.an-bar-stack{height:100px;display:flex;align-items:flex-end;gap:2px}.an-bar{width:8px;min-height:2px;border-radius:4px 4px 0 0;background:#287f75}.an-bar.views{background:#8abbb5}.an-bar-day small{font-size:9px;color:var(--muted);writing-mode:vertical-rl;transform:rotate(180deg);height:25px}.an-legend{display:flex;gap:13px;align-items:center;font-size:11px;color:var(--muted)}.an-legend span:before{content:'';display:inline-block;width:8px;height:8px;border-radius:2px;background:#287f75;margin-inline-end:5px}.an-legend .views:before{background:#8abbb5}
.an-meter-list{display:grid;gap:11px}.an-meter-row{display:grid;grid-template-columns:minmax(90px,1.1fr) minmax(60px,2fr) auto;gap:9px;align-items:center;font-size:12px}.an-meter-name{overflow-wrap:anywhere}.an-meter-track{height:7px;background:var(--line);border-radius:10px;overflow:hidden}.an-meter-fill{height:100%;background:#287f75;border-radius:10px}.an-meter-value{color:var(--muted);font-variant-numeric:tabular-nums;white-space:nowrap}
.an-pill{display:inline-flex;align-items:center;padding:4px 8px;border:1px solid var(--line);border-radius:999px;font-size:11px}.an-kicker{font-size:11px;color:var(--muted);margin-top:7px}.an-empty{padding:20px;text-align:center;color:var(--muted);font-size:12px}.an-section{margin-top:24px}.an-section-title{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px}.an-section-title h3{font-size:16px;margin:0}.an-path{direction:ltr;text-align:left;max-width:280px;white-space:normal;overflow-wrap:anywhere}.an-privacy{border-radius:12px;background:var(--soft,var(--line));padding:12px 14px;color:var(--muted);font-size:11px;line-height:2;margin-top:18px}
@media(max-width:1000px){.an-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.an-panels{grid-template-columns:1fr}}@media(max-width:560px){.an-grid{gap:8px}.an-stat{padding:11px}.an-stat strong{font-size:21px}.an-table th,.an-table td{padding:8px 6px}.an-head h2{font-size:19px}}
</style>
<div class="card">
  <div class="an-head">
    <div>
      <h2 style="margin:0">تحلیل رفتار کاربران</h2>
      <p>نمای زنده و گزارش‌های بازدید، تعامل، صفحات، منابع ورودی و مشخصات فنی بازدیدکنندگان سایت. داده‌ها از زمان نصب و اجرای migration جمع‌آوری می‌شوند؛ آمار گذشته به‌صورت خودکار ساخته نمی‌شود.</p>
    </div>
    <div class="an-actions">
      <form class="an-period" method="GET" action="{{ route('admin.analytics.index') }}">
        <label for="an-period" class="an-muted">بازه گزارش</label>
        <select id="an-period" name="period" onchange="this.form.submit()">
          <option value="7" @selected($days === 7)>۷ روز اخیر</option>
          <option value="30" @selected($days === 30)>۳۰ روز اخیر</option>
          <option value="90" @selected($days === 90)>۹۰ روز اخیر</option>
        </select>
      </form>
      <a class="btn sec" href="{{ route('admin.analytics.export', ['period' => $days]) }}">خروجی CSV</a>
      <button type="button" class="btn" onclick="window.location.reload()">↻ تازه‌سازی</button>
    </div>
  </div>

  <div class="an-grid">
    <div class="an-stat"><small>کاربران آنلاین در ۲ دقیقه اخیر</small><div class="an-live"><span class="an-dot"></span><strong>{{ $F::num($stats['online']) }}</strong></div><span class="an-sub">بر اساس آخرین heartbeat؛ مهمان و عضو واردشده</span></div>
    <div class="an-stat"><small>بازدیدکنندگان یکتا</small><strong>{{ $F::num($stats['visitors']) }}</strong><span class="an-sub">شناسهٔ مرورگر اول‌شخص یا نشست</span></div>
    <div class="an-stat"><small>نشست‌ها</small><strong>{{ $F::num($stats['sessions']) }}</strong><span class="an-sub">شروع نشست در بازهٔ انتخاب‌شده</span></div>
    <div class="an-stat"><small>کل بازدید صفحات</small><strong>{{ $F::num($stats['pageviews']) }}</strong><span class="an-sub">Page views</span></div>
    <div class="an-stat"><small>میانگین زمان فعال هر نشست</small><strong style="font-size:19px">{{ $duration($stats['avg_active']) }}</strong><span class="an-sub">فعالیت در تب قابل مشاهده، نه فقط بازماندن صفحه</span></div>
    <div class="an-stat"><small>نرخ پرش</small><strong>{{ $F::num($stats['bounce_rate']) }}٪</strong><span class="an-sub">نشست‌های بدون تعامل؛ تعریف نزدیک به GA4</span></div>
    <div class="an-stat"><small>نرخ تعامل</small><strong>{{ $F::num($stats['engagement_rate']) }}٪</strong><span class="an-sub">نشست‌های دارای تعامل ثبت‌شده</span></div>
    <div class="an-stat"><small>صفحه در هر نشست</small><strong>{{ $F::num($stats['pages_per_session']) }}</strong><span class="an-sub">میانگین تعداد بازدید صفحه</span></div>
    <div class="an-stat"><small>بازدیدکننده جدید</small><strong>{{ $F::num($stats['new_visitors']) }}</strong><span class="an-sub">شناسه‌هایی که سابقه‌ای پیش از این بازه ندارند</span></div>
    <div class="an-stat"><small>بازدیدکننده بازگشتی</small><strong>{{ $F::num($stats['returning_visitors']) }}</strong><span class="an-sub">شناسه‌هایی با نشست قبل از بازه</span></div>
  </div>

  <section class="an-section">
    <div class="an-section-title"><h3>نمای زنده کاربران</h3><span class="an-pill">آخرین ۲ دقیقه · {{ $F::num($liveSessions->count()) }} نشست فعال</span></div>
    @if($liveSessions->isEmpty())
      <div class="an-empty">هنوز نشست فعالی ثبت نشده. با بازدید از صفحات سایت و اجرای heartbeat، کاربران اینجا نمایش داده می‌شوند.</div>
    @else
      <div class="an-table-wrap"><table class="an-table">
        <thead><tr><th>بازدیدکننده</th><th>آخرین صفحه</th><th>منبع / مدیوم</th><th>دستگاه و مرورگر</th><th>بازدید صفحه</th><th>زمان فعال</th><th>آخرین فعالیت</th></tr></thead>
        <tbody>
        @foreach($liveSessions as $session)
          @php $page = $livePageViews->get($session->session_key); @endphp
          <tr>
            <td class="wrap"><strong>{{ $session->user?->display_name ?: 'مهمان / ناشناس' }}</strong><div class="an-muted">{{ $session->user_id ? 'کاربر واردشده · شناسه '.$F::num($session->user_id) : 'بازدیدکننده مهمان' }}</div></td>
            <td class="wrap an-path">{{ $page?->path ?: $session->exit_page ?: '—' }}<div class="an-muted" style="direction:rtl;text-align:right">{{ $page?->title ?: '' }}</div></td>
            <td>{{ $session->source ?: 'direct' }} / {{ $session->medium ?: 'none' }}</td>
            <td>{{ $session->device_type }} · {{ $session->browser }} {{ $session->browser_version }}<div class="an-muted">{{ $session->os }} {{ $session->os_version }} · {{ $session->device_brand }}</div></td>
            <td>{{ $F::num($session->pageviews) }}</td>
            <td>{{ $duration($session->active_seconds) }}</td>
            <td>{{ $session->last_seen_at?->diffForHumans() }}</td>
          </tr>
        @endforeach
        </tbody>
      </table></div>
    @endif
    <p class="an-muted">این بخش تعداد نشست‌های فعال را نشان می‌دهد، نه تضمین حضور فیزیکی فرد؛ heartbeat متوقف‌شده پس از دو دقیقه نشست را از وضعیت آنلاین خارج می‌کند.</p>
  </section>

  <div class="an-panels">
    <section class="an-panel">
      <h3>روند روزانه نشست‌ها و بازدید صفحات</h3>
      <div class="an-legend"><span>نشست‌ها</span><span class="views">بازدید صفحات</span></div>
      <div class="an-bars">
        @foreach($trend as $day)
          <div class="an-bar-day" title="{{ $day['day'] }} · نشست: {{ $day['sessions'] }} · بازدید صفحه: {{ $day['pageviews'] }}">
            <div class="an-bar-stack">
              <div class="an-bar" style="height:{{ max(2, ($day['sessions'] / $maxSessions) * 100) }}px"></div>
              <div class="an-bar views" style="height:{{ max(2, ($day['pageviews'] / $maxViews) * 100) }}px"></div>
            </div>
            <small>{{ $day['label'] }}</small>
          </div>
        @endforeach
      </div>
      <p class="an-muted">برای خوانایی بازه‌های طولانی، نمودار افقی قابل پیمایش است.</p>
    </section>
    <section class="an-panel">
      <h3>منبع و مدیوم ورودی</h3>
      @if($sources->isEmpty())<div class="an-empty">هنوز داده‌ای وجود ندارد.</div>
      @else
        @php $sourceMax = max(1, (int) $sources->max('sessions')); @endphp
        <div class="an-meter-list">
          @foreach($sources as $source)
            <div class="an-meter-row"><div class="an-meter-name">{{ $source->source ?: 'direct' }}<div class="an-muted">{{ $source->medium ?: 'none' }}</div></div><div class="an-meter-track"><div class="an-meter-fill" style="width:{{ ($source->sessions / $sourceMax) * 100 }}%"></div></div><div class="an-meter-value">{{ $F::num($source->sessions) }}</div></div>
          @endforeach
        </div>
      @endif
      <p class="an-muted">ورودی مستقیم، ارجاعی، جست‌وجوی شناسایی‌شده و UTM در صورت وجود تفکیک می‌شوند.</p>
    </section>
  </div>

  <div class="an-panels">
    <section class="an-panel">
      <h3>صفحات پربازدید و زمان فعال</h3>
      @if($topPages->isEmpty())<div class="an-empty">هنوز بازدید صفحه‌ای ثبت نشده است.</div>
      @else
        <div class="an-table-wrap"><table class="an-table"><thead><tr><th>صفحه</th><th>بازدید</th><th>نشست</th><th>میانگین زمان فعال</th></tr></thead><tbody>
        @foreach($topPages as $page)
          <tr><td class="wrap an-path">{{ $page->path }}</td><td>{{ $F::num($page->views) }}</td><td>{{ $F::num($page->sessions) }}</td><td>{{ $duration((int) round($page->avg_active_seconds ?? 0)) }}</td></tr>
        @endforeach
        </tbody></table></div>
      @endif
    </section>
    <section class="an-panel">
      <h3>کمپین‌ها (UTM)</h3>
      @if($campaigns->isEmpty())<div class="an-empty">هنوز utm_campaign ثبت نشده. لینک‌های تبلیغاتی را با پارامترهای UTM بساز.</div>
      @else
        <div class="an-table-wrap"><table class="an-table"><thead><tr><th>کمپین</th><th>Source</th><th>Medium</th><th>نشست</th></tr></thead><tbody>
        @foreach($campaigns as $campaign)
          <tr><td class="wrap">{{ $campaign->campaign }}</td><td>{{ $campaign->source }}</td><td>{{ $campaign->medium }}</td><td>{{ $F::num($campaign->sessions) }}</td></tr>
        @endforeach
        </tbody></table></div>
      @endif
    </section>
  </div>

  <div class="an-panels">
    @foreach([['عنوان'=>'نوع دستگاه','items'=>$devices,'field'=>'device_type'],['عنوان'=>'برند دستگاه','items'=>$brands,'field'=>'device_brand'],['عنوان'=>'مرورگر','items'=>$browsers,'field'=>'browser'],['عنوان'=>'سیستم‌عامل','items'=>$systems,'field'=>'os']] as $group)
      <section class="an-panel">
        <h3>{{ $group['عنوان'] }}</h3>
        @if($group['items']->isEmpty())<div class="an-empty">هنوز داده‌ای وجود ندارد.</div>
        @else
          @php $max = max(1, (int) $group['items']->max('total')); @endphp
          <div class="an-meter-list">
            @foreach($group['items'] as $item)
              <div class="an-meter-row"><div class="an-meter-name">{{ $item->{$group['field']} ?: 'نامشخص' }}</div><div class="an-meter-track"><div class="an-meter-fill" style="width:{{ ($item->total / $max) * 100 }}%"></div></div><div class="an-meter-value">{{ $F::num($item->total) }}</div></div>
            @endforeach
          </div>
        @endif
      </section>
    @endforeach
  </div>

  <div class="an-panels">
    <section class="an-panel">
      <h3>نسخه مرورگر و سیستم‌عامل</h3>
      <div class="an-table-wrap"><table class="an-table"><thead><tr><th>دسته</th><th>نام / نسخه</th><th>نشست</th></tr></thead><tbody>
      @foreach($browserVersions as $item)<tr><td>مرورگر</td><td>{{ $item->browser_version ?: 'نامشخص' }}</td><td>{{ $F::num($item->total) }}</td></tr>@endforeach
      @foreach($systemVersions as $item)<tr><td>سیستم‌عامل</td><td>{{ $item->os_version ?: 'نامشخص' }}</td><td>{{ $F::num($item->total) }}</td></tr>@endforeach
      </tbody></table></div>
      <p class="an-muted">نسخه‌ها از User-Agent استخراج می‌شوند؛ بعضی مرورگرها نسخه یا برند دقیق را پنهان می‌کنند.</p>
    </section>
    <section class="an-panel">
      <h3>رزولوشن نمایشگر (پیکسل)</h3>
      @if($resolutions->isEmpty())<div class="an-empty">رزولوشن بعد از اولین heartbeat مرورگر ثبت می‌شود.</div>
      @else
        @php $resolutionMax = max(1, (int) $resolutions->max()); @endphp
        <div class="an-meter-list">
          @foreach($resolutions as $resolution => $total)
            <div class="an-meter-row"><div class="an-meter-name">{{ $resolution }} px</div><div class="an-meter-track"><div class="an-meter-fill" style="width:{{ ($total / $resolutionMax) * 100 }}%"></div></div><div class="an-meter-value">{{ $F::num($total) }}</div></div>
          @endforeach
        </div>
      @endif
      <p class="an-muted">اندازه viewport و نسبت پیکسل (DPR) نیز برای نشست‌ها ذخیره می‌شود.</p>
    </section>
  </div>

  <div class="an-panels">
    <section class="an-panel">
      <h3>رویدادهای تعاملی</h3>
      @if($events->isEmpty())<div class="an-empty">رویدادی هنوز ثبت نشده است.</div>
      @else
        <div class="an-meter-list">
          @php $eventMax = max(1, (int) $events->max('total')); @endphp
          @foreach($events as $event)
            <div class="an-meter-row"><div class="an-meter-name">{{ $event->event_name }}</div><div class="an-meter-track"><div class="an-meter-fill" style="width:{{ ($event->total / $eventMax) * 100 }}%"></div></div><div class="an-meter-value">{{ $F::num($event->total) }}</div></div>
          @endforeach
        </div>
      @endif
      <p class="an-muted">کلیک روی لینک‌ها، دکمه‌ها و دانلودها ثبت می‌شود؛ متن فرم‌ها، رمزها و مقادیر ورودی ذخیره نمی‌شوند.</p>
    </section>
    <section class="an-panel">
      <h3>یادداشت اندازه‌گیری</h3>
      <ul style="padding-inline-start:20px;font-size:12px;line-height:2.1;margin:0">
        <li>زمان فعال با heartbeat مرورگر و فقط وقتی تب قابل مشاهده و متمرکز است تخمین زده می‌شود.</li>
        <li>نشست آنلاین در صورت دریافت heartbeat طی دو دقیقهٔ اخیر محسوب می‌شود.</li>
        <li>Source و Medium از UTM یا دامنه ارجاع‌دهنده استخراج می‌شوند؛ referrer کامل و query string صفحه ذخیره نمی‌شود.</li>
        <li>این ابزار از تحلیل سمت سرور استفاده می‌کند و به Google Analytics یا سرویس ثالث نیاز ندارد.</li>
      </ul>
    </section>
  </div>

  <div class="an-privacy">حریم خصوصی: IP خام و محتوای فرم‌ها ذخیره نمی‌شوند. شناسه مرورگر برای تشخیص بازدیدکننده بازگشتی در مرورگر اول‌شخص نگه‌داری می‌شود. در صورت نیاز به رضایت کوکی یا سیاست نگه‌داری داده، باید با سیاست حریم خصوصی و مقررات محل استفاده هماهنگ شود.</div>
</div>
@endsection
