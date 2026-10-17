@extends('admin.layout')
@section('title', 'داشبورد مدیریت')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $adm = auth('admin')->user();
  $menu = \App\Support\AdminMenu::for($adm);
  $pct = fn ($part, $whole) => $whole > 0 ? min(100, round($part / $whole * 100)) : 0;
  $kpis = [
    ['کاربران سامانه', $usersCount, $F::num($newUsers).' ثبت‌نام در ۳۰ روز اخیر', 'users', '#3b82c4', $pct($newUsers, $usersCount)],
    ['ساختمان‌ها', $buildingsCount, $F::num($newBuildings).' ساختمان جدید در ۳۰ روز اخیر', 'bld', '#2a9b8f', $pct($newBuildings, $buildingsCount)],
    ['اشتراک فعال', $activeSubs, $expiringSoon ? $F::num($expiringSoon).' اشتراک تا ۷ روز دیگر منقضی می‌شود' : 'اشتراکی در آستانه‌ی انقضا نیست', 'crown', '#e8a33d', $pct($activeSubs - $expiringSoon, $activeSubs)],
    ['پلن‌های فعال', $activePlans, 'از '.$F::num($plansCount).' پلن تعریف‌شده', 'coin', '#6b5bd2', $pct($activePlans, $plansCount)],
  ];
  $quick = collect($menu)->flatMap(fn ($g) => $g['items'])->filter(fn ($i) => $i['desc'] && $i['route'] !== 'admin.account');
@endphp

@if($expiringSoon)
  <div class="warnbox">{{ $F::num($expiringSoon) }} اشتراک فعال تا ۷ روز آینده به پایان می‌رسد.</div>
@endif

<div class="kpis">
  @foreach($kpis as [$label, $value, $sub, $icon, $color, $p])
    <div class="card kpi">
      <div class="h"><span class="chip" style="background:{{ $color }}"><svg class="i"><use href="#{{ $icon }}"/></svg></span>{{ $label }}</div>
      <div><div class="v">{{ $F::num($value) }}</div><div class="bar"><i style="width:{{ max($p, 2) }}%"></i></div><div class="sub">{{ $sub }}</div></div>
    </div>
  @endforeach
</div>

@if($quick->isNotEmpty())
<div>
  <div class="hd"><h2 style="font-size:15px;font-weight:700">دسترسی سریع</h2></div>
  <div class="qa">
    @foreach($quick as $item)
      <a class="card" href="{{ $item['url'] }}">
        <div class="ic"><svg class="i"><use href="#{{ $item['icon'] }}"/></svg></div>
        <div><b>{{ $item['label'] }}</b><span>{{ $item['desc'] }}</span></div>
      </a>
    @endforeach
  </div>
</div>
@endif

<div class="card sec-card">
  <div class="t">
    <div class="ic" style="background:{{ $adm->two_factor_enabled ? '#2a9b8f' : '#e8a33d' }}"><svg class="i"><use href="#shield"/></svg></div>
    <div>
      <b>امنیت حساب شما</b>
      <small>{{ $adm->two_factor_enabled ? 'تأیید دومرحله‌ای فعال است.' : 'تأیید دومرحله‌ای فعال نیست؛ برای امنیت بیشتر پنل مدیریت آن را فعال کنید.' }}</small>
    </div>
  </div>
  <a class="btn {{ $adm->two_factor_enabled ? 'sec' : '' }}" href="{{ route('admin.account') }}">{{ $adm->two_factor_enabled ? 'مدیریت حساب' : 'فعال‌سازی ۲FA' }}</a>
</div>
@endsection
