@extends('admin.layout')
@section('title', 'آرشیو پیامک')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $statusLabel = ['queued' => 'در صف', 'sent' => 'ارسال‌شده', 'failed' => 'ناموفق'];
  $total = array_sum($statusCounts->all());
  $tplLabel = ['otp' => 'کد ورود', 'invitation' => 'دعوت‌نامه', 'expense_reminder' => 'یادآوری هزینه', 'receipt_approved' => 'تأیید فیش', 'receipt_rejected' => 'رد فیش', 'admin_test' => 'تست ادمین'];
@endphp

<div class="card">
  <div class="page-h">
    <h2>آرشیو پیامک‌ها</h2>
    <a class="btn sec sm" href="{{ route('admin.sms.logs.export', array_filter($filters)) }}">خروجی CSV</a>
  </div>
  <form method="GET" class="fgrid" style="grid-template-columns:repeat(3,1fr);margin-top:10px">
    <div class="fld"><label for="from">از تاریخ</label><input id="from" name="from" class="jdate" data-clearable="1" autocomplete="off" value="{{ $filters['from'] }}"></div>
    <div class="fld"><label for="to">تا تاریخ</label><input id="to" name="to" class="jdate" data-clearable="1" autocomplete="off" value="{{ $filters['to'] }}"></div>
    <div class="fld"><label for="q">شماره</label><input id="q" name="q" dir="ltr" placeholder="0912..." value="{{ $filters['q'] }}"></div>
    <div class="fld"><label for="status">وضعیت</label>
      <select id="status" name="status"><option value="">همه</option>@foreach($statusLabel as $k => $l)<option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="fld"><label for="template">نوع پیام</label>
      <select id="template" name="template"><option value="">همه</option>@foreach($templates as $t)<option value="{{ $t }}" @selected($filters['template'] === $t)>{{ $tplLabel[$t] ?? $t }}</option>@endforeach</select></div>
    <div class="fld"><label for="gateway">اتصال</label>
      <select id="gateway" name="gateway"><option value="">همه</option>@foreach($gateways as $g)<option value="{{ $g }}" @selected($filters['gateway'] === $g)>{{ $g }}</option>@endforeach</select></div>
    <div class="fbar" style="grid-column:1/-1;margin-top:0"><button class="btn" type="submit">اعمال فیلتر</button><a class="btn sec" href="{{ route('admin.sms.logs.index') }}">پاک‌کردن</a></div>
  </form>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;font-size:12.5px">
    <span class="pill">کل: {{ $F::num($total) }}</span>
    <span class="pill">ارسال‌شده: {{ $F::num($statusCounts['sent'] ?? 0) }}</span>
    <span class="pill u">ناموفق: {{ $F::num($statusCounts['failed'] ?? 0) }}</span>
    <span class="pill h">در صف: {{ $F::num($statusCounts['queued'] ?? 0) }}</span>
  </div>
</div>

@if($daily->isNotEmpty())
<div class="card">
  <div class="page-h"><h2>به تفکیک روز</h2></div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>تاریخ</th><th>کل</th><th>ارسال‌شده</th><th>ناموفق</th></tr></thead>
    <tbody>
    @foreach($daily as $d)
      <tr><td>{{ $F::date(\Carbon\Carbon::parse($d->d), 'l j F Y') }}</td><td>{{ $F::num($d->total) }}</td><td>{{ $F::num($d->sent) }}</td><td>{{ $F::num($d->failed) }}</td></tr>
    @endforeach
    </tbody>
  </table></div>
  <p class="hdr-sub" style="margin-top:8px">حداکثر ۳۱ روز آخرِ بازهٔ انتخاب‌شده.</p>
</div>
@endif

<div class="card">
  @if($logs->isEmpty())
    <div class="empty">پیامکی با این فیلترها یافت نشد.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>زمان</th><th>شماره</th><th>نوع</th><th>وضعیت</th><th>اتصال</th><th>متن / خطا</th></tr></thead>
    <tbody>
    @foreach($logs as $l)
      <tr>
        <td style="white-space:nowrap">{{ $F::date($l->created_at, 'j F Y') }}<div style="font-size:11.5px;color:var(--muted)">{{ $F::digits($l->created_at->format('H:i')) }}</div></td>
        <td dir="ltr" style="text-align:right">{{ $l->mobile }}</td>
        <td>{{ $tplLabel[$l->template_key] ?? ($l->template_key ?: '—') }}</td>
        <td><span class="pill {{ ['sent' => '', 'failed' => 'u', 'queued' => 'h'][$l->status] ?? 'h' }}">{{ $statusLabel[$l->status] ?? $l->status }}</span></td>
        <td>{{ $l->gateway ?: '—' }}</td>
        <td style="max-width:340px;font-size:12.5px;line-height:1.8">
          {{ \Illuminate\Support\Str::limit($l->body, 90) }}
          @if($l->error)<div style="color:#c0392b">{{ $l->error }}</div>@endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $logs->links() }}
  @endif
</div>
@endsection
