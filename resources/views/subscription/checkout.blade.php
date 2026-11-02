@php $F = \App\Support\Fa::class; @endphp
@extends('layouts.app')
@section('title', 'تکمیل خرید اشتراک')
@section('content')
<link rel="stylesheet" href="{{ asset('css/billing.css') }}">
@php
    $eff = (int) $plan->effectivePrice();
    $hasDiscount = $plan->discountIsActive() && (int) $plan->price > $eff;
    $savedAmount = $hasDiscount ? (int) $plan->price - $eff : 0;
@endphp
<div class="bl bl-co">
<section class="bl-intro">
    <h2>تکمیل خرید اشتراک</h2>
    <p>اشتراک ساختمان «{{ $building->name }}» را بررسی کنید، در صورت داشتن کد تخفیف آن را وارد کنید و درگاه پرداخت را انتخاب کنید.</p>
    <ol class="bl-steps" aria-label="مراحل خرید"><li class="done"><i>✓</i>انتخاب پلن</li><li class="on"><i>۲</i>تکمیل خرید</li><li><i>۳</i>دریافت فاکتور</li></ol>
</section>
@if(session('error'))<div class="bl-note" role="alert">{{ session('error') }}</div>@endif
@if(!$gatewayReady && $eff > 0)<div class="bl-note">در حال حاضر درگاه پرداخت فعالی وجود ندارد. لطفاً بعداً مراجعه کنید.</div>@endif
<form method="POST" action="{{ route('subscription.subscribe',['id'=>$plan->id]) }}">
@csrf
<div class="bl-grid"><div>
<section class="bl-card"><h3><i>۱</i>اشتراک انتخاب‌شده</h3>
    <div class="bl-chosen"><small class="tag">پلن انتخاب‌شده</small><strong>{{ $plan->name }}</strong>
        <div class="meta"><span>{{ $F::num($plan->units_limit) }} واحد</span><span>{{ $F::num($plan->duration_months) }} ماه اعتبار</span></div>
        <div class="px">@if($eff <= 0)رایگان @else{{ $F::num($eff) }} <small>تومان</small>@endif</div>
        @if($hasDiscount)<div class="was"><del>{{ $F::money($plan->price) }}</del> · قیمت با تخفیف پلن</div>@endif
    </div>
</section>
<section class="bl-card"><h3><i>۲</i>کد تخفیف <em>(اختیاری)</em></h3>
    <div class="bl-field"><input id="coupon-code" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="کد تخفیف را وارد کنید" autocomplete="off" aria-label="کد تخفیف"><button id="apply-coupon" type="button">بررسی و اعمال</button></div>
    <div id="coupon-hint" class="coupon-hint bl-hint" aria-live="polite">کد را وارد کنید و روی «بررسی و اعمال» بزنید.</div>
    @error('coupon_code')<div class="bl-err">{{ $message }}</div>@enderror
</section>
<section class="bl-card"><h3><i>۳</i>انتخاب درگاه پرداخت</h3>
    @if($paymentGateways)<div class="bl-gws">@foreach($paymentGateways as $id=>$gateway)@php $gwName = $gateway['provider_name'] ?? ($gateway['driver']==='bitpay'?'بیت‌پی':'زرین‌پال'); $gwLogo = $gateway['logo_path'] ?? null; @endphp<label class="bl-gw"><input type="radio" name="gateway_id" value="{{ $id }}" @checked(old('gateway_id',count($paymentGateways)===1?(string)$id:'')===(string)$id) required><span class="bl-gw-ic" aria-hidden="true">@if($gwLogo)<img src="{{ asset('storage/'.ltrim($gwLogo, '/')) }}" alt="" loading="lazy">@else{{ \Illuminate\Support\Str::substr($gwName, 0, 1) }}@endif</span><span><b>{{ $gwName }}</b><small>پرداخت آنلاین امن و هدایت به درگاه بانکی</small></span><span class="bl-gw-tick" aria-hidden="true">✓</span></label>@endforeach</div>
    @else<div class="bl-hint">درگاه فعالی برای پرداخت آنلاین تنظیم نشده است.</div>@endif
</section>
</div>
<aside class="bl-card bl-sum"><h3>خلاصه سفارش</h3>
    <div class="bl-sr"><span>{{ $hasDiscount ? 'قیمت پلن' : 'مبلغ اشتراک' }}</span><strong>{{ $F::money($hasDiscount ? $plan->price : $eff) }}</strong></div>
    @if($hasDiscount)<div class="bl-sr save"><span>تخفیف پلن</span><strong>−{{ $F::money($savedAmount) }}</strong></div>@endif
    <div class="bl-sr" id="coupon-discount-row" hidden><span>تخفیف کد</span><strong id="coupon-discount-value">−۰ تومان</strong></div>
    <div class="bl-sr total"><span>مبلغ قابل پرداخت</span><strong id="payable-amount" data-base-amount="{{ $eff }}">{{ $F::money($eff) }}</strong></div>
    <button class="bl-btn" style="width:100%;margin-top:14px" type="submit" @disabled(!$gatewayReady && $eff>0)>ادامه و پرداخت امن <span aria-hidden="true">←</span></button>
    <a class="bl-back" href="{{ route('subscription.show') }}">بازگشت به انتخاب پلن</a>
    <div class="bl-safe"><svg class="i" aria-hidden="true"><use href="#lock"/></svg><span>اطلاعات پرداخت در سمت سرور اعتبارسنجی می‌شود و اشتراک پولی فقط پس از تأیید درگاه فعال خواهد شد.</span></div>
</aside></div>
</form></div>
<script>
(() => {
    const input = document.getElementById('coupon-code');
    const button = document.getElementById('apply-coupon');
    const hint = document.getElementById('coupon-hint');
    const discountRow = document.getElementById('coupon-discount-row');
    const discountValue = document.getElementById('coupon-discount-value');
    const payable = document.getElementById('payable-amount');
    if (!input || !button || !hint || !payable) return;

    const baseAmount = Number(payable.dataset.baseAmount || 0);
    const formatMoney = value => new Intl.NumberFormat('fa-IR').format(Math.max(0, Number(value) || 0)) + ' تومان';
    let appliedCode = '';

    const resetPreview = () => {
        appliedCode = '';
        discountRow.hidden = true;
        payable.textContent = formatMoney(baseAmount);
    };

    const applyCoupon = async () => {
        const code = input.value.trim();
        if (!code) {
            resetPreview();
            hint.textContent = 'کد تخفیف را وارد کنید.';
            hint.classList.remove('is-success');
            return;
        }

        button.disabled = true;
        button.textContent = 'در حال بررسی…';
        hint.textContent = 'در حال اعتبارسنجی کد تخفیف…';
        hint.classList.remove('is-success');

        try {
            const response = await fetch(@json(route('subscription.coupon.preview', ['id' => $plan->id])), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({ coupon_code: code }),
            });
            const data = await response.json();
            if (!response.ok || !data.valid) {
                resetPreview();
                hint.textContent = data.message || 'اعمال کد تخفیف ممکن نشد.';
                return;
            }

            appliedCode = data.coupon_code;
            discountRow.hidden = false;
            discountValue.textContent = '−' + formatMoney(data.discount_amount);
            payable.textContent = formatMoney(data.payable_amount);
            hint.textContent = data.message + ' میزان تخفیف: ' + formatMoney(data.discount_amount);
            hint.classList.add('is-success');
        } catch (error) {
            resetPreview();
            hint.textContent = 'ارتباط با سرور برقرار نشد. دوباره تلاش کنید.';
        } finally {
            button.disabled = false;
            button.textContent = 'بررسی و اعمال';
        }
    };

    button.addEventListener('click', applyCoupon);
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyCoupon();
        }
    });
    input.addEventListener('input', () => {
        if (appliedCode && input.value.trim().toUpperCase() !== appliedCode) {
            resetPreview();
            hint.textContent = 'کد تغییر کرده؛ برای محاسبه مبلغ جدید دوباره بررسی کنید.';
            hint.classList.remove('is-success');
        }
    });
})();
</script>
@endsection
