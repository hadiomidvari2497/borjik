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
@php $replyTo = $ticket->comments->firstWhere('id', (int) request('reply_to_id')); @endphp
<div class="card"><div class="page-h"><h2>گفت‌وگو و پاسخ‌ها</h2></div>
  @forelse($ticket->comments->sortBy('created_at') as $comment)
    <div id="comment-{{ $comment->id }}" style="padding:12px 0;border-bottom:1px solid var(--line)">
      <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><b>{{ $comment->user?->display_name ?: $comment->user?->mobile }}</b><small style="color:var(--muted)">{{ $comment->created_at?->format('Y/m/d H:i') }}</small></div>
      @if($comment->replyTo)<a href="#comment-{{ $comment->reply_to_id }}" style="display:block;margin-top:6px;padding:8px;border-right:3px solid var(--primary);background:var(--soft);border-radius:6px;text-decoration:none;color:inherit"><small>در پاسخ به {{ $comment->replyTo->user?->display_name ?: $comment->replyTo->user?->mobile }}</small><div>{{ \Illuminate\Support\Str::limit($comment->replyTo->body, 180) }}</div></a>@endif
      <p style="white-space:pre-wrap;margin:6px 0 0">{{ $comment->body }}</p>
      <a class="btn sec sm" href="{{ route($isMine ? 'my.requests.show' : 'requests.show', $ticket->id) }}?reply_to_id={{ $comment->id }}#comment-form" style="display:inline-block;margin-top:8px">پاسخ به این پیام</a>
    </div>
  @empty<div class="empty">هنوز پاسخی ثبت نشده است.</div>@endforelse
  <form id="comment-form" method="POST" action="{{ $isMine ? route('my.requests.comments.store', $ticket->id) : route('requests.comments.store', $ticket->id) }}" style="margin-top:14px">@csrf
    <input type="hidden" name="reply_to_id" value="{{ old('reply_to_id', $replyTo?->id) }}">
    @if($replyTo)<div style="padding:8px;margin-bottom:8px;border-right:3px solid var(--primary);background:var(--soft);border-radius:6px"><b>پاسخ به {{ $replyTo->user?->display_name ?: $replyTo->user?->mobile }}</b><div>{{ \Illuminate\Support\Str::limit($replyTo->body, 180) }}</div><a href="{{ route($isMine ? 'my.requests.show' : 'requests.show', $ticket->id) }}#comment-form">لغو پاسخ</a></div>@endif
    <div class="fld"><label for="body">ثبت پیام یا پاسخ</label><textarea id="body" name="body" rows="3" maxlength="5000" required placeholder="پیام خود را بنویسید...">{{ old('body') }}</textarea>@error('body')<div class="e">{{ $message }}</div>@enderror</div>
    <div class="fbar"><button class="btn" type="submit">ارسال پیام</button></div>
  </form>
</div>
<div class="card"><div class="page-h"><h2>تاریخچه کامل درخواست</h2></div>
  @forelse($ticket->events->sortBy('created_at') as $event)
    <div style="padding:10px 0;border-bottom:1px solid var(--line)"><b>{{ $event->description }}</b><div style="color:var(--muted);font-size:.9em">{{ $event->user?->display_name ?: $event->user?->mobile ?: 'سامانه' }} · {{ $event->created_at?->format('Y/m/d H:i') }}</div></div>
  @empty<div class="empty">هنوز رویدادی ثبت نشده است.</div>@endforelse
</div>
@endsection
