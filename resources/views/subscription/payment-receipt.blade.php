@php $F = \App\Support\Fa::class; @endphp
@extends('layouts.app')
@section('title', 'فاکتور پرداخت')
@section('content')
<link rel="stylesheet" href="{{ asset('css/billing.css') }}">
@php
    $plan = $payment->subscription?->plan;
    $sub = $payment->subscription;
    $gw = data_get($payment->raw_response, 'gateway_name', $payment->gateway === 'bitpay' ? 'بیت‌پی' : ($payment->gateway === 'zarinpal' ? 'زرین‌پال' : $payment->gateway));
@endphp
<div class="invoice-page"><article class="invoice">
<header class="invoice-top">
    <div><div class="invoice-brand"><span><img src="{{ asset('images/sabkhoneh-mark.png') }}" alt="" width="48" height="48"></span>صابخونه</div><div class="invoice-sub">مدیریت ساختمان، با آرامش بیشتر</div></div>
    <div class="invoice-meta"><h2>فاکتور و رسید پرداخت</h2><div class="invoice-sub">رسید صادرشده پس از تأیید درگاه</div><span class="invoice-no" dir="ltr">{{ $invoiceNumber }}</span></div>
</header>
<div class="invoice-body">
<div class="invoice-row"><section class="invoice-section"><h3>اطلاعات پرداخت‌کننده</h3><div class="invoice-grid"><div class="invoice-item"><span>نام و نام خانوادگی / نام شرکت</span><strong>{{ $payment->user?->display_name ?: 'کاربر صابخونه' }}</strong></div><div class="invoice-item"><span>شناسه کاربر</span><strong dir="ltr">#{{ $payment->user_id }}</strong></div></div></section><div class="invoice-stamp" aria-label="پرداخت موفق"><span><b>✓</b>پرداخت موفق</span></div></div>
<section class="invoice-section"><h3>شرح خرید</h3>
<table class="invoice-table"><thead><tr><th>شرح</th><th>ظرفیت</th><th>مدت</th><th>مبلغ</th></tr></thead><tbody><tr><td>{{ $plan?->name ? 'اشتراک '.$plan->name : 'اشتراک صابخونه' }}</td><td>{{ $plan?->units_limit ? $F::num($plan->units_limit).' واحد' : ($sub?->units_limit ? $F::num($sub->units_limit).' واحد' : '—') }}</td><td>{{ $plan?->duration_months ? $F::num($plan->duration_months).' ماه' : '—' }}</td><td>{{ $F::money($payment->amount) }}</td></tr></tbody></table></section>
<section class="invoice-section"><h3>جزئیات پرداخت</h3><div class="invoice-grid three">
<div class="invoice-item"><span>درگاه پرداخت</span><strong>{{ $gw }}</strong></div>
<div class="invoice-item"><span>شماره پیگیری</span><strong dir="ltr">{{ $payment->ref_id ?: '—' }}</strong></div>
<div class="invoice-item"><span>شناسه پرداخت</span><strong dir="ltr">#{{ $payment->id }}</strong></div>
<div class="invoice-item"><span>تاریخ پرداخت</span><strong>{{ $payment->paid_at ? $F::date($payment->paid_at, 'Y/m/d H:i') : '—' }}</strong></div>
@if($sub?->starts_at && $sub?->expires_at)<div class="invoice-item"><span>دوره اشتراک</span><strong>{{ $F::date($sub->starts_at, 'Y/m/d') }} تا {{ $F::date($sub->expires_at, 'Y/m/d') }}</strong></div>@endif
<div class="invoice-item"><span>وضعیت</span><strong>تأییدشده توسط درگاه</strong></div>
</div></section>
<div class="invoice-total"><span>مبلغ پرداخت‌شده</span><strong>{{ $F::money($payment->amount) }}</strong></div>
<p class="invoice-thanks">از اعتماد شما سپاسگزاریم. این فاکتور پس از تأیید پرداخت توسط درگاه صادر شده است.</p>
<div class="invoice-actions"><button type="button" onclick="window.print()">چاپ فاکتور / ذخیره PDF</button><a class="secondary" href="{{ route('subscription.show') }}">بازگشت به اشتراک‌ها</a></div>
</div></article></div>
@endsection
