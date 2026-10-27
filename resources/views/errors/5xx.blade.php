@extends('errors.layout')
@section('title', 'خطای سرور')
@section('tone', 't-err')
@section('label', 'ERROR')
@section('code', isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : '5xx')
@section('heading')
مشکلی در <em>موتورخانه</em> پیش آمده
@endsection
@section('text', 'خطایی از سمت ما رخ داد و تیم فنی در جریان است. چند دقیقه دیگر دوباره تلاش کنید.')
@section('extra')
<span class="chip">خطای سرور</span>
@endsection
@section('actions')
<button class="btn btn-p" type="button" data-act="reload">تلاش دوباره <span aria-hidden="true">↻</span></button>
<a class="btn btn-g" href="{{ url('/') }}">صفحه اصلی</a>
@endsection
