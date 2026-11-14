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

@if($me->two_factor_enabled)
<div class="card" style="margin-top:16px">
  <div class="page-h"><h2>کدهای بازیابی اضطراری</h2></div>
  <p style="line-height:1.9;margin:8px 0 12px">اگر هم پیامک و هم Google Authenticator در دسترس نبودند، با یکی از این کدها وارد پنل می‌شوید. هر کد فقط یک بار قابل استفاده است و کدها را باید خارج از دستگاه و در جای امن نگهداری کنید.</p>
  @if(session('recovery_codes'))
    <div style="padding:14px;border:1px solid var(--line);border-radius:10px;margin-bottom:14px">
      <strong>کدهای جدید؛ فقط همین بار نمایش داده می‌شوند:</strong>
      <div dir="ltr" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:12px;font-family:monospace">
        @foreach(session('recovery_codes') as $recoveryCode)
          <code style="padding:8px;background:var(--surface, #f5f7f6);border-radius:6px;text-align:center">{{ $recoveryCode }}</code>
        @endforeach
      </div>
    </div>
  @endif
  <p class="muted">تعداد کدهای باقی‌مانده: {{ count($me->two_factor_recovery_codes ?? []) }} از ۱۰</p>
  <form method="POST" action="{{ route('admin.account.recovery-codes') }}" style="margin-top:12px">
    @csrf
    <div class="fgrid">
      <div class="fld"><label for="recovery_password">رمز عبور فعلی</label><input id="recovery_password" type="password" name="password" dir="ltr" autocomplete="current-password" required>@error('password')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="recovery_authenticator_code">کد فعلی Google Authenticator</label><input id="recovery_authenticator_code" name="authenticator_code" inputmode="numeric" maxlength="6" dir="ltr" autocomplete="one-time-code" required>@error('authenticator_code')<div class="e">{{ $message }}</div>@enderror</div>
    </div>
    @error('recovery_codes')<div class="e">{{ $message }}</div>@enderror
    <div class="fbar"><button class="btn" type="submit">{{ count($me->two_factor_recovery_codes ?? []) ? 'تولید مجدد ۱۰ کد و ابطال کدهای قبلی' : 'تولید ۱۰ کد بازیابی' }}</button></div>
  </form>
</div>
@endif

<div class="card" style="margin-top:16px">
  <div class="page-h"><h2>شماره موبایل و ورود پیامکی</h2></div>
  <p style="line-height:1.9;margin:8px 0 12px">این شماره فقط برای حساب مدیر پنل کل سایت استفاده می‌شود. برای فعال‌کردن پیامک جایگزین Google Authenticator، ابتدا مالکیت شماره را تأیید کنید.</p>
  <form method="POST" action="{{ route('admin.account.mobile') }}">@csrf
    <div class="fgrid">
      <div class="fld"><label for="admin_mobile">شماره موبایل</label><input id="admin_mobile" name="mobile" type="tel" inputmode="tel" dir="ltr" maxlength="16" value="{{ old('mobile', $me->mobile) }}" placeholder="09xxxxxxxxx" required>@error('mobile')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><button class="btn" type="submit" style="margin-top:26px">ذخیره شماره</button></div>
    </div>
  </form>
  @if($me->mobile)
    <p style="margin:10px 0">@if($me->mobile_verified_at)<span class="pill">شماره تأیید شده: {{ $me->mobile }}</span>@else<span class="pill u">شماره تأیید نشده: {{ $me->mobile }}</span>@endif</p>
    @unless($me->mobile_verified_at)
      <form method="POST" action="{{ route('admin.account.mobile.request') }}" style="margin-top:10px">@csrf<button class="btn sec" type="submit">ارسال کد تأیید پیامکی</button></form>
      <form method="POST" action="{{ route('admin.account.mobile.verify') }}" class="fgrid" style="margin-top:12px">@csrf
        <div class="fld"><label for="mobile_code">کد ۶ رقمی پیامک‌شده</label><input id="mobile_code" name="code" inputmode="numeric" maxlength="6" dir="ltr" autocomplete="one-time-code" required>@error('mobile_code')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><button class="btn" type="submit" style="margin-top:26px">تأیید شماره</button></div>
      </form>
    @endunless
  @endif
  <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:14px">
    <h3 style="margin:0 0 8px">ورود با پیامک به‌عنوان روش جایگزین</h3>
    @if($me->two_factor_sms_enabled)<p><span class="pill">فعال</span> کد پیامکی در صفحه ورود دومرحله‌ای قابل درخواست است.</p>@else<p class="muted">برای استفاده از پیامک، ابتدا Google Authenticator و شماره موبایل را تأیید کنید.</p>@endif
    @error('sms_two_factor')<div class="e">{{ $message }}</div>@enderror
    <form method="POST" action="{{ route('admin.account.sms-two-factor') }}" style="margin-top:10px">@csrf
      <input type="hidden" name="enabled" value="{{ $me->two_factor_sms_enabled ? 0 : 1 }}">
      <button class="btn {{ $me->two_factor_sms_enabled ? 'sec' : '' }}" type="submit" @if(!$me->two_factor_sms_enabled && (!$me->two_factor_enabled || !$me->mobile_verified_at)) disabled @endif>{{ $me->two_factor_sms_enabled ? 'غیرفعال‌کردن ورود پیامکی' : 'فعال‌سازی ورود پیامکی' }}</button>
    </form>
  </div>
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
