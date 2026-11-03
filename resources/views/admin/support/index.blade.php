@extends('admin.layout')
@section('title', 'مرکز پشتیبانی مشتریان')
@section('content')
@php($F = \App\Support\Fa::class)
<style>
.sup-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:16px}.sup-kpi{padding:13px;border:1px solid var(--line);border-radius:13px}.sup-kpi small{display:block;color:var(--muted);font-size:11px}.sup-kpi strong{display:block;font-size:22px;margin-top:5px}.sup-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}.sup-filter input,.sup-filter select{min-width:150px;padding:9px 11px;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--text);font:inherit;font-size:12px}.sup-filter button:not(.b-select-trigger){border:0;border-radius:10px;padding:9px 15px;background:var(--fill-a,#1c4f4a);color:#fff;font:inherit;font-size:12px;font-weight:800}.sup-table{width:100%;border-collapse:collapse}.sup-table th,.sup-table td{padding:11px 9px;border-bottom:1px solid var(--line);text-align:right;font-size:11px;vertical-align:middle}.sup-table th{color:var(--muted);font-weight:800}.sup-table a{color:var(--text);text-decoration:none;font-weight:800}.sup-table a:hover{color:var(--brand)}.sup-pill{display:inline-flex;padding:4px 8px;border-radius:999px;background:var(--surface);font-size:10px;white-space:nowrap}.sup-empty{text-align:center;padding:30px;color:var(--muted)}
@media(max-width:800px){.sup-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.sup-table-wrap{overflow:auto}.sup-table{min-width:760px}}
</style>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px">
  <a href="{{ route('admin.support.canned-replies') }}" class="sup-pill" style="text-decoration:none">مدیریت پاسخ‌های آماده</a>
  <a href="{{ route('admin.support.reports') }}" class="sup-pill" style="text-decoration:none">گزارش عملکرد</a>
  <a href="{{ route('admin.support.intelligence') }}" class="sup-pill" style="text-decoration:none">تحلیل هوشمند درخواست‌ها</a>
</div>
<div class="sup-kpis">
  <div class="sup-kpi"><small>کل درخواست‌ها</small><strong>{{ $F::num($stats['total']) }}</strong></div>
  <div class="sup-kpi"><small>در حال رسیدگی</small><strong>{{ $F::num($stats['open']) }}</strong></div>
  <div class="sup-kpi"><small>منتظر مشتری</small><strong>{{ $F::num($stats['waiting']) }}</strong></div>
  <div class="sup-kpi"><small>فوری و باز</small><strong>{{ $F::num($stats['urgent']) }}</strong></div>
  <div class="sup-kpi"><small>پاسخ اولیه با تأخیر</small><strong style="color:#b45309">{{ $F::num($stats['overdue_response']) }}</strong></div>
  <div class="sup-kpi"><small>رسیدگی با تأخیر</small><strong style="color:#b45309">{{ $F::num($stats['overdue_resolution']) }}</strong></div>
  <div class="sup-kpi"><small>پیشنهاد اولویت فوری</small><strong style="color:#b91c1c">{{ $F::num($stats['suggested_urgent']) }}</strong></div>
  <div class="sup-kpi"><small>موارد مشابه شناسایی‌شده</small><strong>{{ $F::num($stats['duplicates']) }}</strong></div>
</div>
@if($supportNotifications->isNotEmpty())
<div class="card" style="padding:13px;margin-bottom:14px;border:1px solid var(--line);border-radius:13px">
  <strong style="display:block;font-size:12px;margin-bottom:9px">اعلان‌های پشتیبانی</strong>
  @foreach($supportNotifications as $notice)
    <a href="{{ data_get($notice->data,'url') }}" style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-top:1px solid var(--line);text-decoration:none;color:var(--text);font-size:11px">
      <span><b>{{ data_get($notice->data,'title','به‌روزرسانی تیکت') }}</b> · {{ data_get($notice->data,'message') }}</span>
      @if(!$notice->read_at)<span class="sup-pill">جدید</span>@endif
    </a>
  @endforeach
</div>
@endif
<form class="sup-filter" method="GET">
  <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="شماره، موضوع، نام یا موبایل">
  <select name="status"><option value="">همه وضعیت‌ها</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected($filters['status']===$key)>{{ $label }}</option>@endforeach</select>
  <select name="priority"><option value="">همه اولویت‌ها</option>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected($filters['priority']===$key)>{{ $label }}</option>@endforeach</select>
  <select name="smart_priority"><option value="">همه پیشنهادهای هوشمند</option>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected($filters['smart_priority']===$key)>{{ $label }}</option>@endforeach</select>
  <select name="assignment">
    <option value="">همه مسئولیت‌ها</option>
    <option value="mine" @selected($filters['assignment']==='mine')>تیکت‌های من</option>
    <option value="unassigned" @selected($filters['assignment']==='unassigned')>بدون مسئول</option>
    <option value="assigned" @selected($filters['assignment']==='assigned')>کارشناس مشخص</option>
  </select>
  <select name="assigned_admin_id"><option value="">همه کارشناسان</option>@foreach($supportAgents as $agent)<option value="{{ $agent->id }}" @selected((string)$filters['assigned_admin_id']===(string)$agent->id)>{{ $agent->name }}</option>@endforeach</select>
  <select name="sla"><option value="">همه مهلت‌ها</option><option value="overdue" @selected($filters['sla']==='overdue')>مهلت گذشته</option><option value="due_soon" @selected($filters['sla']==='due_soon')>نزدیک سررسید</option></select>
  <button type="submit">اعمال فیلتر</button>
  <a href="{{ route('admin.support.index') }}" style="align-self:center;font-size:11px">پاک‌کردن</a>
</form>
<div class="card sup-table-wrap" style="overflow:hidden">
  <table class="sup-table"><thead><tr><th>شماره / موضوع</th><th>مشتری</th><th>ساختمان</th><th>وضعیت</th><th>اولویت / پیشنهاد</th><th>تشخیص مشابه</th><th>وضعیت SLA</th><th>آخرین بروزرسانی</th><th>پیام‌ها</th></tr></thead><tbody>
  @forelse($tickets as $ticket)
    <tr>
      <td><a href="{{ route('admin.support.show',$ticket) }}">{{ $ticket->number }}</a><div style="margin-top:4px">{{ \Illuminate\Support\Str::limit($ticket->subject,48) }}</div></td>
      <td>{{ $ticket->user?->display_name ?: $ticket->user?->mobile }}</td>
      <td>{{ $ticket->building?->name ?? '—' }}</td>
      <td><span class="sup-pill">{{ $statuses[$ticket->status] ?? $ticket->status }}</span></td>
      <td><span class="sup-pill">{{ $priorities[$ticket->priority] ?? $ticket->priority }}</span>
        @if($ticket->priority_suggestion && $ticket->priority_suggestion !== $ticket->priority)
          <div style="margin-top:5px"><span class="sup-pill" style="background:rgba(37,99,235,.09);color:#1d4ed8">پیشنهاد: {{ $priorities[$ticket->priority_suggestion] ?? $ticket->priority_suggestion }}</span></div>
        @endif
      </td>
      <td>@if($ticket->duplicate_of_ticket_id)<span class="sup-pill" style="background:rgba(217,119,6,.1);color:#92400e">مشابه {{ $ticket->duplicate_match_score }}٪</span>@else<span style="color:var(--muted)">—</span>@endif</td>
      <td>
        @if(in_array($ticket->status,['open','in_progress'],true) && (($ticket->first_response_at===null && $ticket->first_response_due_at?->isPast()) || $ticket->resolution_due_at?->isPast()))
          <span class="sup-pill" style="background:rgba(220,38,38,.1);color:#b91c1c">مهلت گذشته</span>
        @elseif(in_array($ticket->status,['open','in_progress'],true) && (($ticket->first_response_at===null && $ticket->first_response_due_at?->lte(now()->addMinutes((int)config('support.sla.warning_minutes',30)))) || $ticket->resolution_due_at?->lte(now()->addMinutes((int)config('support.sla.warning_minutes',30)))))
          <span class="sup-pill" style="background:rgba(217,119,6,.1);color:#b45309">نزدیک سررسید</span>
        @elseif($ticket->status==='waiting_customer')
          <span class="sup-pill">منتظر مشتری</span>
        @else
          <span class="sup-pill">در محدوده مجاز</span>
        @endif
      </td>
      <td>{{ $F::date($ticket->updated_at, 'j F Y · H:i') }}</td>
      <td>{{ $F::num($ticket->messages_count) }}</td>
    </tr>
  @empty
    <tr><td colspan="9" class="sup-empty">درخواستی با این مشخصات پیدا نشد.</td></tr>
  @endforelse
  </tbody></table>
</div>
<div style="margin-top:15px">{{ $tickets->links() }}</div>
@endsection
