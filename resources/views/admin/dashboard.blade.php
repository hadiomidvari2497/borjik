@extends('admin.layout')
@section('title', 'داشبورد مدیریت')
@section('content')
@php
  $F = \\App\\Support\\Fa::class;
  $adm = auth('admin')->user();
  $menu = \\App\\Support\\AdminMenu::for($adm);
  $pct = fn ($part, $whole) => $whole > 0 ? min(100, round($part / $whole * 100)) : 0;
  $kpis = [
    ['کاربران سامانه', $usersCount, $F::num($newUsers).' ثبت‌نام در ۳۰ روز اخیر', 'users', '#3b82c4', $pct($newUsers, $usersCount)],
    ['ساختمان‌ها', $buildingsCount, $F::num($newBuildings).' ساختمان جدید در ۳۰ روز اخیر', 'bld', '#2a9b8f', $pct($newBuildings, $buildingsCount)],
    ['اشتراک فعال', $activeSubs, $expiringSoon ? $F::num($expiringSoon).' اشتراک تا ۷ روز دیگر منقضی می‌شود' : 'اشتراکی در آستانه‌ی انقضا نیست', 'crown', '#e8a33d', $pct($activeSubs - $expiringSoon, $activeSubs)],
    ['پلن‌های فعال', $activePlans, 'از '.$F::num($plansCount).' پلن تعریف‌شده', 'coin', '#6b5bd2', $pct($activePlans, $plansCount)],
  ];
  $quick = collect($menu)->flatMap(fn ($g) => $g['items'])->filter(fn ($i) => !empty($i['desc']) && $i['route'] !== 'admin.account');
  $alerts = collect([
    ['title' => 'اشتراک‌های منقضی یا غیرفعال', 'count' => $expiredSubscriptions, 'detail' => 'نیازمند بررسی وضعیت تمدید', 'icon' => 'crown', 'url' => $adm->hasPermission('subscriptions.manage') ? route('admin.subscriptions.index') : null],
    ['title' => 'ساختمان‌های بدون اشتراک معتبر', 'count' => $buildingsWithoutValidSubscription, 'detail' => 'بدون اشتراک یا دارای اشتراک نامعتبر', 'icon' => 'building-2', 'url' => $adm->hasPermission('buildings.view') ? route('admin.buildings.index') : null],
    ['title' => 'ارسال‌های ناموفق پیامک در ۲۴ ساعت', 'count' => $failedSmsLast24Hours, 'detail' => 'بررسی علت خطا در آرشیو پیامک', 'icon' => 'inbox', 'url' => $adm->hasPermission('sms.logs') ? route('admin.sms.logs.index', ['status' => 'failed']) : null],
    ['title' => 'اشتراک‌های فعال در آستانه انقضا', 'count' => $expiringSoon, 'detail' => 'انقضا در ۷ روز آینده', 'icon' => 'clock', 'url' => $adm->hasPermission('subscriptions.manage') ? route('admin.subscriptions.index') : null],
  ])->filter(fn ($item) => $item['count'] > 0)->values();
@endphp

@if($alerts->isNotEmpty())
  <section class="card" style="padding:18px;margin-bottom:18px">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;flex-wrap:wrap">
      <div>
        <h2 style="font-size:16px;font-weight:800;margin:0 0 4px">نیازمند رسیدگی</h2>
        <div style="font-size:12px;color:var(--muted,#64748b)">هشدارها بر اساس اطلاعات فعلی سامانه محاسبه شده‌اند.</div>
      </div>
      <span style="font-size:12px;padding:5px 10px;border-radius:999px;background:#fff3dc;color:#925b08">{{ $F::num($alerts->count()) }} مورد</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,250px),1fr));gap:10px">
      @foreach($alerts as $alert)
        <div style="display:flex;align-items:center;gap:12px;padding:13px;border:1px solid var(--border,#e2e8f0);border-radius:12px;min-width:0">
          <span style="width:38px;height:38px;flex:0 0 38px;border-radius:10px;background:#fff3dc;color:#9a6700;display:flex;align-items:center;justify-content:center;font-weight:800">
            <svg class="i"><use href="#{{ $alert['icon'] }}"/></svg>
          </span>
          <span style="flex:1;min-width:0">
            <b style="display:block;font-size:13px;line-height:1.8">{{ $alert['title'] }}</b>
            <small style="display:block;color:var(--muted,#64748b);font-size:11px;line-height:1.8">{{ $alert['detail'] }}</small>
            @if($alert['url'])
              <a href="{{ $alert['url'] }}" style="display:inline-block;margin-top:4px;font-size:11px;text-decoration:none">بررسی مورد <span aria-hidden="true">←</span></a>
            @endif
          </span>
          <strong style="font-size:20px;color:#9a6700">{{ $F::num($alert['count']) }}</strong>
        </div>
      @endforeach
    </div>
  </section>
@else
  <section class="card" style="display:flex;align-items:center;gap:12px;padding:16px;margin-bottom:18px">
    <span style="width:38px;height:38px;border-radius:10px;background:#e8f7ef;color:#18794e;display:flex;align-items:center;justify-content:center"><svg class="i"><use href="#shield-check"/></svg></span>
    <div><b style="display:block;font-size:14px">مورد فوری برای رسیدگی پیدا نشد</b><small style="color:var(--muted,#64748b)">در حال حاضر هشدار فعالی در شاخص‌های بررسی‌شده وجود ندارد.</small></div>
  </section>
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
