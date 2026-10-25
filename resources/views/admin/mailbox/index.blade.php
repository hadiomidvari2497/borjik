@extends('admin.layout')

@section('title', 'صندوق ایمیل')

@section('content')
<style>
.mailbox-head{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:20px}.mailbox-title{margin:0;font-size:1.2rem}.mailbox-sub{color:var(--muted,#718096);font-size:.88rem;margin-top:6px}.mailbox-search{display:flex;gap:8px;flex-wrap:wrap}.mailbox-search input{min-width:min(280px,75vw)}.mailbox-table-wrap{overflow:auto;border:1px solid var(--line,#e5e7eb);border-radius:14px}.mailbox-table{width:100%;border-collapse:collapse;min-width:650px}.mailbox-table th,.mailbox-table td{padding:13px 15px;text-align:right;border-bottom:1px solid var(--line,#e5e7eb)}.mailbox-table th{font-size:.82rem;color:var(--muted,#718096);background:var(--surface-2,#f8fafc)}.mailbox-table tr:last-child td{border-bottom:0}.mailbox-table tr.unread td{font-weight:700}.mailbox-table a{color:inherit;text-decoration:none}.mailbox-table a:hover{color:var(--primary,#1c4f4a)}.mailbox-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#16866f;margin-left:8px}.mailbox-empty{text-align:center;padding:38px 15px;color:var(--muted,#718096)}.mailbox-alert{padding:14px 16px;border-radius:12px;margin-bottom:16px;background:var(--surface-2,#f8fafc);border:1px solid var(--line,#e5e7eb)}.mailbox-size{white-space:nowrap;color:var(--muted,#718096);font-size:.82rem}
</style>
<div class="mailbox-head">
  <div><h2 class="mailbox-title">صندوق ورودی <span class="mailbox-size">info@sabkhoneh.ir</span></h2><p class="mailbox-sub">پیام‌های دریافتی روی سرور ایمیل صابخونه</p></div>
  <form class="mailbox-search" method="GET" action="{{ route('admin.mailbox.index') }}">
    <input class="form-control" type="search" name="q" value="{{ $query }}" maxlength="120" placeholder="جست‌وجو در فرستنده و موضوع" aria-label="جست‌وجوی ایمیل">
    <button class="btn btn-primary" type="submit">جست‌وجو</button>
    @if($query !== '')<a class="btn btn-outline-secondary" href="{{ route('admin.mailbox.index') }}">پاک‌کردن</a>@endif
  </form>
</div>
@if($connectionError)
  <div class="mailbox-alert" role="alert"><strong>اتصال صندوق ایمیل برقرار نیست.</strong><p style="margin:6px 0 0">{{ $connectionError }}</p><p style="margin:8px 0 0">تنظیمات <code>MAILBOX_HOST</code>، <code>MAILBOX_USERNAME</code> و <code>MAILBOX_PASSWORD</code> را در محیط سرور تنظیم کن.</p></div>
@endif
<div class="mailbox-table-wrap">
<table class="mailbox-table">
  <thead><tr><th>فرستنده</th><th>موضوع</th><th>تاریخ</th><th>حجم</th></tr></thead>
  <tbody>
  @forelse($messages as $email)
    <tr @class(['unread' => !$email['seen']])>
      <td><a href="{{ route('admin.mailbox.show', $email['uid']) }}">@if(!$email['seen'])<span class="mailbox-dot"></span>@endif{{ $email['from'] }}</a></td>
      <td><a href="{{ route('admin.mailbox.show', $email['uid']) }}">{{ $email['subject'] }}</a></td>
      <td>{{ $email['jalali_date'] }}</td>
      <td class="mailbox-size">{{ $email['size'] > 0 ? number_format($email['size'] / 1024, 1).' KB' : '—' }}</td>
    </tr>
  @empty
    <tr><td colspan="4" class="mailbox-empty">{{ $connectionError ? 'پس از تنظیم اتصال، ایمیل‌ها اینجا نمایش داده می‌شوند.' : 'ایمیلی برای نمایش پیدا نشد.' }}</td></tr>
  @endforelse
  </tbody>
</table>
</div>
@endsection
