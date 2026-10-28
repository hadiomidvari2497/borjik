@php $F = \App\Support\Fa::class; @endphp
@extends('layouts.app')
@section('title', 'خرید اشتراک')
@section('content')
<link rel="stylesheet" href="{{ asset('css/billing.css') }}">
@php
    $expires = $subscription?->expires_at;
    $starts = $subscription?->starts_at;
    $daysLeft = $expires instanceof \Carbon\CarbonInterface ? max(0, (int) now()->diffInDays($expires, false)) : null;
    $spanDays = ($expires instanceof \Carbon\CarbonInterface && $starts instanceof \Carbon\CarbonInterface) ? max(1, (int) $starts->diffInDays($expires)) : null;
    $usedPct = $spanDays && $daysLeft !== null ? min(100, max(0, (int) round(100 - ($daysLeft / $spanDays) * 100))) : 0;
@endphp
<div class="bl">
@if(session('error'))<div class="bl-note" role="alert">{{ session('error') }}</div>@endif
@if(session('status'))<div class="bl-note" role="status">{{ session('status') }}</div>@endif

<section class="bl-intro">
    <h2>{{ $subscription ? 'تمدید یا تغییر اشتراک' : 'پلن مناسب صابخونه را انتخاب کنید' }}</h2>
    <p>امکانات و مدت هر پلن را مقایسه کنید. در مرحله بعد، کد تخفیف و درگاه پرداخت را در یک صفحه ساده و امن انتخاب می‌کنید.</p>
    <ol class="bl-steps" aria-label="مراحل خرید"><li class="on"><i>۱</i>انتخاب پلن</li><li><i>۲</i>تکمیل خرید</li><li><i>۳</i>دریافت فاکتور</li></ol>
</section>

@if($subscription)
<section class="bl-active" aria-label="اشتراک فعلی">
    <div class="bl-active-ic" aria-hidden="true"><svg class="i"><use href="#shield"/></svg></div>
    <div>
        <h3>اشتراک فعلی شما فعال است <span class="bl-pill">فعال</span></h3>
        <p>@if($subscription->plan)پلن {{ $subscription->plan->name }} · @endif ظرفیت {{ $F::num($subscription->units_limit) }} واحد · اعتبار تا {{ $F::date($subscription->expires_at, 'Y/m/d') }}</p>
        @if($spanDays)<div class="bl-meter" role="img" aria-label="{{ $usedPct }} درصد از مدت اشتراک گذشته است"><b style="width:{{ $usedPct }}%"></b></div>@endif
        <a class="bl-link" href="{{ route('dashboard') }}">بازگشت به داشبورد ←</a>
    </div>
    @if($daysLeft !== null)<div class="bl-days"><b>{{ $F::num($daysLeft) }}</b><span>روز تا پایان اعتبار</span></div>@endif
</section>
@endif

@if($plans->isEmpty())
<div class="bl-note">در حال حاضر پلنی برای خرید وجود ندارد. لطفاً بعداً دوباره مراجعه کنید.</div>
@else
<div class="bl-plans">
@foreach($plans as $plan)
@php
    $eff = (int) $plan->effectivePrice();
    $hasDiscount = $plan->discountIsActive() && (int) $plan->price > $eff;
    $savePct = $hasDiscount ? (int) round((1 - $eff / max(1, (int) $plan->price)) * 100) : 0;
    $perMonth = ($eff > 0 && (int) $plan->duration_months > 1) ? intdiv($eff, (int) $plan->duration_months) : null;
    $isCurrent = $subscription && (int) $subscription->plan_id === (int) $plan->id;
@endphp
<article class="bl-plan {{ $hasDiscount ? 'hot' : '' }} {{ $isCurrent ? 'cur' : '' }}">
    @if($isCurrent)<span class="bl-ribbon mint">پلن فعلی شما</span>@elseif($hasDiscount)<span class="bl-ribbon">تخفیف ویژه{{ $savePct > 0 ? ' · '.$F::num($savePct).'٪' : '' }}</span>@elseif($eff <= 0)<span class="bl-ribbon mint">رایگان</span>@endif
    <div class="bl-plan-ic" aria-hidden="true"><svg class="i"><use href="#bld"/></svg></div>
    <h3>{{ $plan->name }}</h3>
    <div class="bl-sub">مدیریت ساختمان، ساکنان و امور مالی در یک فضای یکپارچه</div>
    <div class="bl-price">@if($eff <= 0)رایگان @else{{ $F::num($eff) }} <small>تومان</small>@endif</div>
    <div class="bl-was">@if($hasDiscount)<del>{{ $F::money($plan->price) }}</del> · قیمت با تخفیف پلن @elseif($perMonth)ماهانه حدود {{ $F::money($perMonth) }} @endif</div>
    <ul class="bl-feat"><li>ظرفیت {{ $F::num($plan->units_limit) }} واحد</li><li>{{ $F::num($plan->duration_months) }} ماه اعتبار</li><li>مدیریت ساکنان، شارژ و درخواست‌ها</li></ul>
    <a class="bl-btn" href="{{ route('subscription.show', ['checkout' => $plan->id]) }}">{{ $isCurrent ? 'تمدید این پلن' : 'انتخاب پلن و ادامه' }} <span aria-hidden="true">←</span></a>
</article>
@endforeach
</div>
@endif

@if($recentPayments->isNotEmpty())
<section class="bl-hist"><h3>سوابق پرداخت و فاکتورها</h3>
@foreach($recentPayments as $p)
<div class="bl-row">
    <div class="bl-row-ic" aria-hidden="true"><svg class="i"><use href="#doc"/></svg></div>
    <div><b>پرداخت #{{ $F::num($p->id) }}</b><small>{{ $p->subscription?->plan?->name ?? 'اشتراک' }} · {{ $p->paid_at ? $F::date($p->paid_at, 'Y/m/d H:i') : '—' }}</small></div>
    <div class="bl-amt">{{ $F::money($p->amount) }}</div>
    <div>@if($p->status === 'paid')<a class="bl-mini" href="{{ route('subscription.payment.receipt', ['payment'=>$p->id]) }}">مشاهده فاکتور</a>@elseif($p->status === 'pending')<span class="bl-pill warn">در انتظار پرداخت</span>@else<span class="bl-pill bad">ناموفق</span>@endif</div>
</div>
@endforeach
</section>
@endif

<div class="bl-trust">
    <div><svg class="i" aria-hidden="true"><use href="#shield"/></svg><span><b>پرداخت امن</b>فعال‌سازی اشتراک پولی پس از تأیید درگاه</span></div>
    <div><svg class="i" aria-hidden="true"><use href="#coin"/></svg><span><b>قیمت شفاف</b>مبلغ نهایی پیش از انتقال به درگاه نمایش داده می‌شود</span></div>
    <div><svg class="i" aria-hidden="true"><use href="#doc"/></svg><span><b>فاکتور قابل چاپ</b>دریافت رسید پس از پرداخت موفق</span></div>
</div>
</div>
<script>(function(){if(!matchMedia('(hover:hover) and (pointer:fine)').matches)return;document.addEventListener('mousemove',function(e){var c=e.target.closest&&e.target.closest('.bl-plan');if(!c)return;var r=c.getBoundingClientRect();c.style.setProperty('--mx',(e.clientX-r.left)+'px');c.style.setProperty('--my',(e.clientY-r.top)+'px')},{passive:true})})();</script>
@endsection
