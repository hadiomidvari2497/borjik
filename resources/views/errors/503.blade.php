@extends('errors.layout')
@section('title', 'در حال سرویس')
@section('tone', 't-warn')
@section('label', 'SERVICE')
@section('code', '503')
@section('heading')
<em>آسانسور</em> در حال سرویس است
@endsection
@section('text', 'در حال به‌روزرسانی و نگهداری سامانه هستیم. چند دقیقه دیگر برگردید؛ به‌زودی دوباره در خدمتیم.')
@section('extra')
<span class="chip">در حال سرویس</span>
@endsection
@section('actions')
<button class="btn btn-p" type="button" data-act="reload">تلاش دوباره <span aria-hidden="true">↻</span></button>
<a class="btn btn-g" href="{{ url('/') }}">صفحه اصلی</a>
@endsection
