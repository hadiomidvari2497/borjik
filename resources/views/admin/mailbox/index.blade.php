@extends('admin.layout')

@section('title', 'صندوق ایمیل')

@section('content')
<style>
.mailbox-head{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:20px}.mailbox-title{margin:0;font-size:1.2rem}.mailbox-sub{color:var(--muted,#718096);font-size:.88rem;margin-top:6px}.mailbox-search{display:flex;gap:8px;flex-wrap:wrap}.mailbox-search input{min-width:min(280px,75vw)}.mailbox-table-wrap{overflow:auto;border:1px solid var(--line,#e5e7eb);border-radius:14px}.mailbox-table{width:100%;border-collapse:collapse;min-width:650px}.mailbox-table th,.mailbox-table td{padding:13px 15px;text-align:right;border-bottom:1px solid var(--line,#e5e7eb)}.mailbox-table th{font-size:.82rem;color:var(--muted,#718096);background:var(--surface-2,#f8fafc)}.mailbox-table tr:last-child td{border-bottom:0}.mailbox-table tr.unread td{font-weight:700}.mailbox-table a{color:inherit;text-decoration:none}.mailbox-table a:hover{color:var(--primary,#1c4f4a)}.mailbox-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#16866f;margin-left:8px}.mailbox-empty{text-align:center;padding:38px 15px;color:var(--muted,#718096)}.mailbox-alert{padding:14px 16px;border-radius:12px;margin-bottom:16px;background:var(--surface-2,#f8fafc);border:1px solid var(--line,#e5e7eb)}.mailbox-size{white-space:nowrap;color:var(--muted,#718096);font-size:.82rem}.mailbox-actions{display:flex;gap:8px;flex-wrap:wrap}.mailbox-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 16px}.mailbox-tabs a{padding:8px 14px;border:1px solid var(--line,#e5e7eb);border-radius:10px;color:inherit;text-decoration:none}.mailbox-tabs a.active{background:var(--primary,#1c4f4a);border-color:var(--primary,#1c4f4a);color:#fff}.mail-login{max-width:520px;border:1px solid var(--line,#e5e7eb);border-radius:16px;padding:22px}.mail-login label{display:grid;gap:7px;margin:14px 0;font-weight:650}.mail-login input{width:100%}
</style>
@php($adm = auth('admin')->user())
<div class="mailbox-head">
  <div><h2 class="mailbox-title">صندوق ایمیل @if($account)<span class="mailbox-size">{{ $account->email }}</span>@endif</h2><p class="mailbox-sub">برای هر صندوق، با آدرس و رمز همان ایمیل وارد شوید.</p></div>
  <div class="mailbox-actions">
    @if($adm?->is_super)<a class="btn btn-outline-secondary" href="{{ route('admin.mailbox.settings') }}">مدیریت صندوق‌ها و اتصال</a>@endif
    @if($account)
      <a class="btn btn-primary" href="{{ route('admin.mailbox.compose') }}">ارسال ایمیل</a>
      <form method="POST" action="{{ route('admin.mailbox.logout') }}">@csrf<button class="btn btn-outline-secondary" type="submit">خروج از صندوق</button></form>
    @endif
  </div>
</div>
@if(session('success'))<div class="mailbox-alert" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mailbox-alert" role="alert">{{ $errors->first() }}</div>@endif
@if($connectionError)<div class="mailbox-alert" role="alert"><strong>اتصال صندوق ایمیل برقرار نیست.</strong><p style="margin:6px 0 0">{{ $connectionError }}</p></div>@endif

@if(!$account)
  <section class="mail-login">
    <h3 style="margin-top:0">ورود به صندوق ایمیل</h3>
    <p class="mailbox-sub">آدرس و رمز ایمیلی را وارد کنید که قبلاً روی سرور ساخته شده است. نیازی به اختصاص صندوق به حساب ادمین نیست.</p>
    @if($errors->any())<div class="mailbox-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.mailbox.login') }}">@csrf
      <label>آدرس ایمیل<input class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="info@sabkhoneh.ir" autocomplete="username" required maxlength="254"></label>
      <label>رمز صندوق<input class="form-control" type="password" name="password" autocomplete="current-password" required maxlength="200"></label>
      <button class="btn btn-primary" type="submit">ورود به صندوق</button>
    </form>
  </section>
@else
  <nav class="mailbox-tabs" aria-label="پوشه‌های ایمیل">
    <a href="{{ route('admin.mailbox.index') }}" @class(['active' => $folder === 'INBOX'])>صندوق ورودی</a>
    <a href="{{ route('admin.mailbox.sent') }}" @class(['active' => $folder === 'Sent'])>صندوق خروجی</a>
  </nav>
  <form class="mailbox-search" style="margin-bottom:14px" method="GET" action="{{ $folder === 'Sent' ? route('admin.mailbox.sent') : route('admin.mailbox.index') }}">
    <input class="form-control" type="search" name="q" value="{{ $query }}" maxlength="120" placeholder="جست‌وجو در {{ $folder === 'Sent' ? 'گیرنده و موضوع' : 'فرستنده و موضوع' }}" aria-label="جست‌وجوی ایمیل">
    <button class="btn btn-primary" type="submit">جست‌وجو</button>
    @if($query !== '')<a class="btn btn-outline-secondary" href="{{ route('admin.mailbox.index') }}">پاک‌کردن</a>@endif
  </form>
  <div class="mailbox-table-wrap"><table class="mailbox-table">
    <thead><tr><th>{{ $folder === 'Sent' ? 'گیرنده' : 'فرستنده' }}</th><th>موضوع</th><th>تاریخ</th><th>حجم</th></tr></thead><tbody>
    @forelse($messages as $email)
      <tr @class(['unread' => !$email['seen']])>
        <td><a href="{{ route('admin.mailbox.show', ['uid' => $email['uid'], 'folder' => $folder === 'Sent' ? 'sent' : 'inbox']) }}">@if(!$email['seen'])<span class="mailbox-dot"></span>@endif{{ $email['from'] }}</a></td>
        <td><a href="{{ route('admin.mailbox.show',$email['uid']) }}">{{ $email['subject'] }}</a></td>
        <td>{{ $email['jalali_date'] }}</td>
        <td class="mailbox-size">{{ $email['size'] > 0 ? number_format($email['size'] / 1024, 1).' KB' : '—' }}</td>
      </tr>
    @empty<tr><td colspan="4" class="mailbox-empty">{{ $connectionError ? 'پس از رفع اتصال، ایمیل‌ها اینجا نمایش داده می‌شوند.' : '{{ $folder === 'Sent' ? 'هنوز ایمیلی در صندوق خروجی ثبت نشده است.' : 'ایمیلی برای نمایش پیدا نشد.' }}' }}</td></tr>@endforelse
    </tbody></table></div>
@endif
@endsection
