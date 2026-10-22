@php $F = \App\Support\Fa::class; @endphp
@extends('layouts.app')
@section('title', 'اشتراک')
@section('content')
<div class="card">
  <div class="page-h"><h2>اشتراک برجیک</h2></div>
  @if(session('error'))<div class="warnbox" style="margin:10px 0">{{ session('error') }}</div>@endif
  @if(session('status'))<div class="warnbox" style="margin:10px 0">{{ session('status') }}</div>@endif
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
      پلن‌های رایگان بدون پرداخت فعال می‌شوند؛ برای پلن‌های پولی باید پرداخت در درگاه تأیید شود.
    </p>
  @endif
</div>

<div class="card">
  <div class="page-h"><h2>پلن‌ها</h2></div>
  @if($plans->isEmpty())
    <div class="empty">در حال حاضر پلنی برای نمایش وجود ندارد. لطفاً بعداً دوباره سر بزنید.</div>
  @else
    <div class="feat" style="margin-top:8px">
      @foreach($plans as $plan)
        @php $free = (int) $plan->price === 0; @endphp
        <div class="card" style="margin:0">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
            <b>{{ $plan->name }}</b>
            @if($free)<span class="pill">رایگان</span>@endif
          </div>
          <p style="margin:8px 0;color:var(--muted);line-height:1.7">
            سقف واحد: <b>{{ $F::num($plan->units_limit) }}</b><br>
            مدت: <b>{{ $F::num($plan->duration_months) }} ماه</b><br>
            قیمت:
            <b>
              @if($free)
                رایگان
              @else
                {{ $F::money($plan->price) }}
              @endif
            </b>
          </p>
          <form method="POST" action="{{ route('subscription.subscribe', $plan->id) }}"
            data-confirm="{{ $free ? 'پلن رایگان فعال شود؟' : 'برای پرداخت به درگاه زرین‌پال هدایت می‌شوید. ادامه می‌دهید؟' }}"
            data-confirm-title="{{ $free ? 'فعال‌سازی اشتراک' : 'پرداخت اشتراک' }}">
            @csrf
            <button class="btn {{ $free ? '' : 'sec' }}" type="submit" style="width:100%;justify-content:center">
              @if($free)
                {{ $subscription ? 'تعویض به پلن رایگان' : 'فعال‌سازی رایگان' }}
              @else
                {{ $subscription ? 'پرداخت و تمدید / تعویض پلن' : 'پرداخت و خرید اشتراک' }}
              @endif
            </button>
          </form>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
