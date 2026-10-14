@extends('layouts.app')
@section('title', 'خانه‌ی من')
@section('content')
<div class="kpis" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
  <div class="card kpi"><div class="h"><span class="chip" style="background:{{ $debt ? '#e0453a' : '#2a9b8f' }}"><svg class="i"><use href="#coin"/></svg></span>مانده‌ی بدهی</div>
    <div><div class="v">{{ \App\Support\Fa::num($debt) }}</div><div class="sub">{{ $debt ? 'تومان — '.\App\Support\Fa::num($overdueCount).' صورت‌حساب معوق' : 'تسویه‌شده' }}</div></div></div>
  <div class="card kpi"><div class="h"><span class="chip" style="background:#3b82c4"><svg class="i"><use href="#doc"/></svg></span>صورت‌حساب‌های پرداخت‌نشده</div>
    <div><div class="v">{{ \App\Support\Fa::num($unpaid->count()) }}</div></div></div>
  <div class="card kpi"><div class="h"><span class="chip" style="background:#e8a33d"><svg class="i"><use href="#inbox"/></svg></span>درخواست‌های باز من</div>
    <div><div class="v">{{ \App\Support\Fa::num($openRequests) }}</div></div></div>
</div>

<div class="r2">
  <div class="card">
    <div class="hd"><h2 style="margin:0">صورت‌حساب‌های پرداخت‌نشده</h2></div>
    @forelse($unpaid as $inv)
      <div class="kv"><div><b>{{ ['charge' => 'شارژ', 'rent' => 'اجاره', 'penalty' => 'جریمه'][$inv->type->value] }}</b><br><span>سررسید {{ \App\Support\Fa::date($inv->due_date) }}</span></div>
        <div style="text-align:end"><b>{{ \App\Support\Fa::money($inv->amount - $inv->paid_amount) }}</b>@if($inv->due_date->isPast())<br><span class="pill u">معوق</span>@endif</div></div>
    @empty
      <div class="empty">صورت‌حساب پرداخت‌نشده‌ای ندارید.</div>
    @endforelse
  </div>
  <div class="card lst">
    <h2>واحد من</h2>
    @forelse($units as $unit)
      <div class="kv"><span>بلوک {{ \App\Support\Fa::digits($unit->block->number) }} · واحد {{ \App\Support\Fa::digits($unit->number) }}</span><b>{{ \App\Support\Fa::num($unit->area_m2) }} متر</b></div>
    @empty
      <div class="empty">واحدی برای شما ثبت نشده است.</div>
    @endforelse
    <h2 style="margin-top:16px">رویدادهای نزدیک</h2>
    @forelse($events as $e)
      <div class="evt"><div class="d">{{ \App\Support\Fa::digits(\Morilog\Jalali\Jalalian::fromCarbon($e->starts_at)->format('j')) }}<small>{{ \App\Support\Fa::MONTHS[\Morilog\Jalali\Jalalian::fromCarbon($e->starts_at)->getMonth() - 1] }}</small></div>
        <div><b>{{ $e->title }}</b><span>{{ \App\Support\Fa::digits($e->starts_at->format('H:i')) }}</span></div></div>
    @empty
      <div class="empty">رویدادی در پیش نیست.</div>
    @endforelse
  </div>
</div>
@endsection
