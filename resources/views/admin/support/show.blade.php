@extends('admin.layout')
@section('title', 'پیگیری تیکت '.$ticket->number)
@section('content')
@php($F = \App\Support\Fa::class)
<style>
.admin-sup-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px}.admin-sup-head h2{font-size:18px;margin:0 0 5px;font-weight:900}.admin-sup-head p{font-size:11px;color:var(--muted);margin:0}.admin-sup-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:9px;margin:15px 0}.admin-sup-meta div{padding:11px;border:1px solid var(--line);border-radius:11px}.admin-sup-meta small{display:block;color:var(--muted);font-size:10px;margin-bottom:5px}.admin-sup-meta b{font-size:12px}.admin-sup-thread{display:grid;gap:10px;margin:17px 0}.admin-sup-message{max-width:88%;padding:12px 14px;border:1px solid var(--line);border-radius:13px;background:var(--surface)}.admin-sup-message.internal{max-width:100%;background:rgba(217,119,6,.08);border-color:rgba(217,119,6,.28);margin-inline:0}.admin-sup-message.admin{margin-inline-start:auto;background:rgba(42,155,143,.09);border-color:rgba(42,155,143,.25)}.admin-sup-message header{display:flex;justify-content:space-between;gap:12px;font-size:10px;color:var(--muted);margin-bottom:7px}.admin-sup-message p{font-size:12px;line-height:2;white-space:pre-wrap;margin:0;overflow-wrap:anywhere}.admin-sup-form{padding:15px;border:1px solid var(--line);border-radius:14px;margin-top:14px}.admin-sup-form label{display:block;font-size:11px;font-weight:800;margin-bottom:6px}.admin-sup-form textarea,.admin-sup-form select{width:100%;padding:10px;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--text);font:inherit;font-size:12px}.admin-sup-form textarea{min-height:120px;line-height:2}.admin-sup-form button:not(.b-select-trigger){border:0;border-radius:10px;padding:10px 15px;background:var(--fill-a,#1c4f4a);color:white;font:inherit;font-size:12px;font-weight:800;cursor:pointer}.admin-sup-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0}.admin-sup-actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}.admin-sup-back{font-size:11px;text-decoration:none}
@media(max-width:600px){.admin-sup-message{max-width:96%}.admin-sup-grid{grid-template-columns:1fr}}
</style>
<div class="admin-sup-head"><div><h2>{{ $ticket->subject }}</h2><p>{{ $ticket->number }} · {{ $F::date($ticket->created_at, 'j F Y · H:i') }}</p></div><a class="admin-sup-back" href="{{ route('admin.support.index') }}">بازگشت به فهرست ←</a></div>
@if($ticket->satisfaction_submitted_at)
  <section class="admin-sup-form" style="margin-bottom:14px">
    <b style="font-size:12px">ارزیابی رضایت مشتری</b>
    <div style="font-size:20px;font-weight:900;margin-top:7px">{{ $ticket->satisfaction_rating }} / ۵</div>
    @if($ticket->satisfaction_comment)<p style="font-size:11px;line-height:2;margin:7px 0 0">{{ $ticket->satisfaction_comment }}</p>@endif
    <small style="display:block;color:var(--muted);margin-top:6px">{{ $F::date($ticket->satisfaction_submitted_at,'j F Y · H:i') }}</small>
  </section>
@endif
@if($ticket->priority_suggestion)
  <section class="admin-sup-form" style="margin-bottom:14px;border-color:rgba(37,99,235,.25)">
    <b style="font-size:12px">تحلیل هوشمند تیکت</b>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px">
      <span class="sup-pill" style="background:rgba(37,99,235,.09);color:#1d4ed8">پیشنهاد اولویت: {{ ['low'=>'کم','normal'=>'عادی','high'=>'زیاد','urgent'=>'فوری'][$ticket->priority_suggestion] ?? $ticket->priority_suggestion }}</span>
      @if($ticket->duplicateOf)
        <a class="sup-pill" href="{{ route('admin.support.show',$ticket->duplicateOf) }}" style="text-decoration:none">درخواست مشابه: {{ $ticket->duplicateOf->number }} · {{ $ticket->duplicate_match_score }}٪</a>
      @endif
    </div>
    <p style="font-size:11px;color:var(--muted);line-height:1.9;margin:8px 0">{{ $ticket->priority_suggestion_reason }}</p>
    @if($ticket->priority_suggestion !== $ticket->priority)
      <form method="POST" action="{{ route('admin.support.apply-priority-suggestion',$ticket) }}">
        @csrf
        <button type="submit" style="border:0;border-radius:9px;padding:8px 12px;background:var(--brand,#1c4f4a);color:white;font:inherit;font-size:11px;font-weight:800">اعمال اولویت پیشنهادی</button>
      </form>
    @endif
  </section>
@endif
@if($ticket->duplicateTickets->isNotEmpty())
  <section class="admin-sup-form" style="margin-bottom:14px">
    <b style="font-size:12px">درخواست‌های مشابه مرتبط</b>
    @foreach($ticket->duplicateTickets as $related)
      <div style="padding:8px 0;border-bottom:1px solid var(--line);font-size:11px">
        <a href="{{ route('admin.support.show',$related) }}">{{ $related->number }} · {{ $related->subject }}</a>
      </div>
    @endforeach
  </section>
@endif
<div class="admin-sup-meta">
  <div><small>مشتری</small><b>{{ $ticket->user?->display_name ?: $ticket->user?->mobile }}</b><small style="margin-top:4px">{{ $ticket->user?->mobile }}</small></div>
  <div><small>ساختمان مرتبط</small><b>{{ $ticket->building?->name ?? 'بدون ساختمان' }}</b></div>
  <div><small>دسته‌بندی</small><b>{{ ['general'=>'عمومی','technical'=>'فنی','billing'=>'اشتراک و پرداخت','account'=>'حساب کاربری','suggestion'=>'پیشنهاد'][$ticket->category] ?? $ticket->category }}</b></div>
  <div><small>مسئول رسیدگی</small><b>{{ $ticket->assignedAdmin?->name ?? 'هنوز ارجاع نشده' }}</b></div>
  <div><small>حل‌کننده نهایی</small><b>{{ $ticket->resolvedByAdmin?->name ?? 'هنوز مشخص نیست' }}</b></div>
  <div><small>مهلت پاسخ اولیه</small><b>{{ $ticket->first_response_at ? 'پاسخ داده شده' : ($ticket->first_response_due_at ? $F::date($ticket->first_response_due_at,'j F Y · H:i') : 'ثبت نشده') }}</b><small style="margin-top:4px">{{ $ticket->first_response_at ? $F::date($ticket->first_response_at,'j F Y · H:i') : ($ticket->first_response_breached_at ? 'مهلت نقض شده' : 'در انتظار اولین پاسخ') }}</small></div>
  <div><small>مهلت رسیدگی</small><b>{{ $ticket->resolution_due_at ? $F::date($ticket->resolution_due_at,'j F Y · H:i') : 'ثبت نشده' }}</b><small style="margin-top:4px">{{ in_array($ticket->status,['resolved','closed'],true) ? 'پایان یافته' : ($ticket->status==='waiting_customer' ? 'متوقف تا پاسخ مشتری' : ($ticket->resolution_breached_at ? 'مهلت نقض شده' : 'در حال محاسبه')) }}</small></div>
</div>
<section class="admin-sup-form" style="margin-top:16px">
  <b style="display:block;font-size:12px;margin-bottom:12px">تاریخچه ارجاع و اقدامات</b>
  @forelse($ticket->events as $event)
    <div style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid var(--line)">
      <span style="width:8px;height:8px;flex:0 0 8px;border-radius:50%;background:var(--brand,#1c4f4a);margin-top:6px"></span>
      <div style="min-width:0">
        <b style="font-size:11px">{{ $event->description ?: 'رویداد تیکت' }}</b>
        <div style="font-size:10px;color:var(--muted);margin-top:4px">
          {{ $event->actorAdmin?->name ?? 'کارشناس حذف‌شده / نامشخص' }} · {{ $F::date($event->created_at,'j F Y · H:i') }}
          @if($event->event_type==='assigned')
            · {{ $event->fromAdmin?->name ?? 'بدون مسئول' }} ← {{ $event->toAdmin?->name ?? 'بدون مسئول' }}
          @endif
          @if($event->event_type==='status_changed')
            · {{ ['open'=>'جدید','in_progress'=>'در حال بررسی','waiting_customer'=>'منتظر پاسخ مشتری','resolved'=>'حل‌شده','closed'=>'بسته'][$event->from_status] ?? $event->from_status }} ← {{ ['open'=>'جدید','in_progress'=>'در حال بررسی','waiting_customer'=>'منتظر پاسخ مشتری','resolved'=>'حل‌شده','closed'=>'بسته'][$event->to_status] ?? $event->to_status }}
          @endif
        </div>
      </div>
    </div>
  @empty
    <p style="font-size:11px;color:var(--muted);margin:0">برای این تیکت هنوز رویداد تاریخی ثبت نشده است. تاریخچه از زمان استقرار این قابلیت ذخیره می‌شود.</p>
  @endforelse
</section>
<div class="admin-sup-thread">
  @foreach($ticket->messages as $message)
    <article class="admin-sup-message {{ $message->is_internal ? 'internal' : ($message->sender_type==='admin'?'admin':'') }}">
      <header><strong>{{ $message->is_internal ? 'یادداشت داخلی · فقط کارشناسان' : ($message->sender_type==='admin'?'تیم پشتیبانی':'مشتری') }}</strong><time>{{ $F::date($message->created_at,'j F Y · H:i') }}</time></header>
      <p>{{ $message->body }}</p>
      @foreach($message->attachments as $attachment)<a href="{{ route('admin.support.attachments.download',$attachment) }}" style="display:inline-block;margin-top:8px;font-size:11px">📎 {{ $attachment->original_name }}</a>@endforeach
    </article>
  @endforeach
</div>
<form class="admin-sup-form" method="POST" action="{{ route('admin.support.internal-note',$ticket) }}" style="border-color:rgba(217,119,6,.35);background:rgba(217,119,6,.035)">
  @csrf
  <b style="display:block;font-size:12px;margin-bottom:8px">یادداشت داخلی تیم</b>
  <small style="display:block;color:var(--muted);margin-bottom:8px">این متن فقط برای کارشناسان قابل مشاهده است و برای مشتری ارسال یا اعلان نمی‌شود.</small>
  <textarea name="body" required minlength="2" maxlength="10000" placeholder="نکته، راهکار یا هماهنگی با همکاران...">{{ old('body') }}</textarea>
  <button type="submit" style="background:#a16207;margin-top:8px">ثبت یادداشت داخلی</button>
</form>
<form class="admin-sup-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.support.reply',$ticket) }}">
  @csrf
  <label for="canned_reply">پاسخ آماده</label><select id="canned_reply" style="margin-bottom:8px"><option value="">انتخاب پاسخ آماده...</option>@foreach($cannedReplies as $canned)<option value="{{ e($canned->body) }}">{{ $canned->title }}</option>@endforeach</select>
  <label for="body">پاسخ به مشتری</label><textarea id="body" name="body" required minlength="2" maxlength="10000" placeholder="پاسخ خود را بنویسید...">{{ old('body') }}</textarea>
  <div class="admin-sup-grid"><div><label for="reply_status">وضعیت پس از پاسخ</label><select id="reply_status" name="status">@foreach(['in_progress'=>'در حال بررسی','waiting_customer'=>'منتظر پاسخ مشتری','resolved'=>'حل‌شده','closed'=>'بسته'] as $key=>$label)<option value="{{ $key }}" @selected(old('status','waiting_customer')===$key)>{{ $label }}</option>@endforeach</select></div></div>
  <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt" style="display:block;margin:9px 0;font-size:11px"><small style="display:block;color:var(--muted);margin-bottom:8px">حداکثر ۵ فایل، هرکدام تا ۱۰ مگابایت</small><button type="submit">ارسال پاسخ</button>
</form>
<script>
document.getElementById('canned_reply')?.addEventListener('change', function () {
    if (this.value) {
        const field = document.getElementById('body');
        field.value = this.value;
        field.focus();
    }
});
</script>
<form class="admin-sup-form" method="POST" action="{{ route('admin.support.update',$ticket) }}">
  @csrf @method('PATCH')
  <b style="display:block;font-size:12px;margin-bottom:12px">مدیریت وضعیت و اولویت</b>
  <div class="admin-sup-grid">
    <div><label for="assigned_admin_id">ارجاع به کارشناس</label><select id="assigned_admin_id" name="assigned_admin_id"><option value="">بدون مسئول مشخص</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected((int)$ticket->assigned_admin_id===(int)$admin->id)>{{ $admin->name }}</option>@endforeach</select></div>
    <div><label for="status">وضعیت</label><select id="status" name="status">@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected($ticket->status===$key)>{{ $label }}</option>@endforeach</select></div>
    <div><label for="priority">اولویت</label><select id="priority" name="priority">@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected($ticket->priority===$key)>{{ $label }}</option>@endforeach</select></div>
  </div>
  <button type="submit">ذخیره تغییرات</button>
</form>
@endsection
