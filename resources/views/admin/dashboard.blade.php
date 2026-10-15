@extends('admin.layout')
@section('title', 'داشبورد مدیریت')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<div class="feat">
  <div class="card"><b>{{ $F::num($usersCount) }}</b><p>کاربران</p></div>
  <div class="card"><b>{{ $F::num($buildingsCount) }}</b><p>ساختمان‌ها</p></div>
  <div class="card"><b>{{ $F::num($activeSubs) }}</b><p>اشتراک فعال</p></div>
  <div class="card"><b>{{ $F::num($activePlans) }} / {{ $F::num($plansCount) }}</b><p>پلن فعال / کل</p></div>
</div>
<div class="card" style="margin-top:16px">
  <div class="page-h"><h2>دسترسی سریع</h2></div>
  <div class="fbar">
    <a class="btn" href="{{ route('admin.plans.index') }}">مدیریت پلن‌های اشتراک</a>
    <a class="btn sec" href="{{ route('admin.plans.create') }}">+ پلن جدید</a>
  </div>
</div>
@endsection
