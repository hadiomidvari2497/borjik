@extends('admin.layout')
@section('title', 'مرکز پشتیبانی مشتریان')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-support.css') }}">@endpush
@section('content')
@php
    $F = \App\Support\Fa::class;
    $warnAt = now()->addMinutes((int) config('support.sla.warning_minutes', 30));
    $statusTone = ['open'=>'t-info','in_progress'=>'t-brand','waiting_customer'=>'t-warn','resolved'=>'t-ok','closed'=>'t-muted'];
    $priorityTone = ['low'=>'t-muted','normal'=>'t-info','high'=>'t-warn','urgent'=>'t-danger'];
    $currentStatus = (string) ($filters['status'] ?? '');
    $hasFilters = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp
<div class="sp">
  <div class="sp-head">
    <div>
      <h2>صف درخواست‌های مشتریان</h2>
      <p>وضعیت تیکت‌ها، مهلت‌های پاسخ‌گویی و پیشنهادهای هوشمند را از اینجا پیگیری و مدیریت کنید.</p>
    </div>
    <div class="sp-actions">
      <a class="btn sec sm" href="{{ route('admin.support.canned-replies') }}">مدیریت پاسخ‌های آماده</a>
      <a class="btn sec sm" href="{{ route('admin.support.intelligence') }}">تحلیل هوشمند درخواست‌ها</a>
      <a class="btn sm" href="{{ route('admin.support.reports') }}">گزارش عملکرد</a>
    </div>
  </div>

  <div class="sp-group-title">وضعیت صف</div>
  <div class="sp-grid">
    <div class="sp-kpi t-brand"><small>کل درخواست‌ها</small><strong>{{ $F::num($stats['total']) }}</strong></div>
    <div class="sp-kpi t-info"><small>در حال رسیدگی</small><strong>{{ $F::num($stats['open']) }}</strong></div>
    <div class="sp-kpi t-warn"><small>منتظر مشتری</small><strong>{{ $F::num($stats['waiting']) }}</strong></div>
    <div class="sp-kpi t-ok"><small>حل‌شده / بسته</small><strong>{{ $F::num($stats['resolved']) }}</strong></div>
    <div @class(['sp-kpi','t-danger','is-alert'=>$stats['urgent'] > 0])><small>فوری و باز</small><strong>{{ $F::num($stats['urgent']) }}</strong></div>
  </div>

  <div class="sp-group-title">مهلت‌ها و تحلیل هوشمند</div>
  <div class="sp-grid">
    <div @class(['sp-kpi','t-warn','is-alert'=>$stats['overdue_response'] > 0])><small>پاسخ اولیه با تأخیر</small><strong>{{ $F::num($stats['overdue_response']) }}</strong></div>
    <div @class(['sp-kpi','t-warn','is-alert'=>$stats['overdue_resolution'] > 0])><small>رسیدگی با تأخیر</small><strong>{{ $F::num($stats['overdue_resolution']) }}</strong></div>
    <div @class(['sp-kpi','t-danger','is-alert'=>$stats['suggested_urgent'] > 0])><small>پیشنهاد اولویت فوری</small><strong>{{ $F::num($stats['suggested_urgent']) }}</strong></div>
    <div class="sp-kpi t-info"><small>موارد مشابه شناسایی‌شده</small><strong>{{ $F::num($stats['duplicates']) }}</strong></div>
  </div>

  @if($supportNotifications->isNotEmpty())
  <section class="sp-card">
    <h3 class="sp-card-title">اعلان‌های پشتیبانی</h3>
    @foreach($supportNotifications as $notice)
      <a class="sp-notice" href="{{ data_get($notice->data,'url') }}">
        <span><b>{{ data_get($notice->data,'title','به‌روزرسانی تیکت') }}</b> · {{ data_get($notice->data,'message') }}</span>
        @if(!$notice->read_at)<span class="sp-pill t-info dot">جدید</span>@endif
      </a>
    @endforeach
  </section>
  @endif

  <nav class="sp-seg" aria-label="فیلتر وضعیت">
    <a href="{{ request()->fullUrlWithQuery(['status'=>null,'page'=>null]) }}" @class(['on'=>$currentStatus === ''])>همه</a>
    @foreach($statuses as $key=>$label)
      <a href="{{ request()->fullUrlWithQuery(['status'=>$key,'page'=>null]) }}" @class(['on'=>$currentStatus === $key])>{{ $label }}</a>
    @endforeach
  </nav>

  <form class="sp-card sp-filter" method="GET">
    @if($currentStatus !== '')<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
    <div class="fld"><label for="f-q">جست‌وجو</label><input id="f-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="شماره، موضوع، نام یا موبایل"></div>
    <div class="fld"><label for="f-priority">اولویت</label><select id="f-priority" name="priority"><option value="">همه اولویت‌ها</option>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected($filters['priority']===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="fld"><label for="f-smart">پیشنهاد هوشمند</label><select id="f-smart" name="smart_priority"><option value="">همه پیشنهادهای هوشمند</option>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected($filters['smart_priority']===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="fld"><label for="f-assignment">مسئولیت</label>
      <select id="f-assignment" name="assignment">
        <option value="">همه مسئولیت‌ها</option>
        <option value="mine" @selected($filters['assignment']==='mine')>تیکت‌های من</option>
        <option value="unassigned" @selected($filters['assignment']==='unassigned')>بدون مسئول</option>
        <option value="assigned" @selected($filters['assignment']==='assigned')>کارشناس مشخص</option>
      </select>
    </div>
    <div class="fld"><label for="f-agent">کارشناس</label><select id="f-agent" name="assigned_admin_id"><option value="">همه کارشناسان</option>@foreach($supportAgents as $agent)<option value="{{ $agent->id }}" @selected((string)$filters['assigned_admin_id']===(string)$agent->id)>{{ $agent->name }}</option>@endforeach</select></div>
    <div class="fld"><label for="f-sla">مهلت</label><select id="f-sla" name="sla"><option value="">همه مهلت‌ها</option><option value="overdue" @selected($filters['sla']==='overdue')>مهلت گذشته</option><option value="due_soon" @selected($filters['sla']==='due_soon')>نزدیک سررسید</option></select></div>
    <div class="sp-filter-actions">
      @if($hasFilters)<a class="sp-clear" href="{{ route('admin.support.index') }}">پاک‌کردن فیلترها</a>@endif
      <button type="submit" class="btn sm">اعمال فیلتر</button>
    </div>
  </form>

  <section class="sp-card is-flush">
    <h3 class="sp-card-title">فهرست درخواست‌ها <small>{{ $F::num($tickets->total()) }} مورد</small></h3>
    <div class="sp-table-wrap" style="margin-top:12px">
      <table class="sp-table"><thead><tr><th>شماره / موضوع</th><th>مشتری</th><th>ساختمان</th><th>مسئول</th><th>وضعیت</th><th>اولویت / پیشنهاد</th><th>تشخیص مشابه</th><th>وضعیت SLA</th><th>آخرین بروزرسانی</th><th>پیام‌ها</th></tr></thead><tbody>
      @forelse($tickets as $ticket)
        @php
            $active = in_array($ticket->status, ['open','in_progress'], true);
            $overdue = $active && (($ticket->first_response_at === null && $ticket->first_response_due_at?->isPast()) || $ticket->resolution_due_at?->isPast());
            $soon = ! $overdue && $active && (($ticket->first_response_at === null && $ticket->first_response_due_at?->lte($warnAt)) || $ticket->resolution_due_at?->lte($warnAt));
            $customerName = $ticket->user?->display_name;
        @endphp
        <tr @class(['is-overdue'=>$overdue])>
          <td>
            <a class="sp-num" href="{{ route('admin.support.show',$ticket) }}">{{ $ticket->number }}</a>
            <a class="sp-subject" href="{{ route('admin.support.show',$ticket) }}" title="{{ $ticket->subject }}">{{ \Illuminate\Support\Str::limit($ticket->subject,60) }}</a>
          </td>
          <td>{{ $customerName ?: $ticket->user?->mobile }}@if($customerName && $ticket->user?->mobile)<span class="sp-sub">{{ $ticket->user->mobile }}</span>@endif</td>
          <td>{{ $ticket->building?->name ?? '—' }}</td>
          <td>{{ $ticket->assignedAdmin?->name ?? '—' }}</td>
          <td><span class="sp-pill dot {{ $statusTone[$ticket->status] ?? 't-muted' }}">{{ $statuses[$ticket->status] ?? $ticket->status }}</span></td>
          <td>
            <div class="sp-stack-xs">
              <span class="sp-pill {{ $priorityTone[$ticket->priority] ?? 't-muted' }}">{{ $priorities[$ticket->priority] ?? $ticket->priority }}</span>
              @if($ticket->priority_suggestion && $ticket->priority_suggestion !== $ticket->priority)
                <span class="sp-pill t-info">پیشنهاد: {{ $priorities[$ticket->priority_suggestion] ?? $ticket->priority_suggestion }}</span>
              @endif
            </div>
          </td>
          <td>@if($ticket->duplicate_of_ticket_id)<span class="sp-pill t-warn">مشابه {{ $ticket->duplicate_match_score }}٪</span>@else<span class="sp-sub">—</span>@endif</td>
          <td>
            @if($overdue)
              <span class="sp-pill dot t-danger">مهلت گذشته</span>
            @elseif($soon)
              <span class="sp-pill dot t-warn">نزدیک سررسید</span>
            @elseif($ticket->status==='waiting_customer')
              <span class="sp-pill dot t-muted">منتظر مشتری</span>
            @elseif(in_array($ticket->status, ['resolved','closed'], true))
              <span class="sp-pill dot t-muted">پایان‌یافته</span>
            @else
              <span class="sp-pill dot t-ok">در محدوده مجاز</span>
            @endif
          </td>
          <td style="white-space:nowrap">{{ $F::date($ticket->updated_at, 'j F Y · H:i') }}</td>
          <td><span class="sp-count">{{ $F::num($ticket->messages_count) }}</span></td>
        </tr>
      @empty
        <tr><td colspan="10"><div class="sp-empty"><b>درخواستی پیدا نشد</b>درخواستی با این مشخصات پیدا نشد. فیلترها را تغییر دهید یا پاک کنید.</div></td></tr>
      @endforelse
      </tbody></table>
    </div>
  </section>
  <div class="sp-pager">{{ $tickets->links() }}</div>
</div>
@endsection
