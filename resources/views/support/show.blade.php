@extends('layouts.app')
@section('title', 'پیگیری درخواست پشتیبانی')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $statusLabels = ['open'=>'ثبت‌شده','in_progress'=>'در حال بررسی','waiting_customer'=>'منتظر پاسخ شما','resolved'=>'حل‌شده','closed'=>'بسته'];
@endphp
<style>
.support-detail-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-bottom:18px}.support-detail-head h2{font-size:18px;font-weight:900;margin:0 0 6px;overflow-wrap:anywhere}.support-detail-head p{font-size:11px;color:var(--muted);margin:0}.support-thread{display:grid;gap:12px;margin:18px 0}.support-message{max-width:88%;padding:13px 15px;border:1px solid var(--gb);border-radius:15px;background:var(--panel)}.support-message.mine{margin-inline-start:auto;background:rgba(39,135,120,.08);border-color:rgba(39,135,120,.2)}.support-message-head{display:flex;justify-content:space-between;gap:14px;margin-bottom:8px;font-size:10px;color:var(--muted)}.support-message-body{font-size:12px;line-height:2;white-space:pre-wrap;overflow-wrap:anywhere}.support-reply{border:1px solid var(--gb);border-radius:16px;padding:15px;background:var(--panel)}.support-reply label{display:block;font-size:12px;font-weight:800;margin-bottom:8px}.support-reply textarea{width:100%;min-height:120px;border:1px solid var(--gb);border-radius:11px;padding:11px;background:transparent;color:var(--ink);font:inherit;font-size:12px;line-height:2}.support-reply button{margin-top:10px;border:0;border-radius:10px;padding:10px 16px;background:var(--brand,#1f766b);color:#fff;font:inherit;font-size:12px;font-weight:850;cursor:pointer}
@media(max-width:600px){.support-message{max-width:96%}}
</style>
<div class="support-detail-head"><div><h2>{{ $ticket->subject }}</h2><p>{{ $ticket->number }} · ثبت {{ $F::date($ticket->created_at, 'j F Y · H:i') }} @if($ticket->building) · {{ $ticket->building->name }} @endif</p></div><a href="{{ route('support.index') }}" style="font-size:11px;text-decoration:none">بازگشت به درخواست‌ها ←</a></div>
<div class="support-thread">
  @foreach($ticket->messages as $message)
    <article class="support-message {{ $message->sender_type === 'user' ? 'mine' : '' }}">
      <div class="support-message-head"><strong>{{ $message->sender_type === 'admin' ? 'تیم پشتیبانی صابخونه' : 'شما' }}</strong><time>{{ $F::date($message->created_at, 'j F Y · H:i') }}</time></div>
      <div class="support-message-body">{{ $message->body }}</div>
    </article>
  @endforeach
</div>
@if(!in_array($ticket->status, ['resolved','closed'], true))
  <form class="support-reply" method="POST" action="{{ route('support.reply', $ticket) }}">@csrf
    <label for="body">پاسخ شما</label><textarea id="body" name="body" required minlength="2" maxlength="10000" placeholder="پیام خودت رو بنویس...">{{ old('body') }}</textarea>
    @error('body')<div style="color:#dc4444;font-size:11px;margin-top:6px">{{ $message }}</div>@enderror
    <button type="submit">ارسال پاسخ</button>
  </form>
@else
  <div style="padding:15px;border-radius:13px;background:rgba(22,131,74,.09);color:var(--ink);font-size:12px">این درخواست بسته شده است. اگر هنوز به راهنمایی نیاز داری، یک درخواست جدید ثبت کن.</div>
  <a href="{{ route('support.create') }}" style="display:inline-block;margin-top:12px;font-size:12px">ثبت درخواست جدید</a>
@endif
@endsection
