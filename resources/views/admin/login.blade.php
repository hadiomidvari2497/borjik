@extends('layouts.admin-guest')
@section('title', 'ورود مدیر سیستم')
@section('content')
<h1 class="h1">ورود به پنل مدیریت</h1>
<p class="lead">برای مدیریت پلن‌های اشتراک و تنظیمات سراسری سامانه وارد شوید.</p>
<form method="POST" action="{{ route('admin.login.submit') }}">
  @csrf
  <div class="field">
    <label for="username">نام کاربری</label>
    <input id="username" name="username" dir="ltr" value="{{ old('username') }}" autocomplete="username" required autofocus>
    @error('username')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div class="field">
    <label for="password">رمز عبور</label>
    <div class="pw"><input id="password" type="password" name="password" dir="ltr" autocomplete="current-password" required><button type="button">نمایش</button></div>
  </div>
  <label class="check"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
  <button class="btn" type="submit">ورود</button>
</form>
@endsection
