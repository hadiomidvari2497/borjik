@extends('layouts.guest')
@section('title', 'تأیید دومرحله‌ای | برجیک')
@section('content')
@if($mode === 'challenge')
  <h1 class="h1">تأیید دومرحله‌ای</h1>
  <p class="lead">کد ۶ رقمی برنامه‌ی احراز هویت (مثل Google Authenticator) را وارد کنید.</p>
  <form method="POST" action="{{ url('/two-factor/challenge') }}">@csrf
    <div class="field"><input class="otp" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="••••••" data-digits required>@error('code')<div class="err">{{ $message }}</div>@enderror</div>
    <button class="btn" type="submit">تأیید و ورود</button>
  </form>
@else
  <h1 class="h1">فعال‌سازی تأیید دومرحله‌ای</h1>
  <p class="lead">این کلید را در Google Authenticator یا برنامه‌ی مشابه اضافه کنید، سپس کد نمایش‌داده‌شده را وارد کنید.</p>
  <div class="code-box">{{ $secret }}</div>
  <div class="code-box">{{ $uri }}</div>
  <form method="POST" action="{{ route('profile.two-factor.confirm') }}">@csrf
    <div class="field"><input class="otp" name="code" inputmode="numeric" maxlength="6" placeholder="••••••" data-digits required>@error('code')<div class="err">{{ $message }}</div>@enderror</div>
    <button class="btn" type="submit">فعال‌سازی</button>
  </form>
@endif
@endsection
