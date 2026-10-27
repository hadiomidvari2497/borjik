@extends('errors.layout')
@section('title', 'دسترسی مجاز نیست')
@section('tone', 't-warn')
@section('label', 'ACCESS')
@section('code', '403')
@section('heading')
دسترسی به این <em>طبقه</em> مجاز نیست
@endsection
@section('text', 'برای ورود به این بخش مجوز لازم است. اگر فکر می‌کنید اشتباهی رخ داده، با مدیر ساختمان یا پشتیبانی تماس بگیرید.')
@section('extra')
<span class="chip">ورود ممنوع</span>
@endsection
@section('actions')
<a class="btn btn-p" href="{{ url('/') }}">بازگشت به صفحه اصلی <span aria-hidden="true">←</span></a>
<a class="btn btn-g" href="{{ url('/login') }}">ورود به سامانه</a>
<button class="btn btn-g" type="button" data-act="back">صفحه قبل</button>
@endsection
