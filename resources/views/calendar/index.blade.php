@extends('layouts.app')
@section('title', 'تقویم ساختمان')
@section('content')
@php
    $F = \App\Support\Fa::class;
    $weekdays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
    $trailing = (7 - (($leading + $days->count()) % 7)) % 7;
@endphp
<style>
.calendar-page{--cal-ink:#183346;--cal-muted:#718392;--cal-line:#e4ecef;--cal-teal:#176b63;color:var(--cal-ink)}
.cal-hero{position:relative;isolation:isolate;overflow:hidden;border-radius:25px;padding:clamp(22px,4vw,34px);margin-bottom:19px;color:#fff;background:radial-gradient(circle at 12% 10%,#a5ead52c,transparent 32%),linear-gradient(125deg,#102f3e,#176b63 58%,#388f80);box-shadow:0 18px 42px #174d4525}
.cal-hero:after{content:"";position:absolute;z-index:-1;width:270px;height:270px;inset-inline-end:-85px;top:-155px;border:1px solid #ffffff2b;border-radius:50%;box-shadow:0 0 0 30px #ffffff09,0 0 0 65px #ffffff08}
.cal-hero-top{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap}
.cal-kicker{display:inline-flex;align-items:center;gap:7px;border:1px solid #ffffff35;background:#ffffff13;padding:6px 11px;border-radius:99px;font-size:11px;color:#dff9f1;margin-bottom:13px}
.cal-hero h2{margin:0 0 7px;color:#fff;font-size:clamp(23px,3vw,32px);font-weight:900;letter-spacing:-.4px}
.cal-hero p{margin:0;max-width:630px;color:#d6eee7;font-size:12px;line-height:2}
.cal-date-card{min-width:140px;padding:14px 19px;text-align:center;border:1px solid #ffffff35;border-radius:17px;background:#ffffff13;backdrop-filter:blur(8px)}
.cal-date-card strong{display:block;font-size:25px;color:#fff}.cal-date-card span{font-size:11px;color:#d6eee7}
.cal-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}
.cal-stat{display:flex;align-items:center;gap:12px;padding:15px;border:1px solid var(--cal-line);border-radius:16px;background:var(--card,#fff);box-shadow:0 6px 18px #173d4b08}
.cal-stat-icon{display:grid;place-items:center;width:41px;height:41px;border-radius:13px;background:#e8f6f1;color:#176b63;font-size:19px;flex-shrink:0}
.cal-stat strong{display:block;font-size:21px;font-weight:900;line-height:1.35}.cal-stat span{font-size:10px;color:var(--cal-muted)}
.cal-layout{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:17px;align-items:start}
.cal-card{min-width:0;border:1px solid var(--cal-line);border-radius:20px;background:var(--card,#fff);overflow:hidden;box-shadow:0 8px 24px #173d4b08}
.cal-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:18px 19px;border-bottom:1px solid var(--cal-line)}
.cal-month{display:flex;align-items:center;gap:12px}.cal-month h3{font-size:19px;font-weight:900;margin:0}.cal-month small{display:block;font-size:10px;color:var(--cal-muted);margin-top:3px}
.cal-nav{display:flex;gap:6px;align-items:center}.cal-nav a,.cal-today{display:inline-flex;align-items:center;justify-content:center;min-width:35px;height:35px;padding:0 10px;border:1px solid var(--cal-line);border-radius:10px;background:var(--card,#fff);color:var(--cal-ink);text-decoration:none;font-size:11px;font-weight:800}
.cal-nav a:hover,.cal-today:hover{border-color:#9ccfc1;background:#f0faf6;color:#176b63}.cal-today{background:#176b63;color:#fff;border-color:#176b63}
.cal-weekdays,.cal-days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr))}
.cal-weekdays{background:#f7faf9;border-bottom:1px solid var(--cal-line)}
.cal-weekdays div{text-align:center;padding:12px 2px;color:#667d89;font-size:10px;font-weight:850}
.cal-day{position:relative;min-height:122px;padding:8px 7px;border-inline-start:1px solid #edf1f2;border-bottom:1px solid #edf1f2;background:var(--card,#fff);min-width:0}
.cal-day:nth-child(7n){border-inline-start:0}.cal-day.is-today{background:linear-gradient(150deg,#f0faf6,#fff 70%)}
.cal-day.is-today .cal-day-number{background:#176b63;color:#fff;box-shadow:0 3px 8px #176b6330}
.cal-day-number{display:grid;place-items:center;width:29px;height:29px;border-radius:10px;font-size:12px;font-weight:850;color:#3c5260;margin-bottom:6px}
.cal-day.is-today .cal-day-number{color:#fff}.cal-day-events{display:grid;gap:4px}
.cal-event{display:block;width:100%;min-width:0;border:0;border-radius:6px;padding:5px 6px;text-align:right;text-decoration:none;font-size:9px;line-height:1.6;font-weight:750;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:default}
a.cal-event{cursor:pointer}.cal-event.teal{background:#e4f5ef;color:#176b63}.cal-event.blue{background:#e9f0ff;color:#345ba7}.cal-event.amber{background:#fff2d9;color:#96620c}.cal-event.rose{background:#ffebeb;color:#ad444c}.cal-event.violet{background:#f1eaff;color:#7550a4}
.cal-more{font-size:9px;color:var(--cal-muted);padding:3px 5px}
.cal-empty{min-height:122px;background:#fafcfc;border-inline-start:1px solid #edf1f2;border-bottom:1px solid #edf1f2}
.cal-legend{display:flex;flex-wrap:wrap;gap:11px;padding:13px 18px;border-top:1px solid var(--cal-line);color:var(--cal-muted);font-size:10px}
.cal-legend span{display:flex;align-items:center;gap:6px}.cal-dot{width:8px;height:8px;border-radius:50%;display:inline-block}.cal-dot.teal{background:#2b9b81}.cal-dot.blue{background:#6288df}.cal-dot.amber{background:#e3a332}.cal-dot.rose{background:#d96b75}.cal-dot.violet{background:#9b78d0}
.cal-side{display:grid;gap:15px}.cal-side-head{padding:17px 18px;border-bottom:1px solid var(--cal-line);display:flex;align-items:center;justify-content:space-between;gap:8px}.cal-side-head h3{font-size:14px;font-weight:900;margin:0}.cal-side-head span{font-size:10px;color:var(--cal-muted)}
.cal-upcoming{padding:4px 14px 13px}.cal-upcoming-item{display:flex;gap:11px;padding:13px 4px;border-bottom:1px solid #edf1f2}.cal-upcoming-item:last-child{border-bottom:0}
.cal-mini-date{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:0 0 43px;height:49px;border-radius:12px;background:#eff7f4;color:#176b63}.cal-mini-date strong{font-size:17px;line-height:1.3}.cal-mini-date small{font-size:9px}
.cal-upcoming-copy{min-width:0;flex:1}.cal-upcoming-copy strong{display:block;font-size:11px;line-height:1.8;overflow-wrap:anywhere}.cal-upcoming-copy small{display:block;color:var(--cal-muted);font-size:9px;margin-top:3px;line-height:1.8}
.cal-type{display:inline-block;margin-top:5px;padding:3px 7px;border-radius:99px;background:#f0f4f6;color:#607481;font-size:9px}
.cal-empty-note{padding:23px 15px;text-align:center;color:var(--cal-muted);font-size:11px;line-height:1.9}
.cal-create{padding:18px}.cal-create summary{display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;list-style:none;font-size:13px;font-weight:900}.cal-create summary::-webkit-details-marker{display:none}.cal-create summary:after{content:"＋";display:grid;place-items:center;width:29px;height:29px;border-radius:10px;background:#e7f6ef;color:#176b63;font-size:18px}
.cal-create[open] summary{padding-bottom:15px;border-bottom:1px solid var(--cal-line);margin-bottom:15px}
.cal-form{display:grid;gap:11px}.cal-field label{display:block;font-size:10px;font-weight:800;color:#4c6170;margin-bottom:6px}.cal-field input,.cal-field select,.cal-field textarea{width:100%;min-width:0;border:1px solid #dce6e9;border-radius:10px;background:var(--card,#fff);color:var(--cal-ink);padding:10px 11px;font:inherit;font-size:11px;outline:none}.cal-field input:focus,.cal-field select:focus,.cal-field textarea:focus{border-color:#68b5a2;box-shadow:0 0 0 3px #329b8116}.cal-two{display:grid;grid-template-columns:1fr 1fr;gap:9px}.cal-check{display:flex;align-items:center;gap:8px;font-size:10px;color:#526977}.cal-check input{accent-color:#176b63}.cal-submit{border:0;border-radius:11px;background:linear-gradient(120deg,#176b63,#2b9280);color:#fff;padding:12px;font:inherit;font-size:11px;font-weight:850;cursor:pointer}.cal-status{padding:11px 13px;border-radius:12px;background:#eaf8f1;color:#176b63;font-size:11px;margin-bottom:15px}.cal-errors{padding:11px 13px;border-radius:12px;background:#fff1f0;color:#a43e3e;font-size:11px;line-height:1.9;margin-bottom:15px}
.cal-view-switch{display:flex;gap:4px;padding:4px;border-radius:11px;background:#f1f6f5}.cal-view-switch a{padding:7px 10px;border-radius:8px;color:var(--cal-muted);text-decoration:none;font-size:10px;font-weight:850}.cal-view-switch a.active{background:#fff;color:#176b63;box-shadow:0 2px 7px #173d4b10}.cal-days.mode-day{grid-template-columns:minmax(0,1fr)}.cal-days.mode-day .cal-day{min-height:330px;padding:15px}.cal-days.mode-week .cal-day{min-height:230px}.cal-days.mode-week .cal-day-number{margin-bottom:10px}.cal-day.is-drop-target{outline:2px dashed #2b9280;outline-offset:-4px;background:#e8f8f1}.cal-event[draggable=true]{cursor:grab}.cal-event[draggable=true]:active{cursor:grabbing;opacity:.65}.cal-day-label{display:block;font-size:9px;color:var(--cal-muted);margin-top:1px}.cal-filters{display:flex;gap:6px;flex-wrap:wrap;padding:12px 18px;border-bottom:1px solid var(--cal-line)}.cal-filter{display:inline-flex;padding:7px 11px;border:1px solid var(--cal-line);border-radius:9px;text-decoration:none;color:var(--cal-muted);font-size:10px;font-weight:800}.cal-filter.active{background:#e6f6f0;border-color:#b8e4d5;color:#176b63}.cal-edit{margin-top:9px;border-top:1px dashed var(--cal-line);padding-top:8px}.cal-edit summary{font-size:10px;font-weight:800;color:#176b63;cursor:pointer}.cal-delete{display:inline-flex;margin-top:5px;border:0;background:transparent;color:#b14a4a;padding:0;font-size:9px;cursor:pointer}
@media(max-width:1050px){.cal-layout{grid-template-columns:minmax(0,1fr) 255px}.cal-day{min-height:105px;padding:6px 5px}.cal-empty{min-height:105px}}
@media(max-width:760px){.cal-layout{grid-template-columns:1fr}.cal-side{grid-template-columns:1fr}.cal-day{min-height:87px;padding:5px 3px}.cal-empty{min-height:87px}.cal-event{font-size:8px;padding:4px 3px}.cal-day-number{width:25px;height:25px;font-size:11px}.cal-weekdays div{font-size:9px;padding:10px 1px}.cal-toolbar{padding:15px}.cal-month h3{font-size:16px}.cal-stats{gap:7px}.cal-stat{padding:11px 8px;gap:7px}.cal-stat-icon{width:32px;height:32px;font-size:15px}.cal-stat strong{font-size:17px}.cal-stat span{font-size:9px}.cal-date-card{min-width:110px}.cal-hero{border-radius:19px}}
@media(max-width:420px){.cal-day{min-height:72px}.cal-empty{min-height:72px}.cal-event{font-size:7px}.cal-more{font-size:8px;padding:2px}.cal-nav a,.cal-today{min-width:30px;height:31px;padding:0 7px}.cal-month{gap:8px}}
</style>
<div class="calendar-page">
    <section class="cal-hero">
        <div class="cal-hero-top">
            <div>
                <div class="cal-kicker">✦ برنامه‌ریزی هوشمند ساختمان</div>
                <h2>همه‌چیز سرِ وقت، در یک نگاه</h2>
                <p>رویدادهای ساختمان، مهلت تسک‌ها و زمان‌بندی نظرسنجی‌ها را در تقویم شمسی دنبال کنید؛ برنامه‌هایتان همیشه مرتب و در دسترس باشند.</p>
            </div>
            <div class="cal-date-card"><strong>{{ $F::digits($todayLabel) }}</strong><span>امروز به تقویم شمسی</span></div>
        </div>
    </section>

    @if(session('status'))<div class="cal-status" role="status">✓ {{ session('status') }}</div>@endif
    @if($errors->any())<div class="cal-errors" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="cal-stats">
        <div class="cal-stat"><span class="cal-stat-icon">▦</span><div><strong>{{ $F::digits($items->count()) }}</strong><span>مورد در این ماه</span></div></div>
        <div class="cal-stat"><span class="cal-stat-icon">◷</span><div><strong>{{ $F::digits($upcoming->count()) }}</strong><span>رویداد پیشِ رو</span></div></div>
        <div class="cal-stat"><span class="cal-stat-icon">✦</span><div><strong>{{ $F::digits($days->sum(fn($day) => $day['items']->count())) }}</strong><span>برنامه در روزهای ماه</span></div></div>
    </div>

    <div class="cal-layout">
        <section class="cal-card">
            <div class="cal-toolbar">
                <div class="cal-month"><div>
                    <h3>{{ $monthName }} {{ $F::digits((string) $year) }}</h3>
                    <small>{{ ['month' => 'نمای ماهانه', 'week' => 'نمای هفتگی · شروع هفته از شنبه', 'day' => 'برنامه روزانه'][$viewMode] }}</small>
                </div></div>
                <div class="cal-nav">
                    @if($viewMode === 'month')
                        <a href="{{ route('events.index', ['month' => $prevKey, 'filter' => $filter, 'view' => 'month']) }}" aria-label="ماه قبل" title="ماه قبل">›</a>
                        <a class="cal-today" href="{{ route('events.index', ['filter' => $filter, 'view' => 'month']) }}">امروز</a>
                        <a href="{{ route('events.index', ['month' => $nextKey, 'filter' => $filter, 'view' => 'month']) }}" aria-label="ماه بعد" title="ماه بعد">‹</a>
                    @else
                        <a href="{{ route('events.index', ['date' => $prevDateKey, 'filter' => $filter, 'view' => $viewMode]) }}" aria-label="قبلی" title="قبلی">›</a>
                        <a class="cal-today" href="{{ route('events.index', ['date' => $todayLabel, 'filter' => $filter, 'view' => $viewMode]) }}">امروز</a>
                        <a href="{{ route('events.index', ['date' => $nextDateKey, 'filter' => $filter, 'view' => $viewMode]) }}" aria-label="بعدی" title="بعدی">‹</a>
                    @endif
                </div>
                <nav class="cal-view-switch" aria-label="حالت نمایش">
                    @foreach(['month' => 'ماه', 'week' => 'هفته', 'day' => 'روز'] as $mode => $label)
                        <a class="{{ $viewMode === $mode ? 'active' : '' }}" href="{{ route('events.index', ['view' => $mode, 'date' => $anchorKey, 'month' => $monthKey, 'filter' => $filter]) }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>
            <nav class="cal-filters" aria-label="فیلتر تقویم">
                @foreach(['all' => 'همه برنامه‌ها', 'events' => 'رویدادها', 'tasks' => 'مهلت تسک‌ها', 'polls' => 'نظرسنجی‌ها'] as $filterKey => $filterLabel)
                    <a class="cal-filter {{ $filter === $filterKey ? 'active' : '' }}" href="{{ route('events.index', ['view' => $viewMode, 'date' => $anchorKey, 'month' => $monthKey, 'filter' => $filterKey]) }}">{{ $filterLabel }}</a>
                @endforeach
            </nav>
            @if($viewMode !== 'day')
                <div class="cal-weekdays">@foreach($weekdays as $weekday)<div>{{ $weekday }}</div>@endforeach</div>
            @endif
            <div class="cal-days mode-{{ $viewMode }}">
                @for($i=0;$i<$leading;$i++)<div class="cal-empty" aria-hidden="true"></div>@endfor
                @foreach($days as $day)
                    <div class="cal-day {{ $day['is_today'] ? 'is-today' : '' }}" data-date="{{ $day['date'] }}" data-jalali-date="{{ \App\Support\JalaliDate::format(\Illuminate\Support\Carbon::parse($day['date'])) }}">
                        <div class="cal-day-number">
                            @if($viewMode !== 'day')<span class="cal-day-label">{{ $weekdays[(\Illuminate\Support\Carbon::parse($day['date'])->dayOfWeek + 1) % 7] }}</span>@endif
                            {{ $day['jalali'] }}
                        </div>
                        <div class="cal-day-events">
                            @foreach(($viewMode === 'month' ? $day['items']->take(3) : $day['items']) as $item)
                                @php $draggable = $item['event_id'] && $item['managed']; @endphp
                                @if($item['url'])
                                    <a class="cal-event {{ $item['color'] }}" href="{{ $item['url'] }}" title="{{ $item['title'] }} · {{ $item['type'] }}">{{ $item['title'] }}</a>
                                @else
                                    <div class="cal-event {{ $item['color'] }}" title="{{ $item['title'] }} · {{ $item['type'] }}" @if($draggable) draggable="true" data-event-id="{{ $item['event_id'] }}" data-move-url="{{ route('events.move', $item['event_id']) }}" data-start-time="{{ $item['starts_at']->format('H:i') }}" @endif>
                                        @if($viewMode !== 'month' && !$item['event_model']?->is_all_day)<strong>{{ $item['time'] }}</strong> · @endif{{ $item['title'] }}
                                    </div>
                                @endif
                            @endforeach
                            @if($viewMode === 'month' && $day['items']->count() > 3)<div class="cal-more">+ {{ $F::digits((string) ($day['items']->count()-3)) }} مورد دیگر</div>@endif
                        </div>
                    </div>
                @endforeach
                @for($i=0;$i<$trailing;$i++)<div class="cal-empty" aria-hidden="true"></div>@endfor
            </div>
            <div class="cal-legend">
                <span><i class="cal-dot teal"></i>رویدادهای ساختمان</span>
                <span><i class="cal-dot amber"></i>مهلت تسک‌ها</span>
                <span><i class="cal-dot blue"></i>شروع نظرسنجی</span>
                <span><i class="cal-dot rose"></i>پایان نظرسنجی</span>
            </div>
        </section>

        <aside class="cal-side">
            @if($canManage)
            <section class="cal-card">
                <details class="cal-create">
                    <summary>ثبت رویداد جدید</summary>
                    <form class="cal-form" method="POST" action="{{ route('events.store') }}">
                        @csrf
                        <div class="cal-field"><label for="cal-title">عنوان رویداد *</label><input id="cal-title" name="title" maxlength="180" required value="{{ old('title') }}" placeholder="مثلاً جلسه ساختمان"></div>
                        <div class="cal-field"><label for="cal-description">توضیحات</label><textarea id="cal-description" name="description" rows="2" maxlength="3000" placeholder="جزئیات و یادداشت‌ها...">{{ old('description') }}</textarea></div>
                        <div class="cal-field"><label for="cal-start-date">تاریخ شروع شمسی *</label><input class="jdate" id="cal-start-date" name="start_date" required autocomplete="off" placeholder="انتخاب تاریخ" value="{{ old('start_date', $todayLabel) }}"></div>
                        <div class="cal-two">
                            <div class="cal-field"><label for="cal-start-time">ساعت شروع</label><input id="cal-start-time" name="start_time" type="time" value="{{ old('start_time', '09:00') }}"></div>
                            <div class="cal-field"><label for="cal-end-time">ساعت پایان</label><input id="cal-end-time" name="end_time" type="time" value="{{ old('end_time', '10:00') }}"></div>
                        </div>
                        <div class="cal-field"><label for="cal-end-date">تاریخ پایان (اختیاری)</label><input class="jdate" id="cal-end-date" name="end_date" autocomplete="off" placeholder="همان روز"></div>
                        <div class="cal-field"><label for="cal-reminder">یادآوری</label><select id="cal-reminder" name="reminder_minutes"><option value="0">بدون یادآوری</option><option value="15">۱۵ دقیقه قبل</option><option value="60">۱ ساعت قبل</option><option value="1440">۱ روز قبل</option><option value="10080">۱ هفته قبل</option></select></div>
                        <div class="cal-field"><label for="cal-color">رنگ رویداد</label><select id="cal-color" name="color"><option value="teal">سبز فیروزه‌ای</option><option value="blue">آبی</option><option value="amber">کهربایی</option><option value="rose">صورتی</option><option value="violet">بنفش</option></select></div>
                        <label class="cal-check"><input type="checkbox" name="is_all_day" value="1" @checked(old('is_all_day'))> رویداد تمام‌روزه است</label>
                        <button class="cal-submit" type="submit">＋ افزودن به تقویم</button>
                    </form>
                </details>
            </section>
            @endif

            <section class="cal-card">
                <div class="cal-side-head"><h3>رویدادهای پیشِ رو</h3><span>{{ $F::digits((string) $upcoming->count()) }} مورد</span></div>
                @if($upcoming->isEmpty())
                    <div class="cal-empty-note">در ادامهٔ این ماه برنامه‌ای ثبت نشده است.<br>روزهای خلوت فرصت خوبی برای برنامه‌ریزی‌اند.</div>
                @else
                    <div class="cal-upcoming">
                        @foreach($upcoming as $item)
                            @php $itemJalali = \App\Support\JalaliDate::format($item['starts_at']); @endphp
                            <div class="cal-upcoming-item">
                                <div class="cal-mini-date"><strong>{{ $F::digits((string) \Morilog\Jalali\Jalalian::fromCarbon($item['starts_at'])->getDay()) }}</strong><small>{{ $monthNames[\Morilog\Jalali\Jalalian::fromCarbon($item['starts_at'])->getMonth()] }}</small></div>
                                <div class="cal-upcoming-copy">
                                    @if($item['url'])<a href="{{ $item['url'] }}" style="color:inherit;text-decoration:none"><strong>{{ $item['title'] }}</strong></a>@else<strong>{{ $item['title'] }}</strong>@endif
                                    <small>{{ $F::digits($itemJalali) }} · {{ $item['time'] }}</small>
                                    <span class="cal-type">{{ $item['type'] }}</span>
                                    @if($item['event_id'] && $item['managed'])
                                        
                                    <details class="cal-edit">
                                        <summary>ویرایش رویداد</summary>
                                        <form class="cal-form" method="POST" action="{{ route('events.update', $item['event_id']) }}">
                                            @csrf @method('PUT')
                                            <div class="cal-field"><label>عنوان</label><input name="title" maxlength="180" required value="{{ $item['title'] }}"></div>
                                            <div class="cal-field"><label>توضیحات</label><textarea name="description" rows="2" maxlength="3000">{{ $item['description'] }}</textarea></div>
                                            <div class="cal-field"><label>تاریخ شروع شمسی</label><input class="jdate" name="start_date" required autocomplete="off" value="{{ \App\Support\JalaliDate::format($item['starts_at']) }}"></div>
                                            <div class="cal-two">
                                                <div class="cal-field"><label>ساعت شروع</label><input name="start_time" type="time" value="{{ $item['starts_at']->format('H:i') }}"></div>
                                                <div class="cal-field"><label>ساعت پایان</label><input name="end_time" type="time" value="{{ $item['event_model']->ends_at?->format('H:i') ?? $item['starts_at']->copy()->addHour()->format('H:i') }}"></div>
                                            </div>
                                            <div class="cal-field"><label>تاریخ پایان (اختیاری)</label><input class="jdate" name="end_date" autocomplete="off" value="{{ $item['event_model']->ends_at ? \App\Support\JalaliDate::format($item['event_model']->ends_at) : '' }}"></div>
                                            <div class="cal-field"><label>رنگ رویداد</label><select name="color">@foreach(['teal'=>'سبز فیروزه‌ای','blue'=>'آبی','amber'=>'کهربایی','rose'=>'صورتی','violet'=>'بنفش'] as $colorKey=>$colorLabel)<option value="{{ $colorKey }}" @selected($item['color']===$colorKey)>{{ $colorLabel }}</option>@endforeach</select></div>
                                            <div class="cal-field"><label>یادآوری</label><select name="reminder_minutes">@foreach([0=>'بدون یادآوری',15=>'۱۵ دقیقه قبل',60=>'۱ ساعت قبل',1440=>'۱ روز قبل',10080=>'۱ هفته قبل'] as $minutes=>$label)<option value="{{ $minutes }}" @selected((int) $item['reminder_minutes']===$minutes)>{{ $label }}</option>@endforeach</select></div>
                                            <label class="cal-check"><input type="checkbox" name="is_all_day" value="1" @checked($item['event_model']->is_all_day)> رویداد تمام‌روزه است</label>
                                            <button class="cal-submit" type="submit">ذخیره تغییرات</button>
                                        </form>
                                    </details>
                                        <form method="POST" action="{{ route('events.destroy', $item['event_id']) }}" data-confirm="این رویداد از تقویم حذف شود؟" data-confirm-title="حذف رویداد" data-confirm-icon="warning">
                                            @csrf @method('DELETE')<button class="cal-delete" type="submit">حذف رویداد</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.querySelector('.calendar-page');
    if (!root) return;
    let dragged = null;
    root.querySelectorAll('.cal-event[draggable="true"]').forEach((event) => {
        event.addEventListener('dragstart', (e) => {
            dragged = event;
            e.dataTransfer?.setData('text/plain', event.dataset.eventId || '');
            if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
        });
        event.addEventListener('dragend', () => {
            dragged = null;
            root.querySelectorAll('.cal-day.is-drop-target').forEach((day) => day.classList.remove('is-drop-target'));
        });
    });
    root.querySelectorAll('.cal-day[data-jalali-date]').forEach((day) => {
        day.addEventListener('dragover', (e) => {
            if (!dragged) return;
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            day.classList.add('is-drop-target');
        });
        day.addEventListener('dragleave', (e) => {
            if (!day.contains(e.relatedTarget)) day.classList.remove('is-drop-target');
        });
        day.addEventListener('drop', async (e) => {
            if (!dragged) return;
            e.preventDefault();
            day.classList.remove('is-drop-target');
            const url = dragged.dataset.moveUrl;
            if (!url) return;
            const token = @json(csrf_token());
            const payload = new URLSearchParams({
                _token: token,
                _method: 'PATCH',
                start_date: day.dataset.jalaliDate,
                start_time: dragged.dataset.startTime || '09:00',
                view: @json($viewMode),
                filter: @json($filter)
            });
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.hidden = true;
            payload.forEach((value, key) => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = key; input.value = value; form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        });
    });
})();
</script>
@endpush
