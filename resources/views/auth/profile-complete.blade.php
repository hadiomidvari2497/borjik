@php($done = false)
@extends('layouts.guest')
@section('title', 'تکمیل پروفایل | صابخونه')
@section('wide', 'wide')
@section('content')
  <div class="steps" aria-hidden="true"><i class="on"></i><i class="on"></i><i></i></div>
  <h1 class="h1">یک قدم تا شروع کار</h1>
  <p class="lead">برای ادامه، اطلاعات هویتی‌تان را کامل کنید. عکس پروفایل اختیاری است و بعداً هم می‌توانید اضافه کنید.</p>
  @include('auth.partials.profile-form')
@endsection
