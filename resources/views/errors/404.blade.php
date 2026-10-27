@extends('errors.layout')
@section('title', 'صفحه پیدا نشد')
@section('tone', '')
@section('label', 'FLOOR')
@section('code', '404')
@section('heading')
این <em>طبقه</em> در ساختمان ما وجود ندارد
@endsection
@section('text', 'صفحه‌ای که دنبالش بودید پیدا نشد. ممکن است آدرس را اشتباه وارد کرده باشید یا صفحه جابه‌جا شده باشد. با دکمه‌های زیر به مسیر درست برگردید.')
@section('extra')
<div class="arrows"><i>▲</i><i class="on">▼</i></div>
@endsection
@section('actions')
<a class="btn btn-p" href="{{ url('/') }}">بازگشت به صفحه اصلی <span aria-hidden="true">←</span></a>
<a class="btn btn-g" href="{{ url('/articles') }}">مطالب و راهنماها</a>
<button class="btn btn-g" type="button" data-act="back">صفحه قبل</button>
@endsection
