@extends('layouts.app')
@section('title', 'جزئیات تسک')
@section('content')
@php
  $status=$ticket->status->value; $priority=$ticket->priority->value;
  $assigneeIds=$ticket->assignees->pluck('user_id')->map(fn($id)=>(int)$id);
  $myAssigned=$assigneeIds->contains((int)auth()->id());
  $myRating=$ticket->ratings->where('rated_by',auth()->id())->keyBy('rated_user_id');
@endphp
<style>
.task-detail-page{display:grid;gap:18px}.task-detail-hero{padding:24px;border-radius:22px;background:linear-gradient(115deg,#174d49,#217f74 60%,#35a99a);color:#fff}.task-detail-top,.task-detail-bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.task-detail-id{font-size:11px;opacity:.8;direction:ltr}.task-detail-hero h2{font-size:23px;line-height:1.8;margin:12px 0 7px;overflow-wrap:anywhere}.task-detail-hero p{font-size:11px;opacity:.82}.task-detail-tags{display:flex;gap:7px;flex-wrap:wrap}.task-detail-tag{padding:6px 9px;border-radius:8px;background:rgba(255,255,255,.14);font-size:10px;font-weight:800}.task-detail-back{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:8px 11px;font-size:11px}.task-detail-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(260px,.65fr);gap:18px;align-items:start}.task-detail-main,.task-detail-side{display:grid;gap:18px;min-width:0}.task-detail-card-head{display:flex;align-items:center;gap:10px;margin-bottom:16px}.task-detail-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:12px;background:var(--brand-soft);color:var(--brand-d);font-size:18px}.task-detail-card-head h3{font-size:14px;margin:0 0 4px}.task-detail-card-head p{font-size:11px;color:var(--muted)}.task-detail-grid-info{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.task-detail-info{padding:12px;border:1px solid var(--line);border-radius:12px;background:color-mix(in srgb,var(--track) 28%,transparent);min-width:0}.task-detail-info span{display:block;font-size:10px;color:var(--muted);margin-bottom:6px}.task-detail-info b{font-size:12px;overflow-wrap:anywhere}.task-detail-description{white-space:pre-wrap;line-height:2;font-size:13px;margin-top:15px;padding:15px;border:1px solid var(--line);border-radius:13px}.task-detail-form{display:grid;gap:12px}.task-detail-form label{font-size:11px;font-weight:800}.task-detail-form input,.task-detail-form select,.task-detail-form textarea{border-radius:11px}.task-detail-form .btn{border-radius:11px}.task-detail-list{display:grid;gap:9px}.task-detail-list-item{padding:12px;border:1px solid var(--line);border-radius:12px;font-size:12px;line-height:1.8}.task-detail-list-item small{display:block;color:var(--muted);font-size:10px;margin-top:4px}.task-detail-list-item a{color:var(--brand-d);text-decoration:none}.task-detail-comment{padding:12px;border:1px solid var(--line);border-radius:12px;margin-bottom:9px}.task-detail-comment-head{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:11px;font-weight:800}.task-detail-comment time{font-size:10px;color:var(--muted);font-weight:400}.task-detail-comment p{white-space:pre-wrap;line-height:1.9;font-size:12px;margin-top:8px;overflow-wrap:anywhere}.task-detail-event{padding:10px 0;border-bottom:1px solid var(--line);font-size:11px;line-height:1.9}.task-detail-event:last-child{border-bottom:0}.task-detail-event small{display:block;color:var(--muted);font-size:10px}.task-rating-row{display:grid;grid-template-columns:minmax(100px,1fr) 90px minmax(130px,1.2fr);gap:8px;align-items:center;margin-bottom:10px}.task-rating-row label{font-size:11px}@media(max-width:900px){.task-detail-grid{grid-template-columns:1fr}.task-detail-side{grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}}@media(max-width:620px){.task-detail-hero{padding:18px}.task-detail-hero h2{font-size:19px}.task-detail-grid-info{gap:8px}.task-detail-info{padding:10px}.task-detail-side{grid-template-columns:1fr}.task-rating-row{grid-template-columns:1fr 90px}.task-rating-row input[name$="[comment]"]{grid-column:1/-1}}
</style>
<div class="task-detail-page">
  <section class="task-detail-hero">
    <div class="task-detail-top"><span class="task-detail-id">{{ $ticket->number }}</span><div style="display:flex;gap:8px;flex-wrap:wrap">@if($canManage)<a class="task-detail-back" href="{{ route('tickets.edit',$ticket->id) }}">ویرایش تسک</a>@endif<a class="task-detail-back" href="{{ route('tickets.index') }}">← بازگشت به تسک‌ها</a></div></div>
    <h2>{{ $ticket->title }}</h2>
    <div class="task-detail-bottom"><p>ثبت‌شده توسط {{ $ticket->createdBy?->display_name ?: $ticket->createdBy?->mobile }} · {{ $ticket->created_at ? \\App\\Support\\Fa::date($ticket->created_at, 'Y/m/d H:i') : '—' }}</p><div class="task-detail-tags"><span class="task-detail-tag">{{ $statuses[$status] }}</span><span class="task-detail-tag">اولویت: {{ $priorities[$priority] }}</span><span class="task-detail-tag">{{ $categories[$ticket->category] ?? $ticket->category }}</span></div></div>
  </section>
  <div class="task-detail-grid">
    <div class="task-detail-main">
      <section class="card">
        <div class="task-detail-card-head"><div class="task-detail-icon">▤</div><div><h3>جزئیات کار</h3><p>شرح، موقعیت و مهلت انجام</p></div></div>
        <div class="task-detail-grid-info">
          <div class="task-detail-info"><span>مهلت انجام</span><b>{{ $ticket->deadline ? \\App\\Support\\Fa::date($ticket->deadline, 'Y/m/d H:i') : 'بدون مهلت' }}</b></div>
          <div class="task-detail-info"><span>واحد مرتبط</span><b>{{ $ticket->unit ? (($ticket->unit->block?->name ? $ticket->unit->block->name.' · ' : '').'واحد '.$ticket->unit->number) : 'مشخص نشده' }}</b></div>
          <div class="task-detail-info"><span>محل</span><b>{{ $ticket->location ?: 'مشخص نشده' }}</b></div>
          <div class="task-detail-info"><span>مسئول اصلی</span><b>{{ $ticket->acceptedBy?->display_name ?: $ticket->acceptedBy?->mobile ?: 'هنوز مسئول اصلی انتخاب نشده' }}</b></div>
        </div>
        <div class="task-detail-description">{{ $ticket->description }}</div>
      </section>
      @if($ticket->attachments->isNotEmpty())
      <section class="card"><div class="task-detail-card-head"><div class="task-detail-icon">↧</div><div><h3>پیوست‌ها</h3><p>فایل‌ها فقط برای افراد دارای دسترسی به این تسک قابل دریافت‌اند.</p></div></div><div class="task-detail-list">@foreach($ticket->attachments as $file)<div class="task-detail-list-item"><a href="{{ route('tickets.attachments.show',[$ticket->id,$file->id]) }}">↧ {{ $file->original_name }}</a><small>{{ number_format($file->size_bytes/1024,1) }} KB · {{ $file->created_at ? \\App\\Support\\Fa::date($file->created_at, 'Y/m/d H:i') : '—' }}</small></div>@endforeach</div></section>
      @endif
      <section class="card">
        <div class="task-detail-card-head"><div class="task-detail-icon">☷</div><div><h3>گفت‌وگو و یادداشت‌ها</h3><p>توضیحات تکمیلی را در تاریخچه کار نگه دارید.</p></div></div>
        @forelse($ticket->comments->sortBy('created_at') as $comment)<article class="task-detail-comment"><div class="task-detail-comment-head"><span>{{ $comment->user?->display_name ?: $comment->user?->mobile ?: 'کاربر' }}</span><time>{{ $comment->created_at ? \\App\\Support\\Fa::date($comment->created_at, 'Y/m/d H:i') : '—' }}</time></div><p>{{ $comment->body }}</p></article>@empty<div class="task-detail-list-item">هنوز گفت‌وگویی ثبت نشده است.</div>@endforelse
        <form class="task-detail-form" method="POST" action="{{ route('tickets.comments.store',$ticket->id) }}">@csrf<div class="fld"><label for="comment-body">ثبت پیام جدید</label><textarea id="comment-body" name="body" rows="3" maxlength="5000" required placeholder="شرح پیشرفت، مشکل یا هماهنگی با همکاران...">{{ old('body') }}</textarea>@error('body')<div class="e">{{ $message }}</div>@enderror</div><button class="btn" type="submit">ثبت پیام</button></form>
      </section>
      <section class="card"><div class="task-detail-card-head"><div class="task-detail-icon">◷</div><div><h3>تاریخچه تغییرات</h3><p>وضعیت و ارجاع‌ها به ترتیب زمان ثبت شده‌اند.</p></div></div><div>@forelse($ticket->events->sortByDesc('created_at') as $event)<div class="task-detail-event"><b>{{ $event->description }}</b><small>{{ $event->user?->display_name ?: $event->user?->mobile ?: 'سامانه' }} · {{ $event->created_at ? \\App\\Support\\Fa::date($event->created_at, 'Y/m/d H:i') : '—' }}</small></div>@empty<div class="task-detail-list-item">تاریخچه‌ای وجود ندارد.</div>@endforelse</div></section>
    </div>
    <aside class="task-detail-side">
      @if($myAssigned && !$ticket->accepted_by)
      <section class="card"><div class="task-detail-card-head"><div class="task-detail-icon">✓</div><div><h3>قبول مسئولیت</h3><p>مسئولیت اصلی تسک را بپذیرید.</p></div></div><form method="POST" action="{{ route('tickets.claim',$ticket->id) }}">@csrf<button class="btn" type="submit">قبول مسئولیت تسک</button></form></section>
      @endif
      @if($canUpdateStatus)
      <section class="card"><div class="task-detail-card-head"><div class="task-detail-icon">↻</div><div><h3>تغییر وضعیت</h3><p>پیشرفت تسک را ثبت کنید.</p></div></div>@if($status === 'done')<p class="task-submit-note">پرسنل پایان کار را اعلام کرده است؛ تسک تا تأیید مدیر بسته نمی‌شود.</p>@endif<form class="task-detail-form" method="POST" action="{{ route('tickets.status',$ticket->id) }}">@csrf<div class="fld"><label for="status">وضعیت جدید</label><select id="status" name="status" required>@foreach($statuses as $key=>$label)@if($key !== 'closed' || $isManager)<option value="{{ $key }}" @selected($status===$key)>{{ $label }}</option>@endif @endforeach</select></div><div class="fld"><label for="note">یادداشت (اختیاری)</label><textarea id="note" name="note" rows="2" maxlength="1000" placeholder="مثلاً قطعه سفارش داده شد..."></textarea></div><button class="btn" type="submit">ثبت وضعیت</button></form></section>
      @endif
      <section class="card"><div class="task-detail-card-head"><div class="task-detail-icon">♧</div><div><h3>پرسنل مرتبط</h3><p>همکاران ارجاع‌شده به این کار</p></div></div><div class="task-detail-list">@forelse($ticket->assignees as $assignee)<div class="task-detail-list-item"><b>{{ $assignee->user?->display_name ?: $assignee->user?->mobile }}</b><small>{{ $assignee->accepted_at ? 'مسئولیت پذیرفته شده' : 'در انتظار پذیرش' }} · {{ $assignee->assigned_at ? \\App\\Support\\Fa::date($assignee->assigned_at, 'Y/m/d H:i') : '—' }}</small></div>@empty<div class="task-detail-list-item">هنوز پرسنلی ارجاع نشده است.</div>@endforelse</div>
        @if($canManage || $canAddHelper)<form class="task-detail-form" method="POST" action="{{ route('tickets.assign',$ticket->id) }}" style="margin-top:14px">@csrf<div class="fld"><label for="helper_ids">افزودن همکار</label><select id="helper_ids" name="user_ids[]" multiple required size="4">@foreach($staff as $person)<option value="{{ $person->id }}" @disabled($assigneeIds->contains((int)$person->id))>{{ $person->display_name ?: $person->mobile }}</option>@endforeach</select></div><button class="btn" type="submit">افزودن همکار</button></form>@endif
      </section>
      @if($canRate)
      <section class="card"><div class="task-detail-card-head"><div class="task-detail-icon">★</div><div><h3>ارزیابی عملکرد</h3><p>پس از انجام تسک، به همکاران امتیاز بدهید.</p></div></div><form class="task-detail-form" method="POST" action="{{ route('tickets.rate',$ticket->id) }}">@csrf
        @foreach($ticket->assignees as $assignee)
          @if((int)$assignee->user_id !== (int)auth()->id() && !$myRating->has($assignee->user_id))
            <div class="task-rating-row"><label>{{ $assignee->user?->display_name ?: $assignee->user?->mobile }}</label><select name="ratings[{{ $assignee->user_id }}][score]" required><option value="">امتیاز</option>@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }} از ۵</option>@endfor</select><input name="ratings[{{ $assignee->user_id }}][user_id]" type="hidden" value="{{ $assignee->user_id }}"><input name="ratings[{{ $assignee->user_id }}][comment]" maxlength="1000" placeholder="نظر اختیاری"></div>
          @endif
        @endforeach
        <button class="btn" type="submit">ثبت امتیازها</button>
      </form></section>
      @endif
      @if($ticket->ratings->isNotEmpty())
      <section class="card"><div class="task-detail-card-head"><div class="task-detail-icon">★</div><div><h3>امتیازهای ثبت‌شده</h3><p>امتیازهای این تسک</p></div></div><div class="task-detail-list">@foreach($ticket->ratings as $rating)<div class="task-detail-list-item"><b>{{ $rating->ratedUser?->display_name ?: $rating->ratedUser?->mobile }} · {{ $rating->score }}/5</b>@if($rating->comment)<small>{{ $rating->comment }}</small>@endif</div>@endforeach</div></section>
      @endif
    </aside>
  </div>
</div>
@endsection
