@extends('layouts.guest')
@section('title', 'دعوت به ساختمان | برجیک')
@section('content')
<div class="invite-page">
  <div class="invite-mark" aria-hidden="true">ب</div>
  @isset($error)
    <h1 class="h1">دعوت‌نامه</h1>
    <p class="lead">{{ $error }}</p>
    <a class="btn" href="{{ route('login') }}">رفتن به صفحه‌ی ورود</a>
  @else
    <span class="invite-eyebrow">دعوت‌نامه‌ی عضویت</span>
    <h1 class="h1">به «{{ $building ?: 'ساختمان' }}» دعوت شده‌اید</h1>
    <p class="lead">برای تکمیل عضویت، شماره‌ی موبایل دعوت‌شده را تأیید کنید.</p>

    <div class="invite-card">
      <span class="invite-detail-label">نقش پیشنهادی شما</span>
      <b>{{ $role ?: 'عضو ساختمان' }}</b>
      <span class="invite-detail-label">شماره‌ی دعوت‌شده</span>
      <strong dir="ltr" class="invite-mobile">{{ App\Support\Fa::digits($mobile) }}</strong>
    </div>

    <form method="POST" action="{{ route('login.otp') }}" class="invite-form">
      @csrf
      <input type="hidden" name="invite" value="{{ $token }}">
      <input type="hidden" name="mobile" value="{{ $mobile }}">
      <button class="btn" type="submit">دریافت کد و پذیرش دعوت</button>
    </form>
    <p class="fine">کد تأیید به همین شماره‌ی موبایل ارسال می‌شود.</p>
  @endisset
</div>
@endsection
