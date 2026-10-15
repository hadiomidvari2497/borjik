@php $F = \App\Support\Fa::class; @endphp
@extends('layouts.app')
@section('title', 'اشتراک')
@section('content')
<div class="card">
  <div class="page-h"><h2>اشتراک برجیک</h2></div>
  @if($subscription)
    <div class="warnbox" style="margin:10px 0;background:var(--brand-soft);border-color:transparent">
      اشتراک فعال دارید:
      <b>{{ $F::num($subscription->units_limit) }} واحد</b>
      تا
      <b>{{ $F::date($subscription->expires_at, 'Y/m/d') }}</b>
      @if($subscription->plan)<span class="pill" style="margin-inline-start:8px">{{ $subscription->plan->name }}</span>@endif
    </div>
    <div class="fbar">
      <a class="btn" href="{{ route('buildings.create') }}">ساخت ساختمان</a>
      <a class="btn sec" href="{{ route('dashboard') }}">بازگشت به داشبورد</a>
    </div>
  @else
    <p style="color:var(--muted);margin:8px 0 16px;line-height:1.8">
      برای ساخت ساختمان و استفاده از امکانات سامانه، یکی از پلن‌های زیر را انتخاب کنید.
    </p>
  @endif
</div>

<div class="card">
  <div class="page-h"><h2>پلن‌ها</h2></div>
  @if($plans->isEmpty())
    <div class="empty">پلنی تعریف نشده است.</div>
  @else
    <div class="feat" style="margin-top:8px">
      @foreach($plans as $plan)
        <div class="card" style="margin:0">
          <b>{{ $plan->name }}</b>
          <p style="margin:8px 0;color:var(--muted);line-height:1.7">
            سقف واحد: <b>{{ $F::num($plan->units_limit) }}</b><br>
            مدت: <b>{{ $F::num($plan->duration_months) }} ماه</b><br>
            قیمت: <b>{{ $F::money($plan->price) }}</b>
          </p>
          <form method="POST" action="{{ route('subscription.subscribe', $plan->id) }}" data-confirm="این پلن فعال شود؟" data-confirm-title="فعال‌سازی اشتراک">
            @csrf
            <button class="btn" type="submit" style="width:100%;justify-content:center">
              {{ $subscription ? 'تمدید / تعویض به این پلن' : 'انتخاب و فعال‌سازی' }}
            </button>
          </form>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
