@extends('layouts.app')
@section('title', 'گزارش عملکرد درخواست‌ها')
@section('content')
@php
  $maxCount = max(1, collect($chart)->max(fn ($day) => max($day['created'], $day['resolved'])));
  $seconds = $stats['average_seconds'];
  $averageLabel = $seconds === null ? '—' : ($seconds >= 86400 ? \App\Support\Fa::num(round($seconds / 86400, 1)).' روز' : ($seconds >= 3600 ? \App\Support\Fa::num(round($seconds / 3600, 1)).' ساعت' : \App\Support\Fa::num(max(1, round($seconds / 60))).' دقیقه'));
@endphp
<style>
.report-page{display:grid;gap:18px}.report-hero{padding:24px;border-radius:22px;background:linear-gradient(115deg,#174d49,#258b7e);color:#fff}.report-hero h2{margin:0 0 8px;font-size:21px}.report-hero p{margin:0;line-height:1.9;font-size:12px;opacity:.88}.report-top{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}.report-back{color:var(--brand-d);text-decoration:none;font-size:12px;font-weight:800}.report-filter{display:flex;align-items:end;gap:10px;flex-wrap:wrap;padding:16px;border:1px solid var(--line);border-radius:16px;background:var(--card)}.report-filter label{display:block;margin-bottom:6px;font-size:11px;color:var(--muted)}.report-filter input{padding:9px 10px;border:1px solid var(--line);border-radius:9px;background:var(--surface,var(--card));color:var(--text);font:inherit;font-size:12px}.report-filter button{border:0;border-radius:9px;background:var(--brand);color:#fff;padding:10px 14px;font:inherit;font-size:12px;font-weight:800;cursor:pointer}.report-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.report-stat{padding:18px;border:1px solid var(--line);border-radius:16px;background:var(--card)}.report-stat span{display:block;font-size:12px;color:var(--muted)}.report-stat b{display:block;font-size:27px;margin-top:7px}.report-stat small{display:block;color:var(--muted);font-size:10px;margin-top:4px}.report-chart{padding:18px;border:1px solid var(--line);border-radius:16px;background:var(--card)}.report-chart h3{font-size:14px;margin:0 0 5px}.report-chart p{font-size:11px;color:var(--muted);margin-bottom:18px}.report-legend{display:flex;gap:16px;flex-wrap:wrap;font-size:11px;color:var(--muted);margin-bottom:14px}.report-dot{display:inline-block;width:9px;height:9px;border-radius:3px;margin-inline-end:5px;background:var(--brand)}.report-dot.resolved{background:#d69b43}.report-bars{display:grid;gap:8px}.report-row{display:grid;grid-template-columns:92px minmax(0,1fr);gap:10px;align-items:center;font-size:10px}.report-date{color:var(--muted);direction:ltr;text-align:right}.report-bar-group{display:grid;gap:4px}.report-bar{height:9px;min-width:2px;border-radius:5px;background:var(--brand)}.report-bar.resolved{background:#d69b43}.report-value{font-size:10px;color:var(--muted);margin-inline-start:5px}.report-empty{padding:22px;text-align:center;color:var(--muted);font-size:12px}@media(max-width:650px){.report-stats{grid-template-columns:1fr}.report-hero{padding:19px}.report-row{grid-template-columns:76px minmax(0,1fr)}}
</style>
<div class="report-page">
  <div class="report-top"><a class="report-back" href="{{ route('requests.index') }}">← بازگشت به درخواست‌ها</a></div>
  <section class="report-hero"><h2>گزارش عملکرد رسیدگی</h2><p>روند ثبت و حل درخواست‌های همین ساختمان را در بازه انتخابی بررسی کنید. آمار بر اساس تاریخ ثبت و تاریخ تغییر وضعیت به انجام‌شده یا بسته‌شده محاسبه می‌شود.</p></section>
  <form class="report-filter" method="GET" action="{{ route('requests.reports') }}">
    <div><label for="report-from">از تاریخ</label><input id="report-from" class="jdate" type="text" name="from" value="{{ old('from', $from) }}" placeholder="۱۴۰۵/۰۷/۰۱" autocomplete="off" required></div>
    <div><label for="report-to">تا تاریخ</label><input id="report-to" class="jdate" type="text" name="to" value="{{ old('to', $to) }}" placeholder="۱۴۰۵/۰۷/۳۰" autocomplete="off" required></div>
    <button type="submit">نمایش گزارش</button>
  </form>
  @if($errors->has('to'))<p role="alert">{{ $errors->first('to') }}</p>@endif
  <section class="report-stats" aria-label="شاخص‌های عملکرد">
    <article class="report-stat"><span>درخواست ثبت‌شده در بازه</span><b>{{ \App\Support\Fa::num($stats['created']) }}</b><small>بر اساس زمان ثبت درخواست</small></article>
    <article class="report-stat"><span>درخواست حل‌شده در بازه</span><b>{{ \App\Support\Fa::num($stats['resolved']) }}</b><small>رسیده به وضعیت انجام‌شده یا بسته‌شده</small></article>
    <article class="report-stat"><span>میانگین زمان رسیدگی</span><b>{{ $averageLabel }}</b><small>از ثبت تا اولین حل‌شدن ثبت‌شده در بازه</small></article>
  </section>
  <section class="report-chart">
    <h3>روند روزانه</h3><p>هر ردیف یک روز از بازه انتخابی است؛ نمودار برای بازه‌های طولانی نیز قابل پیمایش است.</p>
    <div class="report-legend"><span><i class="report-dot"></i>ثبت‌شده</span><span><i class="report-dot resolved"></i>حل‌شده</span></div>
    @if(collect($chart)->sum(fn($day) => $day['created'] + $day['resolved']) === 0)
      <div class="report-empty">در این بازه داده‌ای برای نمایش وجود ندارد.</div>
    @else
      <div class="report-bars">
        @foreach($chart as $day)
          <div class="report-row">
            <span class="report-date">{{ \App\Support\Fa::digits($day['date']) }}</span>
            <div class="report-bar-group">
              <div style="display:flex;align-items:center"><div class="report-bar" style="width:{{ max($day['created'] > 0 ? 2 : 0, ($day['created'] / $maxCount) * 100) }}%"></div><span class="report-value">{{ \App\Support\Fa::num($day['created']) }}</span></div>
              <div style="display:flex;align-items:center"><div class="report-bar resolved" style="width:{{ max($day['resolved'] > 0 ? 2 : 0, ($day['resolved'] / $maxCount) * 100) }}%"></div><span class="report-value">{{ \App\Support\Fa::num($day['resolved']) }}</span></div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </section>
</div>
@endsection
