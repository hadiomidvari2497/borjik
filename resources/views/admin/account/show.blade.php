@extends('admin.layout')
@section('title', 'حساب من')
@section('content')
<div class="card">
  <div class="page-h"><h2>تأیید دومرحله‌ای (۲FA)</h2></div>
  @if($me->two_factor_enabled)
    <p style="margin:8px 0 12px"><span class="pill">فعال</span> برای ورود، علاوه بر رمز عبور کد برنامه‌ی احراز هویت هم لازم است.</p>
    <form method="POST" action="{{ route('admin.account.two-factor.disable') }}" class="fgrid" data-confirm="تأیید دومرحله‌ای غیرفعال شود؟" data-confirm-title="غیرفعال‌سازی ۲FA" data-confirm-icon="warning">
      @csrf @method('DELETE')
      <div class="fld">
        <label for="d_password">رمز عبور برای غیرفعال‌سازی</label>
        <input id="d_password" type="password" name="password" dir="ltr" autocomplete="current-password" required>
        @error('password')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld"><button class="btn dng" type="submit" style="margin-top:26px">غیرفعال‌سازی</button></div>
    </form>
  @else
    <p style="margin:8px 0 12px"><span class="pill u">غیرفعال</span> با فعال‌سازی، امنیت ورود به پنل مدیریت بالاتر می‌رود.</p>
    <a class="btn" href="{{ route('admin.account.two-factor') }}">فعال‌سازی تأیید دومرحله‌ای</a>
  @endif
</div>

<div class="card" style="margin-top:16px">
  <div class="page-h"><h2>تغییر رمز عبور</h2></div>
  <form method="POST" action="{{ route('admin.account.password') }}" style="margin-top:12px">
    @csrf
    <div class="fgrid">
      <div class="fld">
        <label for="current_password">رمز عبور فعلی</label>
        <input id="current_password" type="password" name="current_password" dir="ltr" autocomplete="current-password" required>
        @error('current_password')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="password">رمز عبور جدید</label>
        <input id="password" type="password" name="password" dir="ltr" autocomplete="new-password" required>
        @error('password')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="password_confirmation">تکرار رمز عبور جدید</label>
        <input id="password_confirmation" type="password" name="password_confirmation" dir="ltr" autocomplete="new-password" required>
      </div>
    </div>
    <div class="fbar"><button class="btn" type="submit">تغییر رمز عبور</button></div>
  </form>
</div>
@endsection
