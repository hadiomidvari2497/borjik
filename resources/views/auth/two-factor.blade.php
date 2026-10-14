@extends('layouts.guest')
@section('title', 'تأیید دومرحله‌ای | برجیک')
@section('content')
@if($mode === 'challenge')
  <h1>تأیید دومرحله‌ای</h1>
  <p class="sub">کد ۶ رقمی برنامه‌ی احراز هویت را وارد کنید.</p>
  <form method="POST" action="{{ url('/two-factor/challenge') }}">@csrf
    <input name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus required>
    @error('code')<div class="err">{{ $message }}</div>@enderror
    <button>تأیید و ورود</button>
  </form>
@else
  <h1>فعال‌سازی تأیید دومرحله‌ای</h1>
  <p class="sub">این کلید را در Google Authenticator یا برنامه‌ی مشابه اضافه کنید، سپس کد نمایش‌داده‌شده را وارد کنید.</p>
  <code>{{ $secret }}</code>
  <code>{{ $uri }}</code>
  <form method="POST" action="{{ route('profile.two-factor.confirm') }}">@csrf
    <input name="code" inputmode="numeric" maxlength="6" required>
    @error('code')<div class="err">{{ $message }}</div>@enderror
    <button>فعال‌سازی</button>
  </form>
@endif
@endsection
