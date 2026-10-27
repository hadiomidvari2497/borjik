@extends('errors.layout')
@section('title', 'نشست منقضی شد')
@section('tone', 't-warn')
@section('label', 'SESSION')
@section('code', '419')
@section('heading')
مدت اعتبار <em>کارت</em> شما به پایان رسید
@endsection
@section('text', 'صفحه مدت زیادی باز مانده و نشست شما منقضی شده است. صفحه را دوباره بارگذاری کنید و عملیات را از نو انجام دهید.')
@section('extra')
<span class="chip">نشست منقضی</span>
@endsection
@section('actions')
<button class="btn btn-p" type="button" data-act="reload">تلاش دوباره <span aria-hidden="true">↻</span></button>
<a class="btn btn-g" href="{{ url('/') }}">صفحه اصلی</a>
@endsection
