@extends('admin.layout')
@section('title', 'گزارش عملکرد پشتیبانی')
@section('content')
@php($F = \\App\\Support\\Fa::class)
<style>
.spr-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:16px}.spr-head h2{font-size:17px;margin:0 0 5px}.spr-head p{font-size:11px;color:var(--muted);margin:0}.spr-filter{display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin:14px 0 18px}.spr-filter label{display:grid;gap:5px;font-size:11px;font-weight:800}.spr-filter input{min-width:145px;padding:9px 11px;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--text);font:inherit}.spr-filter button,.spr-link{border:0;border-radius:10px;padding:10px 14px;background:var(--brand,#1c4f4a);color:#fff;text-decoration:none;font:inherit;font-size:11px;font-weight:800;cursor:pointer}.spr-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:15px 0}.spr-kpi{padding:14px;border:1px solid var(--line);border-radius:13px}.spr-kpi small{display:block;color:var(--muted);font-size:11px}.spr-kpi strong{display:block;font-size:21px;margin-top:6px}.spr-section{font-size:14px;margin:22px 0 10px}.spr-wrap{overflow:auto;border:1px solid var(--line);border-radius:13px}.spr-table{width:100%;border-collapse:collapse;min-width:920px}.spr-table th,.spr-table td{padding:12px 10px;border-bottom:1px solid var(--line);text-align:right;font-size:11px;white-space:nowrap}.spr-table th{color:var(--muted);font-weight:800;background:var(--surface)}.spr-table tr:last-child td{border-bottom:0}.spr-muted{color:var(--muted)}.spr-note{font-size:11px;color:var(--muted);line-height:2;margin-top:12px}.spr-badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:rgba(42,155,143,.1);color:var(--brand,#1c4f4a);font-weight:800}
@media(max-width:700px){.spr-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
<div class="spr-head">
  <div><h2>عملکرد تیم پشتیبانی</h2><p>شاخص‌های پاسخ‌گویی، حل درخواست‌ها و پایبندی به مهلت‌های SLA</p></div>
  <a class="spr-link" href="{{ route('admin.support.index') }}">بازگشت به مرکز پشتیبانی</a>
</div>
<form class="spr-filter" method="GET">
  <label>از تاریخ شمسی<input type="text" name="from" value="{{ $filters['from'] }}" placeholder="۱۴۰۵/۰۸/۱۱" inputmode="numeric"></label>
  <label>تا تاریخ شمسی<input type="text" name="to" value="{{ $filters['to'] }}" placeholder="۱۴۰۵/۰۹/۱۱" inputmode="numeric"></label>
  <button type="submit">اعمال بازه</button>
  <a href="{{ route('admin.support.reports') }}" class="spr-link" style="background:var(--surface);color:var(--text);border:1px solid var(--line)">۳۰ روز اخیر</a>
</form>
<div class="spr-kpis">
  <div class="spr-kpi"><small>کل تیکت‌های ثبت‌شده</small><strong>{{ $F::num($summary['tickets']) }}</strong></div>
  <div class="spr-kpi"><small>تیکت‌های پاسخ‌گرفته</small><strong>{{ $F::num($summary['responded_tickets']) }}</strong></div>
  <div class="spr-kpi"><small>تیکت‌های حل‌شده / بسته</small><strong>{{ $F::num($summary['resolved_tickets']) }}</strong></div>
  <div class="spr-kpi"><small>میانگین زمان اولین پاسخ</small><strong>{{ $summary['avg_first_response_minutes'] === null ? '—' : $F::num($summary['avg_first_response_minutes']).' دقیقه' }}</strong></div>
  <div class="spr-kpi"><small>میانگین زمان حل درخواست</small><strong>{{ $summary['avg_resolution_minutes'] === null ? '—' : $F::num($summary['avg_resolution_minutes']).' دقیقه' }}</strong></div>
  <div class="spr-kpi"><small>رعایت کلی SLA</small><strong>{{ $summary['sla_compliance'] === null ? '—' : $F::digits(number_format($summary['sla_compliance'],1)).'٪' }}</strong></div>
</div>
<h3 class="spr-section">عملکرد به تفکیک کارشناس</h3>
<div class="spr-wrap">
<table class="spr-table">
  <thead><tr><th>کارشناس فعلی</th><th>تیکت‌های منتسب</th><th>پاسخ‌گرفته</th><th>حل‌شده</th><th>میانگین پاسخ اولیه</th><th>میانگین زمان حل</th><th>رعایت SLA</th></tr></thead>
  <tbody>
  @forelse($agents as $agent)
    <tr>
      <td><b>{{ $agent['agent_name'] }}</b></td>
      <td>{{ $F::num($agent['tickets']) }}</td>
      <td>{{ $F::num($agent['responded_tickets']) }}</td>
      <td>{{ $F::num($agent['resolved_tickets']) }}</td>
      <td>{{ $agent['avg_first_response_minutes'] === null ? '—' : $F::num($agent['avg_first_response_minutes']).' دقیقه' }}</td>
      <td>{{ $agent['avg_resolution_minutes'] === null ? '—' : $F::num($agent['avg_resolution_minutes']).' دقیقه' }}</td>
      <td>@if($agent['sla_compliance'] === null) — @else <span class="spr-badge">{{ $F::digits(number_format($agent['sla_compliance'],1)) }}٪</span> @endif</td>
    </tr>
  @empty
    <tr><td colspan="7" class="spr-muted" style="text-align:center;padding:25px">در این بازه تیکتی ثبت نشده است.</td></tr>
  @endforelse
  </tbody>
</table>
</div>
<p class="spr-note">مبنای انتساب گزارش، کارشناس فعلی ثبت‌شده روی تیکت است؛ چون تاریخچه تغییر مسئول در ساختار فعلی ذخیره نمی‌شود، تیکت‌های ارجاع‌شده در گذشته ممکن است در گزارش به مسئول فعلی منتسب شوند. رعایت SLA از روی زمان‌های ثبت‌شده پاسخ اولیه و پایان رسیدگی و نشانگرهای نقض مهلت محاسبه می‌شود.</p>
@endsection
