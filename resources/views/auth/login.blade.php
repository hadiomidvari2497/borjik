@extends('layouts.guest')
@section('title', 'ورود | صابخونه')
@section('content')
@if($step === 'code')
  <h1 class="h1">کد تأیید را وارد کنید</h1>
  <p class="lead">کد ۶ رقمی به شماره‌ی <b dir="ltr">{{ \App\Support\Fa::digits($mobile) }}</b> پیامک شد.</p>
  <form method="POST" action="{{ route('login.otp.verify') }}">@csrf
    <div class="field">
      <input class="otp" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••" data-digits required>
      @error('code')<div class="err">{{ $message }}</div>@enderror
    </div>
    <button class="btn" type="submit">ورود</button>
  </form>
  <div class="row">
    <form method="POST" action="{{ route('login.reset') }}">@csrf<button class="lnk" type="submit">ویرایش شماره</button></form>
    <form method="POST" action="{{ route('login.otp') }}" data-countdown="60">@csrf
      <input type="hidden" name="mobile" value="{{ $mobile }}">
      <button class="lnk" type="submit" data-resend>ارسال دوباره‌ی کد<span data-count-label></span></button>
    </form>
  </div>
  @error('mobile')<div class="alert err" style="margin-top:14px">{{ $message }}</div>@enderror
@else
  <h1 class="h1">ورود به صابخونه</h1>
  @if($otpEnabled)
  <p class="lead">با شماره‌ی موبایل وارد شوید یا ثبت‌نام کنید؛ نیازی به رمز عبور نیست.</p>
  @else
  <p class="lead">با شماره‌ی موبایل و رمز عبور وارد شوید.</p>
  @endif

  @if($otpEnabled && $passwordEnabled)
  <div class="seg" role="tablist">
    <button type="button" class="on" data-tab="otp" role="tab">کد پیامکی</button>
    <button type="button" data-tab="pw" role="tab">رمز عبور</button>
  </div>
  @endif

  @if(! $otpEnabled && ! $passwordEnabled)
    <div class="alert err">ورود به سامانه موقتاً غیرفعال است. لطفاً بعداً تلاش کنید.</div>
  @endif

  @if($otpEnabled)
  <form method="POST" action="{{ route('login.otp') }}" data-pane="otp">@csrf
    <div class="field">
      <label for="m1">شماره‌ی موبایل</label>
      <div class="phone"><input id="m1" name="mobile" inputmode="tel" autocomplete="tel" placeholder="۹۱۲۳۴۵۶۷۸۹" value="{{ old('mobile') }}" required></div>
      @error('mobile')<div class="err">{{ $message }}</div>@enderror
    </div>
    <button class="btn" type="submit">دریافت کد تأیید</button>
  </form>
  @endif

  @if($passwordEnabled)
  <form method="POST" action="{{ route('login.password') }}" data-pane="pw" @if($otpEnabled) hidden @endif>@csrf
    <div class="field">
      <label for="m2">شماره‌ی موبایل</label>
      <div class="phone"><input id="m2" name="mobile" inputmode="tel" autocomplete="tel" placeholder="۹۱۲۳۴۵۶۷۸۹" value="{{ old('mobile') }}" required></div>
    </div>
    <div class="field">
      <label for="pw">رمز عبور</label>
      <div class="pw"><input id="pw" type="password" name="password" autocomplete="current-password" required><button type="button">نمایش</button></div>
    </div>
    <button class="btn" type="submit">ورود</button>
  </form>
  @endif

  <p class="fine">با ورود، قوانین و حریم خصوصی صابخونه را می‌پذیرید.</p>
@endif
@endsection
