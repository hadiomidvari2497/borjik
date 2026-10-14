@extends('layouts.guest')
@section('title', 'دعوت به ساختمان | برجیک')
@section('content')
@isset($error)
  <h1>دعوت‌نامه</h1>
  <p class="sub">{{ $error }}</p>
  <a href="{{ route('login') }}">رفتن به صفحه‌ی ورود</a>
@else
  <h1>به «{{ $building }}» دعوت شده‌اید</h1>
  <p class="sub">نقش شما: {{ $role }}. برای پذیرش، کد تأیید همین شماره را دریافت کنید.</p>
  <form method="POST" action="{{ route('login.otp') }}">@csrf
    <input type="hidden" name="invite" value="{{ $token }}">
    <label>شماره‌ی موبایل</label>
    <input name="mobile" value="{{ $mobile }}" readonly>
    <button>دریافت کد و پذیرش دعوت</button>
  </form>
@endisset
@endsection
