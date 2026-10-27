@extends('errors.layout')
@section('title', 'درخواست قابل انجام نیست')
@section('tone', 't-warn')
@section('label', 'REQUEST')
@section('code', isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : '4xx')
@section('heading')
درخواست شما <em>قابل انجام</em> نیست
@endsection
@section('text', 'درخواست معتبر نبود یا اجازه‌ی انجام آن وجود ندارد. به صفحه اصلی برگردید و دوباره تلاش کنید.')
@section('extra')
<span class="chip">درخواست نامعتبر</span>
@endsection
@section('actions')
<a class="btn btn-p" href="{{ url('/') }}">بازگشت به صفحه اصلی <span aria-hidden="true">←</span></a>
<button class="btn btn-g" type="button" data-act="back">صفحه قبل</button>
@endsection
