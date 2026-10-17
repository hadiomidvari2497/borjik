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
  <button class="btn" type="submit">تأیید و ورود</button>
</form>
<p class="fine"><a href="{{ route('admin.login') }}">بازگشت به ورود</a></p>
@endsection
