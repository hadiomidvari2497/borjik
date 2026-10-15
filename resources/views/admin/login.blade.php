@extends('layouts.guest')
@section('title', 'ورود مدیر سیستم')
@section('content')
<h1 class="h1">پنل مدیریت برجیک</h1>
<p class="lead">ورود مدیر اصلی سیستم برای مدیریت پلن‌های اشتراک و تنظیمات سراسری.</p>
<form method="POST" action="{{ route('admin.login.submit') }}">
  @csrf
  <div class="fld">
    <label for="username">نام کاربری</label>
    <input id="username" name="username" value="{{ old('username') }}" required autofocus>
    @error('username')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div class="fld">
    <label for="password">رمز عبور</label>
    <input id="password" type="password" name="password" required>
  </div>
  <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin:10px 0">
    <input type="checkbox" name="remember" value="1" style="width:auto"> مرا به خاطر بسپار
  </label>
  <button class="btn" type="submit" style="width:100%;justify-content:center">ورود</button>
</form>
@endsection
