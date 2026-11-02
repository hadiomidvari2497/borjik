@extends('admin.layout')
@section('title', 'پیگیری تیکت '.$ticket->number)
@section('content')
@php($F = \App\Support\Fa::class)
<style>
.admin-sup-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px}.admin-sup-head h2{font-size:18px;margin:0 0 5px;font-weight:900}.admin-sup-head p{font-size:11px;color:var(--muted);margin:0}.admin-sup-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:9px;margin:15px 0}.admin-sup-meta div{padding:11px;border:1px solid var(--line);border-radius:11px}.admin-sup-meta small{display:block;color:var(--muted);font-size:10px;margin-bottom:5px}.admin-sup-meta b{font-size:12px}.admin-sup-thread{display:grid;gap:10px;margin:17px 0}.admin-sup-message{max-width:88%;padding:12px 14px;border:1px solid var(--line);border-radius:13px;background:var(--surface)}.admin-sup-message.admin{margin-inline-start:auto;background:rgba(42,155,143,.09);border-color:rgba(42,155,143,.25)}.admin-sup-message header{display:flex;justify-content:space-between;gap:12px;font-size:10px;color:var(--muted);margin-bottom:7px}.admin-sup-message p{font-size:12px;line-height:2;white-space:pre-wrap;margin:0;overflow-wrap:anywhere}.admin-sup-form{padding:15px;border:1px solid var(--line);border-radius:14px;margin-top:14px}.admin-sup-form label{display:block;font-size:11px;font-weight:800;margin-bottom:6px}.admin-sup-form textarea,.admin-sup-form select{width:100%;padding:10px;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--text);font:inherit;font-size:12px}.admin-sup-form textarea{min-height:120px;line-height:2}.admin-sup-form button{border:0;border-radius:10px;padding:10px 15px;background:var(--brand,#1c4f4a);color:white;font:inherit;font-size:12px;font-weight:800;cursor:pointer}.admin-sup-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0}.admin-sup-actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}.admin-sup-back{font-size:11px;text-decoration:none}
@media(max-width:600px){.admin-sup-message{max-width:96%}.admin-sup-grid{grid-template-columns:1fr}}
</style>
<div class="admin-sup-head"><div><h2>{{ $ticket->subject }}</h2><p>{{ $ticket->number }} · {{ $F::date($ticket->created_at, 'j F Y · H:i') }}</p></div><a class="admin-sup-back" href="{{ route('admin.support.index') }}">بازگشت به فهرست ←</a></div>
<div class="admin-sup-meta">
  <div><small>مشتری</small><b>{{ $ticket->user?->display_name ?: $ticket->user?->mobile }}</b><small style="margin-top:4px">{{ $ticket->user?->mobile }}</small></div>
  <div><small>ساختمان مرتبط</small><b>{{ $ticket->building?->name ?? 'بدون ساختمان' }}</b></div>
  <div><small>دسته‌بندی</small><b>{{ ['general'=>'عمومی','technical'=>'فنی','billing'=>'اشتراک و پرداخت','account'=>'حساب کاربری','suggestion'=>'پیشنهاد'][$ticket->category] ?? $ticket->category }}</b></div>
  <div><small>مسئول رسیدگی</small><b>{{ $ticket->assignedAdmin?->name ?? 'هنوز ارجاع نشده' }}</b></div>
</div>
<div class="admin-sup-thread">
  @foreach($ticket->messages as $message)
    <article class="admin-sup-message {{ $message->sender_type==='admin'?'admin':'' }}">
      <header><strong>{{ $message->sender_type==='admin'?'تیم پشتیبانی':'مشتری' }}</strong><time>{{ $F::date($message->created_at,'j F Y · H:i') }}</time></header>
      <p>{{ $message->body }}</p>
      @foreach($message->attachments as $attachment)<a href="{{ route('admin.support.attachments.download',$attachment) }}" style="display:inline-block;margin-top:8px;font-size:11px">📎 {{ $attachment->original_name }}</a>@endforeach
      @foreach($message->attachments as $attachment)<a href="{{ route('admin.support.attachments.download',$attachment) }}" style="display:inline-block;margin-top:8px;font-size:11px">📎 {{ $attachment->original_name }}</a>@endforeach
    </article>
  @endforeach
</div>
<form class="admin-sup-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.support.reply',$ticket) }}">
  @csrf
  <label for="body">پاسخ به مشتری</label><textarea id="body" name="body" required minlength="2" maxlength="10000" placeholder="پاسخ خود را بنویسید...">{{ old('body') }}</textarea>
  <div class="admin-sup-grid"><div><label for="reply_status">وضعیت پس از پاسخ</label><select id="reply_status" name="status">@foreach(['in_progress'=>'در حال بررسی','waiting_customer'=>'منتظر پاسخ مشتری','resolved'=>'حل‌شده','closed'=>'بسته'] as $key=>$label)<option value="{{ $key }}" @selected(old('status','waiting_customer')===$key)>{{ $label }}</option>@endforeach</select></div></div>
  <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt" style="display:block;margin:9px 0;font-size:11px"><small style="display:block;color:var(--muted);margin-bottom:8px">حداکثر ۵ فایل، هرکدام تا ۱۰ مگابایت</small><input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt" style="display:block;margin:9px 0;font-size:11px"><small style="display:block;color:var(--muted);margin-bottom:8px">حداکثر ۵ فایل، هرکدام تا ۱۰ مگابایت</small><button type="submit">ارسال پاسخ</button>
</form>
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
