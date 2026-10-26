@extends('admin.layout')

@section('title', 'مشاهده ایمیل')

@section('content')
<style>
.mail-view-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px}.mail-view-card{border:1px solid var(--line,#e5e7eb);border-radius:16px;overflow:hidden}.mail-view-subject{padding:20px;border-bottom:1px solid var(--line,#e5e7eb);font-size:1.15rem;font-weight:750;overflow-wrap:anywhere}.mail-view-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:18px 20px;background:var(--surface-2,#f8fafc)}.mail-view-meta span{display:block;color:var(--muted,#718096);font-size:.82rem;margin-bottom:4px}.mail-view-meta strong{overflow-wrap:anywhere}.mail-view-body{padding:22px;white-space:pre-wrap;overflow-wrap:anywhere;line-height:2;min-height:180px}.mail-view-note{color:var(--muted,#718096);font-size:.85rem;margin-top:12px}@media(max-width:600px){.mail-view-meta{grid-template-columns:1fr}}
</style>
<div class="mail-view-head">
  <a class="btn btn-outline-secondary" href="{{ $folder === 'Sent' ? route('admin.mailbox.sent') : route('admin.mailbox.index') }}">→ بازگشت به {{ $folder === 'Sent' ? 'صندوق خروجی' : 'صندوق ورودی' }}</a>
  <span class="mailbox-size">{{ $message['jalali_date'] }}</span>
</div>
<article class="mail-view-card">
  <div class="mail-view-subject">{{ $message['subject'] }}</div>
  <div class="mail-view-meta">
    <div><span>فرستنده</span><strong>{{ $message['from'] ?: 'نامشخص' }}</strong></div>
    <div><span>گیرنده</span><strong>{{ $message['to'] ?: $account->email }}</strong></div>
    <div><span>{{ $folder === 'Sent' ? 'تاریخ ارسال' : 'تاریخ دریافت' }}</span><strong>{{ $message['jalali_date'] }}</strong></div>
    <div><span>شناسه پیام</span><strong>#{{ $message['uid'] }}</strong></div>
  </div>
  <div class="mail-view-body">{{ $message['message'] }}</div>
</article>
<p class="mail-view-note">برای امنیت، محتوای پیام به‌صورت متن نمایش داده می‌شود و اسکریپت‌ها و HTML اجرایی ایمیل اجرا نمی‌شوند.</p>
@endsection
