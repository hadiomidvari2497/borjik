@extends('layouts.app')
@section('title', 'جزئیات درخواست')
@section('content')
@php
  $priorityLabels = ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد', 'urgent' => 'فوری'];
  $categoryLabels = ['general' => 'عمومی', 'maintenance' => 'تعمیرات', 'cleaning' => 'نظافت', 'security' => 'حراست', 'facilities' => 'تأسیسات و مشاعات'];
@endphp
<div class="card">
  <div class="page-h"><div><h2>{{ $ticket->title }}</h2><small style="color:var(--muted)">{{ $ticket->number }} · {{ $ticket->created_at?->format('Y/m/d H:i') }}</small></div><a class="btn sec sm" href="{{ $isMine ? route('my.requests') : route('requests.index') }}">بازگشت</a></div>
  <div class="fgrid" style="margin-top:14px">
    <div class="fld"><label>ثبت‌کننده</label><div>{{ $ticket->createdBy?->display_name ?: $ticket->createdBy?->mobile }}</div></div>
    <div class="fld"><label>موضوع</label><div>{{ $categoryLabels[$ticket->category] ?? $ticket->category }}</div></div>
    <div class="fld"><label>اولویت</label><div>{{ $priorityLabels[$ticket->priority->value] ?? $ticket->priority->value }}</div></div>
    <div class="fld"><label>وضعیت فعلی</label><div><b>{{ $statuses[$ticket->status->value] ?? $ticket->status->value }}</b></div></div>
    <div class="fld"><label>واحد</label><div>{{ $ticket->unit ? 'واحد '.$ticket->unit->number : '—' }}</div></div>
    <div class="fld"><label>محل</label><div>{{ $ticket->location ?: '—' }}</div></div>
  </div>
  <div style="margin-top:16px"><h3>شرح درخواست</h3><p style="white-space:pre-wrap">{{ $ticket->description }}</p></div>
  <div style="margin-top:12px"><b>مسئول فعلی:</b> {{ $ticket->assignees->pluck('user.display_name')->filter()->join('، ') ?: 'هنوز ارجاع نشده' }}</div>
</div>
@if($canManage)
<div class="card"><div class="page-h"><h2>ارجاع یا تغییر مسئول</h2></div>
  @if($staff->isEmpty())<div class="empty">پرسنل فعال حراست، نظافت یا تعمیرات برای این ساختمان پیدا نشد.</div>
  @else
  <form method="POST" action="{{ route('requests.assign', $ticket->id) }}" class="fbar" style="margin-top:10px">@csrf
    <div class="fld" style="min-width:240px;flex:1"><label for="user_id">مسئول رسیدگی</label><select id="user_id" name="user_id" required><option value="">انتخاب پرسنل</option>@foreach($staff as $person)<option value="{{ $person->id }}" @selected($ticket->assignees->contains('user_id', $person->id))>{{ $person->display_name ?: $person->mobile }} — {{ $person->mobile }}</option>@endforeach</select></div>
    <button class="btn" type="submit">ارجاع درخواست</button>
  </form>
  @endif
</div>
@endif
@if($canUpdateStatus)
<div class="card"><div class="page-h"><h2>به‌روزرسانی وضعیت</h2></div>
  <form method="POST" action="{{ route('requests.status', $ticket->id) }}" style="margin-top:10px">@csrf
    <div class="fgrid"><div class="fld"><label for="status">وضعیت</label><select id="status" name="status" required>@foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($ticket->status->value === $key)>{{ $label }}</option>@endforeach</select></div>
    <div class="fld"><label for="note">گزارش پیشرفت (اختیاری)</label><input id="note" name="note" maxlength="1000" placeholder="شرح اقدام انجام‌شده"></div></div>
    <div class="fbar"><button class="btn" type="submit">ثبت وضعیت</button></div>
  </form>
</div>
@endif
<div class="card"><div class="page-h"><h2>گفت‌وگو و پاسخ‌ها</h2></div>
  @forelse($ticket->comments->sortBy('created_at') as $comment)
    <div style="padding:12px 0;border-bottom:1px solid var(--line)"><div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><b>{{ $comment->user?->display_name ?: $comment->user?->mobile }}</b><small style="color:var(--muted)">{{ $comment->created_at?->format('Y/m/d H:i') }}</small></div><p style="white-space:pre-wrap;margin:6px 0 0">{{ $comment->body }}</p></div>
  @empty<div class="empty">هنوز پاسخی ثبت نشده است.</div>@endforelse
  <form method="POST" action="{{ $isMine ? route('my.requests.comments.store', $ticket->id) : route('requests.comments.store', $ticket->id) }}" style="margin-top:14px">@csrf
    <div class="fld"><label for="body">ثبت پیام یا پاسخ</label><textarea id="body" name="body" rows="3" maxlength="5000" required placeholder="پیام خود را بنویسید...">{{ old('body') }}</textarea>@error('body')<div class="e">{{ $message }}</div>@enderror</div>
    <div class="fbar"><button class="btn" type="submit">ارسال پیام</button></div>
  </form>
</div>
<div class="card"><div class="page-h"><h2>تاریخچه وضعیت</h2></div>
  @forelse($ticket->statusLogs->sortBy('created_at') as $log)
    <div style="padding:9px 0;border-bottom:1px solid var(--line)"><b>{{ $statuses[$log->to_status] ?? $log->to_status }}</b><span style="color:var(--muted)"> — {{ $log->user?->display_name ?: 'سامانه' }} · {{ $log->created_at?->format('Y/m/d H:i') }}</span>@if($log->note)<div>{{ $log->note }}</div>@endif</div>
  @empty<div class="empty">تاریخچه‌ای ثبت نشده است.</div>@endforelse
</div>
@endsection
