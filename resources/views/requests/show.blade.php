@extends('layouts.app')
@section('title', 'جزئیات درخواست')
@section('content')
@php
  $priorityLabels = ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد', 'urgent' => 'فوری'];
  $categoryLabels = ['general' => 'عمومی', 'maintenance' => 'تعمیرات', 'cleaning' => 'نظافت', 'security' => 'حراست', 'facilities' => 'تأسیسات و مشاعات'];
  $statusClasses = ['new' => 'new', 'review' => 'review', 'doing' => 'doing', 'waiting' => 'waiting', 'done' => 'done', 'closed' => 'closed'];
  $status = $ticket->status->value;
  $priority = $ticket->priority->value;
  $owner = $ticket->acceptedBy;
  $ownerName = $owner?->display_name ?: $owner?->mobile;
  $backUrl = $isMine ? route('my.requests') : route('requests.index');
  $showUrl = route($isMine ? 'my.requests.show' : 'requests.show', $ticket->id);
  $replyTo = $ticket->comments->firstWhere('id', (int) request('reply_to_id'));
@endphp
<style>
.ticket-page{display:grid;gap:18px}.ticket-hero{position:relative;overflow:hidden;border-radius:24px;padding:24px;background:linear-gradient(115deg,#174d49 0%,#217f74 58%,#35a99a 100%);color:#fff;box-shadow:0 12px 28px rgba(24,103,94,.2)}.ticket-hero:after{content:"";position:absolute;width:240px;height:240px;border:1px solid rgba(255,255,255,.15);border-radius:50%;left:-60px;top:-140px;box-shadow:0 0 0 25px rgba(255,255,255,.04),0 0 0 52px rgba(255,255,255,.035)}.ticket-hero-top,.ticket-hero-bottom{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}.ticket-back{display:inline-flex;align-items:center;gap:7px;text-decoration:none;color:#fff;font-size:12px;padding:8px 11px;border:1px solid rgba(255,255,255,.25);border-radius:11px;background:rgba(255,255,255,.09)}.ticket-id{font-size:11px;opacity:.8;direction:ltr;unicode-bidi:isolate}.ticket-hero h2{font-size:23px;line-height:1.8;margin:10px 0 7px;font-weight:800;overflow-wrap:anywhere}.ticket-hero-sub{font-size:12px;opacity:.83}.ticket-tags{display:flex;gap:7px;flex-wrap:wrap;margin-top:15px}.ticket-tag{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:9px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.15);font-size:11px;font-weight:700}.ticket-hero .ticket-status{background:#fff;color:#17645c;border:0}.ticket-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(260px,.65fr);gap:18px;align-items:start}.ticket-main-col,.ticket-side-col{display:grid;gap:18px;min-width:0}.ticket-card-head{display:flex;align-items:center;gap:10px;margin-bottom:16px}.ticket-card-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:13px;background:var(--brand-soft);color:var(--brand-d);font-size:18px;flex:none}.ticket-card-head h3{font-size:14px;margin:0 0 3px}.ticket-card-head p{font-size:11px;color:var(--muted)}.ticket-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.ticket-detail{padding:12px;border-radius:13px;background:color-mix(in srgb,var(--track) 35%,transparent);border:1px solid var(--line);min-width:0}.ticket-detail span{display:block;color:var(--muted);font-size:10px;margin-bottom:6px}.ticket-detail b{font-size:12px;overflow-wrap:anywhere}.ticket-description{margin-top:14px;padding:16px;border:1px solid var(--line);border-radius:15px;background:var(--panel)}.ticket-description-label{font-size:11px;font-weight:800;color:var(--brand-d);margin-bottom:8px}.ticket-description p{white-space:pre-wrap;line-height:2;font-size:13px;overflow-wrap:anywhere}.ticket-owner{display:flex;align-items:center;gap:11px;padding:13px;border-radius:15px;background:var(--brand-soft);margin-bottom:16px}.ticket-owner-avatar{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:var(--brand);color:#fff;font-weight:800;flex:none}.ticket-owner b{font-size:12px;display:block}.ticket-owner small{font-size:10px;color:var(--muted);display:block;margin-top:4px}.ticket-hint{font-size:11px;line-height:1.9;color:var(--muted);margin-bottom:12px}.ticket-form{display:grid;gap:12px}.ticket-form .fld label{font-size:11px;font-weight:800}.ticket-form input,.ticket-form select,.ticket-form textarea{border-radius:12px}.ticket-form .btn{border-radius:12px;font-weight:800;padding:11px 15px}.ticket-btn-row{display:flex;gap:8px;flex-wrap:wrap}.ticket-btn-row .btn{flex:1}.ticket-comments{display:grid;gap:12px}.ticket-comment{padding:14px;border:1px solid var(--line);border-radius:15px;background:var(--panel);min-width:0}.ticket-comment-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap}.ticket-comment-author{font-size:12px;font-weight:800}.ticket-comment-time{font-size:10px;color:var(--muted)}.ticket-comment-body{font-size:12px;line-height:2;white-space:pre-wrap;overflow-wrap:anywhere;margin-top:9px}.ticket-reply-quote{display:block;margin-top:10px;padding:9px 11px;border-right:3px solid var(--brand);background:var(--brand-soft);border-radius:8px;text-decoration:none;color:var(--text);font-size:11px}.ticket-reply-quote small{display:block;color:var(--muted);margin-bottom:4px}.ticket-reply-action{display:inline-flex;margin-top:10px;font-size:11px;font-weight:700;text-decoration:none;color:var(--brand-d)}.ticket-reply-action:hover{text-decoration:underline}.ticket-reply-box{padding:11px;margin-bottom:12px;border-right:3px solid var(--brand);background:var(--brand-soft);border-radius:10px;font-size:11px}.ticket-reply-box a{color:var(--brand-d);margin-right:8px}.ticket-empty{padding:22px;text-align:center;font-size:12px;color:var(--muted);border:1px dashed var(--line);border-radius:14px}.ticket-timeline{display:grid;gap:0}.ticket-event{display:grid;grid-template-columns:22px 1fr;gap:10px;position:relative;padding-bottom:17px}.ticket-event:not(:last-child):before{content:"";position:absolute;top:19px;bottom:0;right:9px;width:1px;background:var(--line)}.ticket-event-dot{width:20px;height:20px;border-radius:50%;display:grid;place-items:center;background:var(--brand-soft);color:var(--brand-d);font-size:10px;z-index:1}.ticket-event-content{min-width:0;padding-top:1px}.ticket-event-content b{font-size:11px;line-height:1.9;display:block}.ticket-event-content small{font-size:10px;color:var(--muted);display:block;margin-top:4px}.ticket-submit-note{font-size:10px;color:var(--muted);line-height:1.8}@media(max-width:900px){.ticket-grid{grid-template-columns:1fr}.ticket-side-col{grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}}@media(max-width:600px){.ticket-hero{padding:18px}.ticket-hero h2{font-size:19px}.ticket-grid{gap:12px}.ticket-side-col{grid-template-columns:1fr}.ticket-details{gap:8px}.ticket-detail{padding:10px}.ticket-card-head{margin-bottom:13px}.ticket-hero-top{align-items:flex-start}.ticket-hero-bottom{align-items:flex-start}.ticket-comments{gap:9px}}
</style>
<div class="ticket-page">
  <section class="ticket-hero">
    <div class="ticket-hero-top"><span class="ticket-id">{{ $ticket->number }}</span><a class="ticket-back" href="{{ $backUrl }}">→ بازگشت به فهرست</a></div>
    <h2>{{ $ticket->title }}</h2>
    <div class="ticket-hero-bottom"><span class="ticket-hero-sub">ثبت‌شده در {{ \App\Support\Fa::digits(\App\Support\JalaliDate::format($ticket->created_at).($ticket->created_at ? ' · '.$ticket->created_at->format('H:i') : '')) }} توسط {{ $ticket->createdBy?->display_name ?: $ticket->createdBy?->mobile }}</span><div class="ticket-tags"><span class="ticket-tag ticket-status">{{ $statuses[$status] ?? $status }}</span><span class="ticket-tag">اولویت: {{ $priorityLabels[$priority] ?? $priority }}</span><span class="ticket-tag">{{ $categoryLabels[$ticket->category] ?? $ticket->category }}</span></div></div>
  </section>
  <div class="ticket-grid">
    <div class="ticket-main-col">
      <section class="card">
        <div class="ticket-card-head"><div class="ticket-card-icon">▤</div><div><h3>خلاصه درخواست</h3><p>اطلاعات اصلی و شرح مشکل</p></div></div>
        <div class="ticket-details">
          <div class="ticket-detail"><span>ثبت‌کننده</span><b>{{ $ticket->createdBy?->display_name ?: $ticket->createdBy?->mobile ?: '—' }}</b></div>
          <div class="ticket-detail"><span>واحد مرتبط</span><b>{{ $ticket->unit ? 'واحد '.$ticket->unit->number : 'مشخص نشده' }}</b></div>
          <div class="ticket-detail"><span>محل مشکل</span><b>{{ $ticket->location ?: 'مشخص نشده' }}</b></div>
          <div class="ticket-detail"><span>مسئول رسیدگی</span><b>{{ $ownerName ?: 'هنوز کسی مسئولیت را نپذیرفته' }}</b></div>
        </div>
        <div class="ticket-description"><div class="ticket-description-label">شرح درخواست</div><p>{{ $ticket->description }}</p></div>
      </section>
      <section class="card">
        <div class="ticket-card-head"><div class="ticket-card-icon">☷</div><div><h3>گفت‌وگو و پاسخ‌ها</h3><p>برای پاسخ دقیق، پیام موردنظر را انتخاب کنید.</p></div></div>
        <div class="ticket-comments">
          @forelse($ticket->comments->sortBy('created_at') as $comment)
            <article class="ticket-comment" id="comment-{{ $comment->id }}">
              <div class="ticket-comment-head"><span class="ticket-comment-author">{{ $comment->user?->display_name ?: $comment->user?->mobile ?: 'کاربر' }}</span><time class="ticket-comment-time">{{ \App\Support\Fa::digits(\App\Support\JalaliDate::format($comment->created_at).($comment->created_at ? ' · '.$comment->created_at->format('H:i') : '')) }}</time></div>
              @if($comment->replyTo)<a class="ticket-reply-quote" href="#comment-{{ $comment->reply_to_id }}"><small>↳ در پاسخ به {{ $comment->replyTo->user?->display_name ?: $comment->replyTo->user?->mobile }}</small>{{ \Illuminate\Support\Str::limit($comment->replyTo->body, 180) }}</a>@endif
              <p class="ticket-comment-body">{{ $comment->body }}</p>
              <a class="ticket-reply-action" href="{{ $showUrl }}?reply_to_id={{ $comment->id }}#comment-form">↩ پاسخ به این پیام</a>
            </article>
          @empty<div class="ticket-empty">هنوز پیامی در گفت‌وگو ثبت نشده. می‌توانید اولین پیام را ارسال کنید.</div>@endforelse
        </div>
        <form id="comment-form" class="ticket-form" method="POST" action="{{ $isMine ? route('my.requests.comments.store', $ticket->id) : route('requests.comments.store', $ticket->id) }}" style="margin-top:18px">@csrf
          <input type="hidden" name="reply_to_id" value="{{ old('reply_to_id', $replyTo?->id) }}">
          @if($replyTo)<div class="ticket-reply-box"><b>در حال پاسخ به {{ $replyTo->user?->display_name ?: $replyTo->user?->mobile }}</b><p style="margin-top:5px;line-height:1.8">{{ \Illuminate\Support\Str::limit($replyTo->body, 180) }}</p><a href="{{ $showUrl }}#comment-form">لغو پاسخ</a></div>@endif
          <div class="fld"><label for="body">پیام شما</label><textarea id="body" name="body" rows="3" maxlength="5000" required placeholder="پیام یا پاسخ خود را اینجا بنویسید...">{{ old('body') }}</textarea>@error('body')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="ticket-btn-row"><button class="btn" type="submit">ارسال پیام ↗</button></div>
        </form>
      </section>
    </div>
    <aside class="ticket-side-col">
      @if($canClaim)
        <section class="card"><div class="ticket-card-head"><div class="ticket-card-icon">✓</div><div><h3>برداشتن درخواست</h3><p>مسئولیت را به عهده بگیرید</p></div></div><p class="ticket-hint">این درخواست هنوز مسئول مشخصی ندارد. با قبول مسئولیت، شما به‌عنوان نیروی پیگیر ثبت می‌شوید.</p><form method="POST" action="{{ route('requests.claim', $ticket->id) }}">@csrf<button class="btn" style="width:100%;border-radius:12px;padding:12px;font-weight:800" type="submit">قبول مسئولیت درخواست</button></form></section>
      @endif
      @if($canManage)
        <section class="card"><div class="ticket-card-head"><div class="ticket-card-icon">♧</div><div><h3>مدیریت مسئولیت</h3><p>ارجاع به یک نیرو یا بازگرداندن به صف</p></div></div>
          <div class="ticket-owner"><div class="ticket-owner-avatar">{{ $owner ? mb_substr($ownerName, 0, 1) : '…' }}</div><div><b>{{ $ownerName ?: 'بدون مسئول مشخص' }}</b><small>{{ $owner ? 'مسئول فعلی درخواست' : 'منتظر قبول مسئولیت توسط پرسنل' }}</small></div></div>
          @if($staff->isEmpty())<p class="ticket-hint">برای این دسته‌بندی نیروی مرتبط فعالی پیدا نشد.</p>
          @else
            <form class="ticket-form" method="POST" action="{{ route('requests.assign', $ticket->id) }}">@csrf
              <div class="fld"><label for="user_id">تعیین مسئول</label><select id="user_id" name="user_id"><option value="">بدون مسئول؛ نمایش برای همه نیروهای مرتبط</option>@foreach($staff as $person)<option value="{{ $person->id }}" @selected((int) $ticket->accepted_by === (int) $person->id)>{{ $person->display_name ?: $person->mobile }} — {{ $person->mobile }}</option>@endforeach</select></div>
              <button class="btn" type="submit">ذخیره تغییر مسئول</button>
            </form>
          @endif
        </section>
      @endif
      @if($canUpdateStatus)
        <section class="card"><div class="ticket-card-head"><div class="ticket-card-icon">↻</div><div><h3>به‌روزرسانی وضعیت</h3><p>پیشرفت کار را ثبت کنید</p></div></div>
          <form class="ticket-form" method="POST" action="{{ route('requests.status', $ticket->id) }}">@csrf
            <div class="fld"><label for="status">وضعیت جدید</label><select id="status" name="status" required>@foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="fld"><label for="note">یادداشت پیشرفت (اختیاری)</label><textarea id="note" name="note" rows="3" maxlength="1000" placeholder="مثلاً قطعه سفارش داده شد..."></textarea></div>
            <button class="btn" type="submit">ثبت به‌روزرسانی</button>
          </form>
        </section>
      @endif
    </aside>
  </div>
  <section class="card">
    <div class="ticket-card-head"><div class="ticket-card-icon">◷</div><div><h3>تاریخچه کامل درخواست</h3><p>رویدادها به ترتیب زمان ثبت شده‌اند.</p></div></div>
    <div class="ticket-timeline">
      @forelse($ticket->events->sortBy('created_at') as $event)
        <div class="ticket-event"><div class="ticket-event-dot">•</div><div class="ticket-event-content"><b>{{ $event->description }}</b><small>{{ $event->user?->display_name ?: $event->user?->mobile ?: 'سامانه' }} · {{ \App\Support\Fa::digits(\App\Support\JalaliDate::format($event->created_at).($event->created_at ? ' · '.$event->created_at->format('H:i') : '')) }}</small></div></div>
      @empty<div class="ticket-empty">هنوز رویدادی در تاریخچه ثبت نشده است.</div>@endforelse
    </div>
  </section>
</div>
@endsection
