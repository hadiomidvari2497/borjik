@extends('errors.layout')
@section('title', 'درخواست‌های زیاد')
@section('tone', 't-warn')
@section('label', 'BUSY')
@section('code', '429')
@section('heading')
<em>آسانسور</em> شلوغ است
@endsection
@section('text', 'تعداد درخواست‌ها از حد مجاز گذشت. چند لحظه صبر کنید و دوباره تلاش کنید.')
@section('extra')
<span class="chip">ترافیک بالا</span>
@endsection
@section('actions')
<button class="btn btn-p" type="button" data-act="reload">تلاش دوباره <span aria-hidden="true">↻</span></button>
<a class="btn btn-g" href="{{ url('/') }}">صفحه اصلی</a>
@endsection
