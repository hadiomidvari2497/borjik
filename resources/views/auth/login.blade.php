@extends('layouts.guest')
@section('title', 'ورود | برجیک')
@section('content')
@if($step === 'code')
  <h1>کد تأیید را وارد کنید</h1>
  <p class="sub">کد ۶ رقمی به شماره‌ی {{ $mobile }} پیامک شد.</p>
  <form method="POST" action="{{ route('login.otp.verify') }}">@csrf
    <label for="code">کد تأیید</label>
    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus required>
    @error('code')<div class="err">{{ $message }}</div>@enderror
    <button>ورود</button>
  </form>
  <form method="POST" action="{{ route('login.otp') }}">@csrf
    <input type="hidden" name="mobile" value="{{ $mobile }}">
    <button class="link" type="submit">ارسال دوباره‌ی کد</button>
    @error('mobile')<div class="err">{{ $message }}</div>@enderror
  </form>
@else
  <h1>ورود به برجیک</h1>
  <p class="sub">با شماره‌ی موبایل وارد شوید یا ثبت‌نام کنید.</p>
  <div class="tabs">
    <button type="button" class="on" data-t="otp">کد پیامکی</button>
    <button type="button" data-t="pw">رمز عبور</button>
  </div>
  <form method="POST" action="{{ route('login.otp') }}" data-p="otp">@csrf
    <label for="m1">شماره‌ی موبایل</label>
    <input id="m1" name="mobile" inputmode="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" value="{{ old('mobile') }}" required>
    @error('mobile')<div class="err">{{ $message }}</div>@enderror
    <button>دریافت کد</button>
  </form>
  <form method="POST" action="{{ route('login.password') }}" data-p="pw" hidden>@csrf
    <label for="m2">شماره‌ی موبایل</label>
    <input id="m2" name="mobile" inputmode="tel" value="{{ old('mobile') }}" required>
    <label for="pw">رمز عبور</label>
    <input id="pw" type="password" name="password" autocomplete="current-password" required>
    <button>ورود</button>
  </form>
  <script>
    document.querySelectorAll('.tabs button').forEach(b => b.onclick = () => {
      document.querySelectorAll('.tabs button').forEach(x => x.classList.toggle('on', x === b));
      document.querySelectorAll('form[data-p]').forEach(f => f.hidden = f.dataset.p !== b.dataset.t);
    });
  </script>
@endif
@endsection
