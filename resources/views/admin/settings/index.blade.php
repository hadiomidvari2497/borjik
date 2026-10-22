@extends('admin.layout')
@section('title', 'تنظیمات سایت')
@section('content')
<div class="card">
  <div class="page-h"><h2>تنظیمات کلی سایت</h2><div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn secondary" href="{{ route('admin.seo.index') }}">مرکز سئو</a><a class="btn secondary" href="{{ route('admin.marketing-content.edit') }}">محتوای صفحهٔ اصلی</a><a class="btn secondary" href="{{ route('admin.articles.index') }}">مدیریت مقالات</a></div></div>
  <form method="POST" action="{{ route('admin.settings.update') }}" style="margin-top:12px">
    @csrf @method('PUT')

    <h3 style="margin:0 0 8px;font-size:1rem">روش‌های ورود کاربران</h3>
    <p style="color:var(--muted);font-size:13px;margin:0 0 10px;line-height:1.8">
      حداقل یک روش باید فعال بماند. اگر فقط «رمز عبور» فعال باشد، کاربرانی که هنوز رمز تعیین نکرده‌اند نمی‌توانند وارد شوند؛
      لینک دعوت ساختمان با کد تأیید همچنان برای دعوت‌شدگان کار می‌کند. تأیید دومرحله‌ای فقط روی ورود با رمز اعمال می‌شود.
    </p>
    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:6px">
      <label style="display:flex;gap:8px;align-items:center">
        <input type="checkbox" name="otp_enabled" value="1" style="width:auto" @checked(old('otp_enabled', $otp))>
        ورود با کد یکبارمصرف پیامکی
      </label>
      <label style="display:flex;gap:8px;align-items:center">
        <input type="checkbox" name="password_enabled" value="1" style="width:auto" @checked(old('password_enabled', $password))>
        ورود با شماره موبایل و رمز عبور
      </label>
      @error('otp_enabled')<div class="e">{{ $message }}</div>@enderror
    </div>

    <h3 style="margin:22px 0 8px;font-size:1rem">اطلاعات عمومی و پشتیبانی</h3>
    <div class="fgrid">
      <div class="fld">
        <label for="site_name">نام سایت *</label>
        <input id="site_name" name="site_name" value="{{ old('site_name', $siteName) }}" required>
        @error('site_name')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="support_phone">شماره‌ی پشتیبانی</label>
        <input id="support_phone" name="support_phone" dir="ltr" value="{{ old('support_phone', $supportPhone) }}">
        @error('support_phone')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="support_email">ایمیل پشتیبانی</label>
        <input id="support_email" name="support_email" type="email" dir="ltr" value="{{ old('support_email', $supportEmail) }}">
        @error('support_email')<div class="e">{{ $message }}</div>@enderror
      </div>
    </div>

    <div class="fbar"><button class="btn" type="submit">ذخیره تنظیمات</button></div>
  </form>
</div>
@endsection
