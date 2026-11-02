@extends('layouts.app')
@section('title', 'پشتیبانی صابخونه')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $statusLabels = ['open'=>'ثبت‌شده','in_progress'=>'در حال بررسی','waiting_customer'=>'منتظر پاسخ شما','resolved'=>'حل‌شده','closed'=>'بسته'];
  $statusColors = ['open'=>'#2563eb','in_progress'=>'#0f766e','waiting_customer'=>'#b7791f','resolved'=>'#16834a','closed'=>'#64748b'];
@endphp
<style>
.support-head{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:18px}.support-head h2{font-size:19px;margin:0 0 5px;font-weight:900}.support-head p{font-size:12px;color:var(--muted);margin:0;line-height:1.9}.support-new{display:inline-flex;align-items:center;gap:8px;padding:10px 15px;border-radius:12px;background:var(--brand,#1f766b);color:#fff;text-decoration:none;font-size:12px;font-weight:800}.support-list{display:grid;gap:10px}.support-item{display:flex;align-items:center;gap:13px;padding:15px;border:1px solid var(--gb);border-radius:15px;background:var(--panel);color:var(--ink);text-decoration:none;transition:border-color .18s,transform .18s}.support-item:hover{border-color:var(--brand,#1f766b);transform:translateY(-1px)}.support-item-main{flex:1;min-width:0}.support-item-title{font-size:13px;font-weight:850;display:block;overflow-wrap:anywhere}.support-item-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;color:var(--muted);font-size:10px}.support-status{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;white-space:nowrap}.support-empty{padding:38px 18px;text-align:center;border:1px dashed var(--gb);border-radius:17px}.support-empty strong{display:block;margin-bottom:7px}.support-empty p{color:var(--muted);font-size:12px;margin:0 0 15px}
</style>
<div class="support-head"><div><h2>مرکز پشتیبانی</h2><p>سؤال یا مشکلت رو ثبت کن؛ پاسخ تیم صابخونه رو همین‌جا دریافت می‌کنی.</p></div><a class="support-new" href="{{ route('support.create') }}">＋ ثبت درخواست جدید</a></div>
@if($tickets->isEmpty())
  <div class="support-empty"><strong>هنوز درخواستی ثبت نکردی</strong><p>اگر به راهنمایی یا بررسی فنی نیاز داری، درخواستت رو برای تیم پشتیبانی بفرست.</p><a class="support-new" href="{{ route('support.create') }}">ثبت اولین درخواست</a></div>
@else
  <div class="support-list">
    @foreach($tickets as $ticket)
      <a class="support-item" href="{{ route('support.show', $ticket) }}">
        <span class="support-item-main">
          <span class="support-item-title">{{ $ticket->subject }}</span>
          <span class="support-item-meta"><span>{{ $ticket->number }}</span><span>{{ $categories[$ticket->category] ?? 'عمومی' }}</span><span>{{ $F::date($ticket->updated_at, 'j F Y · H:i') }}</span><span>{{ $F::num($ticket->messages_count) }} پیام</span></span>
        </span>
        <span class="support-status" style="background:{{ $statusColors[$ticket->status] ?? '#64748b' }}18;color:{{ $statusColors[$ticket->status] ?? '#64748b' }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
        <span aria-hidden="true" style="color:var(--muted)">‹</span>
      </a>
    @endforeach
  </div>
  <div style="margin-top:18px">{{ $tickets->links() }}</div>
@endif
@endsection
