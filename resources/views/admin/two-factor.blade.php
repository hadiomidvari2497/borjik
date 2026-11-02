@extends('layouts.admin-guest')
@section('title', 'تأیید دومرحله‌ای مدیر')
@section('content')
<h1 class="h1">تأیید دومرحله‌ای</h1>
<p class="lead">کد ۶ رقمی برنامه‌ی احراز هویت (مثل Google Authenticator) را وارد کنید.</p>
<form method="POST" action="{{ route('admin.two-factor.verify') }}">@csrf
  <div class="field">
    <input class="otp" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="••••••" data-digits required autofocus>
    @error('code')<div class="err">{{ $message }}</div>@enderror
  </div>
  <button class="btn" type="submit">تأیید و ورود با Authenticator</button>
</form>
@if($smsAvailable)
  <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line)">
    <p class="lead">یا از روش پیامکی استفاده کنید. شماره مقصد: <span dir="ltr">{{ $maskedMobile }}</span></p>
    <form method="POST" action="{{ route('admin.two-factor.sms.request') }}">@csrf
      <button class="btn" type="submit">ارسال کد پیامکی</button>
    </form>
    <form method="POST" action="{{ route('admin.two-factor.sms.verify') }}" style="margin-top:12px">@csrf
      <div class="field">
        <input class="otp" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="کد پیامکی ۶ رقمی" data-digits required>
        @error('code')<div class="err">{{ $message }}</div>@enderror
      </div>
      <button class="btn" type="submit">تأیید پیامک و ورود</button>
    </form>
  </div>
@endif
<p class="fine"><a href="{{ route('admin.login') }}">بازگشت به ورود</a></p>
@endsection
