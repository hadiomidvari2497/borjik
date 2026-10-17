@php
  $u = $units; $t = $tickets; $p = $period; $o = $overdue; $s = $subscription;
  $quota = $s['limit'] ? min(100, round($s['used'] / max(1, $s['limit']) * 100)) : 0;
  $max = max(1, max($collections['values']));
  $sum = array_sum($collections['values']);
  $trendDays = $trend['days'];
  $statusMeta = [['sold', 'فروخته‌شده', '#2a9b8f'], ['rented', 'اجاره‌داده‌شده', '#3b82c4'], ['vacant', 'خالی', '#e8a33d'], ['repair', 'در حال تعمیر', '#e0453a']];
  $ticketMeta = ['new' => 'جدید', 'review' => 'در حال بررسی', 'doing' => 'در حال انجام', 'waiting' => 'در انتظار', 'done' => 'انجام‌شده', 'closed' => 'بسته‌شده'];
  $attentionMeta = [
    'overdue_invoices' => ['شارژهای معوق', 'صورت‌حساب‌های پرداخت‌نشده با سررسید گذشته', 'charges.index'],
    'overdue_tasks' => ['کارهای عقب‌افتاده', 'تسک‌های باز با مهلت سپری‌شده', 'tickets.index'],
    'urgent_tickets' => ['موارد اضطراری باز', 'درخواست‌ها و تسک‌های با اولویت اضطراری', 'tickets.index'],
    'pending_receipts' => ['فیش‌های در انتظار بررسی', 'پرداخت‌هایی که نیاز به تأیید مدیر دارند', 'payments.index'],
  ];
  $actionable = collect($attention)->filter(fn ($item) => $item['count'] > 0);
  $kpis = [
    ['کل واحدها', $u['total'], $s['limit'] ? 'از '.\App\Support\Fa::num($s['limit']).' واحد اشتراک' : 'اشتراک فعالی ثبت نشده', 'doc', '#3b82c4', $quota],
    ['شارژهای معوق', $o['count'], $o['count'] ? 'جمع معوق: '.\App\Support\Fa::money($o['amount']) : 'معوقه‌ای وجود ندارد', 'clk', '#e0453a', $o['openInvoices'] ? round($o['count'] / $o['openInvoices'] * 100) : 0],
    ['درخواست‌های باز', $t['openRequests'], 'از '.\App\Support\Fa::num($t['allRequests']).' درخواست ثبت‌شده', 'inbox', '#e8a33d', $t['allRequests'] ? round($t['openRequests'] / $t['allRequests'] * 100) : 0],
    ['تیکت‌های در جریان', $t['openTasks'], $t['urgent'] ? \App\Support\Fa::num($t['urgent']).' مورد اضطراری' : 'مورد اضطراری ندارید', 'kan', '#2a9b8f', $t['total'] ? round($t['openTasks'] / $t['total'] * 100) : 0],
  ];
@endphp
@extends('layouts.app')
@section('title', 'داشبورد')
@section('content')

@if($s['expires'] && $s['expires']->isBefore(now()->addDays(7)))
  <div class="warnbox">اشتراک ساختمان {{ $s['expires']->isPast() ? 'منقضی شده است' : 'تا '.\App\Support\Fa::date($s['expires']).' اعتبار دارد' }}؛ برای ادامه‌ی ثبت اطلاعات جدید آن را تمدید کنید.</div>
@endif

<div class="kpis">
  @foreach($kpis as [$label, $value, $sub, $icon, $color, $pct])
    <div class="card kpi">
      <div class="h"><span class="chip" style="background:{{ $color }}"><svg class="i"><use href="#{{ $icon }}"/></svg></span>{{ $label }}</div>
      <div><div class="v">{{ \App\Support\Fa::num($value) }}</div><div class="bar"><i style="width:{{ max($pct, 2) }}%"></i></div><div class="sub">{{ $sub }}</div></div>
    </div>
  @endforeach
</div>

<div class="card" style="margin-bottom:16px">
  <div class="hd"><h2 style="margin:0">مرکز رسیدگی هوشمند</h2><small>{{ \App\Support\Fa::num($actionable->count()) }} مورد نیازمند اقدام</small></div>
  @forelse($actionable as $item)
    @php($label = $attentionMeta[$item['key']][0])
    @php($description = $attentionMeta[$item['key']][1])
    @php($routeName = $attentionMeta[$item['key']][2])
    <div class="kv">
      <div>
        <b>{{ $label }}</b><br>
        <span>{{ $description }}</span>
      </div>
      <div style="display:flex;align-items:center;gap:12px">
        <b class="v" style="font-size:20px">{{ \App\Support\Fa::num($item['count']) }}</b>
        @if(\Illuminate\Support\Facades\Route::has($routeName))
          <a class="link" href="{{ route($routeName) }}">رسیدگی <span aria-hidden="true">←</span></a>
        @endif
      </div>
    </div>
  @empty
    <div class="empty">عالیه! فعلاً مورد فوری برای رسیدگی وجود ندارد.</div>
  @endforelse
</div>

<div class="r3">
  <div class="card">
    <h2>وضعیت واحدها</h2>
    <div class="big">{{ \App\Support\Fa::num($u['total']) }} <small style="font-size:14px;font-weight:500">واحد</small></div>
    <div class="seg">@foreach($statusMeta as [$k, , $c])@if($u['byStatus'][$k])<i style="flex:{{ $u['byStatus'][$k] }};background:{{ $c }}"></i>@endif @endforeach</div>
    <div class="lg">@foreach($statusMeta as [$k, $l, $c])<span><em style="background:{{ $c }}"></em>{{ $l }}<b>{{ \App\Support\Fa::num($u['byStatus'][$k]) }}</b></span>@endforeach</div>
    <div class="kv" style="margin-top:10px"><span>ساکنین فعال</span><b>{{ \App\Support\Fa::num($u['residents']) }} نفر</b></div>
  </div>

  <div class="card">
    <h2>شارژ ماه جاری ({{ \App\Support\Fa::MONTHS[$p['month'] - 1] }} {{ \App\Support\Fa::digits($p['year']) }})</h2>
    @if(! $p['configured'])
      <div class="empty">تنظیمات شارژ هنوز انجام نشده است.</div>
      <a class="link" href="{{ \Illuminate\Support\Facades\Route::has('charge-settings.edit') ? route('charge-settings.edit') : '#' }}">انجام تنظیمات شارژ</a>
    @else
      <div class="kv"><span>روش محاسبه</span><b>{{ $p['method']?->label() }}</b></div>
      <div class="kv"><span>تاریخ محاسبه</span><b>{{ \App\Support\Fa::date($p['calcDate']) }} @if($p['daysLeft'] > 0)({{ \App\Support\Fa::num($p['daysLeft']) }} روز دیگر)@endif</b></div>
      <div class="kv"><span>وضعیت دوره</span><b>{{ ['draft' => 'پیش‌نویس', 'calculated' => 'محاسبه‌شده', 'published' => 'منتشرشده'][$p['status']] ?? 'ایجاد نشده' }}</b></div>
      <div class="kv"><span>هزینه‌های ماه</span>
        @if($p['expensesCount'])<b>{{ \App\Support\Fa::num($p['expensesCount']) }} مورد ثبت شده</b>@else<span class="chp">ثبت نشده</span>@endif</div>
      <div class="kv"><span>هشدار پیامکی از</span><b>{{ \App\Support\Fa::date($p['warnFrom']) }}</b></div>
      @if(\Illuminate\Support\Facades\Route::has('expenses.index'))<a class="link" href="{{ route('expenses.index') }}" style="display:inline-block;margin-top:10px">ثبت هزینه‌های ماه</a>@endif
    @endif
  </div>

  <div class="card lst">
    <h2>رویدادهای نزدیک</h2>
    @forelse($events as $e)
      <div class="evt"><div class="d">{{ \App\Support\Fa::digits(\Morilog\Jalali\Jalalian::fromCarbon($e->starts_at)->format('j')) }}<small>{{ \App\Support\Fa::MONTHS[\Morilog\Jalali\Jalalian::fromCarbon($e->starts_at)->getMonth() - 1] }}</small></div>
        <div><b>{{ $e->title }}</b><span>{{ \App\Support\Fa::digits($e->starts_at->format('H:i')) }}</span></div></div>
    @empty
      <div class="empty">رویدادی در پیش نیست.</div>
    @endforelse
  </div>
</div>

<style>
.trend-filter{display:flex;gap:7px;flex-wrap:wrap}.trend-filter a{display:inline-block;padding:7px 11px;border:1px solid var(--line);border-radius:9px;color:var(--muted);text-decoration:none;font-size:11px;font-weight:800}.trend-filter a.active{background:var(--brand);border-color:var(--brand);color:#fff}.trend-comparison{margin-top:7px;font-size:10px;color:var(--muted);line-height:1.9}.trend-comparison b{color:var(--text);font-weight:800}.trend-comparison .up{color:#c47b19}.trend-comparison .down{color:#19866f}.trend-comparison .flat{color:var(--muted)}.trend-comparison .new{color:#4f70d5}.trend-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:14px 0}.trend-stat{padding:12px;border:1px solid var(--line);border-radius:12px;min-width:0}.trend-stat span{display:block;font-size:11px;color:var(--muted)}.trend-stat b{display:block;font-size:18px;margin-top:5px;overflow-wrap:anywhere}.trend-scroll{max-height:460px;overflow:auto;border-top:1px solid var(--line);padding-top:10px}.trend-row{display:grid;grid-template-columns:52px minmax(0,1fr);gap:10px;align-items:center;padding:7px 0;border-bottom:1px solid var(--line)}.trend-date{font-size:10px;color:var(--muted);direction:rtl}.trend-lines{display:grid;gap:5px}.trend-line{display:grid;grid-template-columns:64px minmax(0,1fr) auto;gap:7px;align-items:center;font-size:10px}.trend-line .bar{height:7px}.trend-line .bar i{display:block;height:100%;border-radius:8px;background:var(--brand)}.trend-line.debt .bar i{background:#e8a33d}.trend-line.collections .bar i{background:#2a9b8f}.trend-line strong{font-size:10px;white-space:nowrap}.trend-legend{display:flex;gap:13px;flex-wrap:wrap;color:var(--muted);font-size:10px;margin:8px 0 14px}.trend-legend i{display:inline-block;width:8px;height:8px;border-radius:3px;margin-inline-end:4px}.trend-legend .req{background:var(--brand)}.trend-legend .debt{background:#e8a33d}.trend-legend .col{background:#2a9b8f}@media(max-width:600px){.trend-summary{grid-template-columns:1fr}.trend-line{grid-template-columns:58px minmax(0,1fr) auto;gap:5px}.trend-date{font-size:9px}}
</style>
<div class="card" style="margin-bottom:16px">
  <div class="hd" style="align-items:flex-start;gap:12px;flex-wrap:wrap">
    <div><h2 style="margin:0">روند درخواست‌ها، بدهی و وصولی</h2><small>بازه جاری: {{ \App\Support\Fa::digits($trend['from']) }} تا {{ \App\Support\Fa::digits($trend['to']) }}</small><br><small>مقایسه با بازه قبل: {{ \App\Support\Fa::digits($trend['previous_from']) }} تا {{ \App\Support\Fa::digits($trend['previous_to']) }}</small></div>
    <nav class="trend-filter" aria-label="بازه زمانی گزارش">
      @foreach([7 => '۷ روز', 30 => '۳۰ روز', 90 => '۹۰ روز'] as $days => $label)
        <a href="{{ route('dashboard', ['trend_days' => $days]) }}" class="{{ $trendDays === $days ? 'active' : '' }}" @if($trendDays === $days) aria-current="true" @endif>{{ $label }}</a>
      @endforeach
    </nav>
  </div>
  <div class="trend-summary">
    <div class="trend-stat"><span>درخواست‌های ثبت‌شده</span><b>{{ \App\Support\Fa::num($trend['totals']['requests']) }}</b><div class="trend-comparison">بازه قبل: <b>{{ \App\Support\Fa::num($trend['comparison']['requests']['previous']) }}</b><br><span class="{{ $trend['comparison']['requests']['direction'] }}">@if($trend['comparison']['requests']['direction'] === 'new') شروع از صفر @elseif($trend['comparison']['requests']['direction'] === 'up') افزایش @elseif($trend['comparison']['requests']['direction'] === 'down') کاهش @else بدون تغییر @endif @if($trend['comparison']['requests']['change_percent'] !== null) ({{ $trend['comparison']['requests']['change_percent'] > 0 ? '+' : '' }}{{ \App\Support\Fa::num($trend['comparison']['requests']['change_percent']) }}٪) @endif</span></div></div>
    <div class="trend-stat"><span>بدهی سررسیدشده در بازه</span><b>{{ \App\Support\Fa::money($trend['totals']['debt']) }}</b><div class="trend-comparison">بازه قبل: <b>{{ \App\Support\Fa::money($trend['comparison']['debt']['previous']) }}</b><br><span class="{{ $trend['comparison']['debt']['direction'] }}">@if($trend['comparison']['debt']['direction'] === 'new') شروع از صفر @elseif($trend['comparison']['debt']['direction'] === 'up') افزایش @elseif($trend['comparison']['debt']['direction'] === 'down') کاهش @else بدون تغییر @endif @if($trend['comparison']['debt']['change_percent'] !== null) ({{ $trend['comparison']['debt']['change_percent'] > 0 ? '+' : '' }}{{ \App\Support\Fa::num($trend['comparison']['debt']['change_percent']) }}٪) @endif</span></div></div>
    <div class="trend-stat"><span>وصولی‌های تأییدشده</span><b>{{ \App\Support\Fa::money($trend['totals']['collections']) }}</b><div class="trend-comparison">بازه قبل: <b>{{ \App\Support\Fa::money($trend['comparison']['collections']['previous']) }}</b><br><span class="{{ $trend['comparison']['collections']['direction'] }}">@if($trend['comparison']['collections']['direction'] === 'new') شروع از صفر @elseif($trend['comparison']['collections']['direction'] === 'up') افزایش @elseif($trend['comparison']['collections']['direction'] === 'down') کاهش @else بدون تغییر @endif @if($trend['comparison']['collections']['change_percent'] !== null) ({{ $trend['comparison']['collections']['change_percent'] > 0 ? '+' : '' }}{{ \App\Support\Fa::num($trend['comparison']['collections']['change_percent']) }}٪) @endif</span></div></div>
  </div>
  <div class="trend-legend"><span><i class="req"></i>درخواست</span><span><i class="debt"></i>بدهی سررسیدشده</span><span><i class="col"></i>وصولی تأییدشده</span><small>نوار هر شاخص نسبت به بیشترین مقدار همان شاخص در بازه نمایش داده می‌شود.</small></div>
  <div class="trend-scroll">
    @forelse(array_reverse($trend['rows']) as $day)
      <div class="trend-row">
        <span class="trend-date">{{ \App\Support\Fa::digits($day['label']) }}</span>
        <div class="trend-lines">
          <div class="trend-line"><span>درخواست</span><div class="bar"><i style="width:{{ $day['requests'] ? max(2, $day['requests'] / $trend['maxima']['requests'] * 100) : 0 }}%"></i></div><strong>{{ \App\Support\Fa::num($day['requests']) }}</strong></div>
          <div class="trend-line debt"><span>بدهی</span><div class="bar"><i style="width:{{ $day['debt'] ? max(2, $day['debt'] / $trend['maxima']['debt'] * 100) : 0 }}%"></i></div><strong>{{ \App\Support\Fa::money($day['debt']) }}</strong></div>
          <div class="trend-line collections"><span>وصولی</span><div class="bar"><i style="width:{{ $day['collections'] ? max(2, $day['collections'] / $trend['maxima']['collections'] * 100) : 0 }}%"></i></div><strong>{{ \App\Support\Fa::money($day['collections']) }}</strong></div>
        </div>
      </div>
    @empty
      <div class="empty">در این بازه داده‌ای برای نمایش وجود ندارد.</div>
    @endforelse
  </div>
</div>

<div class="r2">
  <div class="card">
    <div class="hd"><h2 style="margin:0">گزارش وصول ماهانه</h2><small>۱۲ ماه اخیر</small></div>
    <div class="stats"><div><small>مجموع وصول</small><b>{{ \App\Support\Fa::money($sum) }}</b></div><div><small>میانگین</small><b>{{ \App\Support\Fa::money(intdiv($sum, 12)) }}</b></div></div>
    <div class="chart">@foreach($collections['values'] as $i => $v)<div style="height:{{ max($v / $max * 100, 2) }}%" title="{{ $collections['labels'][$i] }}: {{ \App\Support\Fa::money($v) }}"></div>@endforeach</div>
    <div class="ml">@foreach($collections['labels'] as $l)<span>{{ $l }}</span>@endforeach</div>
    @if($pendingReceipts)<div class="note">{{ \App\Support\Fa::num($pendingReceipts) }} فیش در انتظار تأیید شماست.</div>@endif
  </div>

  <div class="card">
    <div class="hd"><h2 style="margin:0">تیکت‌ها بر اساس وضعیت</h2><small>{{ \App\Support\Fa::num($t['total']) }} مورد</small></div>
    @foreach($ticketMeta as $k => $label)
      <div class="tr"><span>{{ $label }}</span><div class="bar"><i style="width:{{ $t['total'] ? $t['byStatus'][$k] / $t['total'] * 100 : 0 }}%"></i></div><b>{{ \App\Support\Fa::num($t['byStatus'][$k]) }}</b></div>
    @endforeach
  </div>
</div>
@endsection
