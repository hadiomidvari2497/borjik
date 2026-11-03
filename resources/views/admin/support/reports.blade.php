@extends('admin.layout')
@section('title', 'گزارش عملکرد پشتیبانی')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-support.css') }}">@endpush
@section('content')
@php
    $F = \App\Support\Fa::class;
    $dur = function ($m) use ($F) {
        if ($m === null) { return '—'; }
        $m = (int) round((float) $m);
        if ($m < 60) { return $F::num($m).' دقیقه'; }
        if ($m < 1440) { $r = $m % 60; return $F::num(intdiv($m, 60)).' ساعت'.($r ? ' و '.$F::num($r).' دقیقه' : ''); }
        $h = intdiv($m % 1440, 60);
        return $F::num(intdiv($m, 1440)).' روز'.($h ? ' و '.$F::num($h).' ساعت' : '');
    };
    $pct = fn ($part, $whole) => (float) $whole > 0 ? min(100, round(((float) $part / (float) $whole) * 100)) : 0;
    $slaTone = fn ($v) => $v === null ? 't-muted' : ((float) $v >= 90 ? 't-ok' : ((float) $v >= 75 ? 't-warn' : 't-danger'));
    $slaValue = $summary['sla_compliance'];
    $rating = $satisfaction['average_rating'] === null ? null : (float) $satisfaction['average_rating'];
    $total = (int) $summary['tickets'];
@endphp
<div class="sp">
  <div class="sp-head">
    <div>
      <h2>عملکرد تیم پشتیبانی</h2>
      <p>شاخص‌های پاسخ‌گویی، حل درخواست‌ها و پایبندی به مهلت‌های SLA@if($filters['from'] && $filters['to']) · بازه: {{ $F::digits($filters['from']) }} تا {{ $F::digits($filters['to']) }}@endif</p>
    </div>
    <div class="sp-actions"><a class="btn sec sm" href="{{ route('admin.support.index') }}">بازگشت به مرکز پشتیبانی</a></div>
  </div>

  <form class="sp-card sp-filter" method="GET">
    <div class="fld"><label for="r-from">از تاریخ شمسی</label><input id="r-from" type="text" name="from" class="jdate" data-max="today" value="{{ $filters['from'] }}" placeholder="۱۴۰۵/۰۸/۱۱" inputmode="numeric" autocomplete="off"></div>
    <div class="fld"><label for="r-to">تا تاریخ شمسی</label><input id="r-to" type="text" name="to" class="jdate" data-max="today" value="{{ $filters['to'] }}" placeholder="۱۴۰۵/۰۹/۱۱" inputmode="numeric" autocomplete="off"></div>
    <div class="sp-filter-actions" style="grid-column:auto;justify-content:flex-start">
      <button type="submit" class="btn sm">اعمال بازه</button>
      <a href="{{ route('admin.support.reports') }}" class="btn sec sm">۳۰ روز اخیر</a>
    </div>
  </form>

  <div class="sp-grid">
    <div class="sp-kpi t-brand"><small>کل تیکت‌های ثبت‌شده</small><strong>{{ $F::num($summary['tickets']) }}</strong></div>
    <div class="sp-kpi t-info"><small>میانگین زمان اولین پاسخ</small><strong style="font-size:20px">{{ $dur($summary['avg_first_response_minutes']) }}</strong></div>
    <div class="sp-kpi t-info"><small>میانگین زمان حل درخواست</small><strong style="font-size:20px">{{ $dur($summary['avg_resolution_minutes']) }}</strong></div>
    <div class="sp-kpi {{ $slaTone($slaValue) }}">
      <small>رعایت کلی SLA</small>
      <strong>{{ $slaValue === null ? '—' : $F::digits(number_format($slaValue,1)).'٪' }}</strong>
      @if($slaValue !== null)<div class="sp-bar is-thin sp-sub" style="margin-top:10px"><i style="width:{{ min(100, max(0, (float) $slaValue)) }}%"></i></div>@endif
    </div>
  </div>

  <div class="sp-two">
    <section class="sp-card">
      <h3 class="sp-card-title">مسیر رسیدگی به تیکت‌ها <small>نسبت به کل تیکت‌های بازه</small></h3>
      <div class="sp-step t-brand"><span>کل تیکت‌های ثبت‌شده</span><div class="sp-bar"><i style="width:{{ $total > 0 ? 100 : 0 }}%"></i></div><b>{{ $F::num($summary['tickets']) }}</b></div>
      <div class="sp-step t-info"><span>تیکت‌های پاسخ‌گرفته</span><div class="sp-bar"><i style="width:{{ $pct($summary['responded_tickets'], $total) }}%"></i></div><span><b>{{ $F::num($summary['responded_tickets']) }}</b> <small>({{ $F::digits($pct($summary['responded_tickets'], $total)) }}٪)</small></span></div>
      <div class="sp-step t-ok"><span>تیکت‌های حل‌شده / بسته</span><div class="sp-bar"><i style="width:{{ $pct($summary['resolved_tickets'], $total) }}%"></i></div><span><b>{{ $F::num($summary['resolved_tickets']) }}</b> <small>({{ $F::digits($pct($summary['resolved_tickets'], $total)) }}٪)</small></span></div>
    </section>
    <section class="sp-card">
      <h3 class="sp-card-title">میانگین رضایت مشتریان</h3>
      @if($rating === null)
        <div class="sp-empty" style="padding:22px 0">—<span class="sp-sub">هنوز ارزیابی‌ای در این بازه ثبت نشده است.</span></div>
      @else
        <div class="sp-rating">
          <strong>{{ $F::num($satisfaction['average_rating']) }} <small style="font-size:13px;font-weight:600;color:var(--muted)">از ۵</small></strong>
          <span class="sp-stars" aria-hidden="true">★★★★★<i style="width:{{ min(100, max(0, $rating / 5 * 100)) }}%">★★★★★</i></span>
        </div>
        <span class="sp-sub" style="margin-top:8px">{{ $F::num($satisfaction['responses']) }} ارزیابی ثبت‌شده</span>
      @endif
    </section>
  </div>

  <section class="sp-card is-flush">
    <h3 class="sp-card-title">عملکرد به تفکیک کارشناس</h3>
    <div class="sp-table-wrap" style="margin-top:12px">
    <table class="sp-table" style="min-width:920px">
      <thead><tr><th>کارشناس</th><th>تیکت‌های دارای فعالیت</th><th>تیکت‌های پاسخ‌داده‌شده</th><th>تعداد پاسخ‌ها</th><th>حل‌شده توسط کارشناس</th><th>میانگین پاسخ اولیه</th><th>میانگین زمان حل</th><th>رعایت SLA</th></tr></thead>
      <tbody>
      @forelse($agents as $agent)
        <tr>
          <td><div class="sp-agent"><span class="sp-avatar">{{ \Illuminate\Support\Str::substr((string) $agent['agent_name'], 0, 1) }}</span>{{ $agent['agent_name'] }}</div></td>
          <td>{{ $F::num($agent['tickets']) }}</td>
          <td>{{ $F::num($agent['responded_tickets']) }}</td>
          <td>{{ $F::num($agent['replied_tickets']) }}</td>
          <td>{{ $F::num($agent['resolved_tickets']) }}</td>
          <td>{{ $dur($agent['avg_first_response_minutes']) }}</td>
          <td>{{ $dur($agent['avg_resolution_minutes']) }}</td>
          <td>
            @if($agent['sla_compliance'] === null)
              <span class="sp-sub">—</span>
            @else
              <div class="sp-meter {{ $slaTone($agent['sla_compliance']) }}">
                <span class="sp-pill">{{ $F::digits(number_format($agent['sla_compliance'],1)) }}٪</span>
                <div class="sp-bar is-thin" style="flex:1"><i style="width:{{ min(100, max(0, (float) $agent['sla_compliance'])) }}%"></i></div>
              </div>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8"><div class="sp-empty"><b>تیکتی ثبت نشده</b>در این بازه تیکتی ثبت نشده است.</div></td></tr>
      @endforelse
      </tbody>
    </table>
    </div>
  </section>

  <div class="sp-note"><p>آمار هر کارشناس از رویدادهایی محاسبه می‌شود که خودش انجام داده است؛ بنابراین پاسخ‌دهی و حل تیکت به مسئول فعلی وابسته نیست. رعایت SLA در جدول کارشناسان بر اساس تیکت‌هایی است که همان کارشناس حل کرده است. تاریخچه فقط از زمان استقرار این قابلیت ثبت می‌شود و سابقه رویدادهای قدیمی قابل بازیابی نیست.</p></div>
</div>
@endsection
