@php
  $F = \App\Support\Fa::class;
  $relationLabels = ['owner' => 'مالک', 'tenant' => 'مستأجر', 'resident' => 'ساکن'];
  $unitStatus = [
    'sold' => ['تملک‌شده', 'mui-status-ok'],
    'rented' => ['دارای مستأجر', 'mui-status-info'],
    'vacant' => ['خالی', 'mui-status-neutral'],
    'repair' => ['در حال تعمیر', 'mui-status-warn'],
  ];
  $invoiceStatus = [
    'unpaid' => ['پرداخت‌نشده', 'mui-bill-open'],
    'partial' => ['پرداخت جزئی', 'mui-bill-open'],
    'paid' => ['تسویه‌شده', 'mui-bill-paid'],
    'void' => ['باطل‌شده', 'mui-bill-void'],
  ];
  $invoiceType = ['charge' => 'شارژ ساختمان', 'rent' => 'اجاره', 'penalty' => 'جریمه'];
@endphp
@extends('layouts.app')
@section('title', 'واحد من')
@section('content')
<style>
.mui{--mui-ink:#172d35;--mui-muted:#71818a;--mui-line:#e6ecec;--mui-card:#fff;color:var(--mui-ink);font-family:inherit}
.mui *{box-sizing:border-box}
.mui a{text-decoration:none}
.mui-hero{position:relative;isolation:isolate;overflow:hidden;border-radius:24px;padding:clamp(22px,4vw,38px);color:#fff;background:linear-gradient(120deg,#102f38 0%,#155c5b 58%,#278b79 100%);box-shadow:0 18px 44px #174b4a20}
.mui-hero:before,.mui-hero:after{content:"";position:absolute;z-index:-1;border:1px solid #ffffff20;border-radius:50%;pointer-events:none}
.mui-hero:before{width:340px;height:340px;left:-80px;top:-190px;box-shadow:0 0 0 35px #ffffff08,0 0 0 70px #ffffff06}
.mui-hero:after{width:220px;height:220px;right:22%;bottom:-190px;box-shadow:0 0 0 26px #ffffff08,0 0 0 52px #ffffff06}
.mui-hero-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px;align-items:center}
.mui-eyebrow{display:flex;align-items:center;gap:8px;color:#c5e8df;font-size:12px;font-weight:700;letter-spacing:.02em}
.mui-live-dot{width:7px;height:7px;border-radius:50%;background:#83e5b9;box-shadow:0 0 0 4px #83e5b925}
.mui-hero h2{margin:14px 0 8px;color:#fff;font-size:clamp(24px,3vw,36px);line-height:1.45;font-weight:850}
.mui-hero p{margin:0;color:#d4e8e5;font-size:13px;line-height:2;max-width:580px}
.mui-hero-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:23px}
.mui-action{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #ffffff35;border-radius:12px;padding:11px 15px;color:#fff;font-size:12px;font-weight:800;transition:transform .2s,background .2s}
.mui-action:hover{transform:translateY(-2px);background:#ffffff18;color:#fff}
.mui-action.primary{background:#e2f7eb;color:#164d43;border-color:#e2f7eb}
.mui-action.primary:hover{background:#fff;color:#164d43}
.mui-hero-mark{width:122px;height:122px;display:grid;place-items:center;border-radius:30px;background:linear-gradient(145deg,#ffffff22,#ffffff08);border:1px solid #ffffff28;transform:rotate(-4deg);box-shadow:inset 0 1px #ffffff30}
.mui-hero-mark svg{width:64px;height:64px;stroke:#e3f7ee;stroke-width:1.25;fill:none;stroke-linecap:round;stroke-linejoin:round}
.mui-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin:18px 0 24px}
.mui-stat{display:flex;align-items:center;gap:13px;min-width:0;background:var(--mui-card);border:1px solid var(--mui-line);border-radius:17px;padding:17px;box-shadow:0 4px 16px #172d3505}
.mui-stat-icon{width:43px;height:43px;flex:0 0 43px;display:grid;place-items:center;border-radius:14px;background:#eaf6f1;color:#287b68}
.mui-stat-icon svg{width:21px;height:21px;stroke:currentColor;stroke-width:1.8;fill:none;stroke-linecap:round;stroke-linejoin:round}
.mui-stat:nth-child(2) .mui-stat-icon{background:#fff3df;color:#a56a18}.mui-stat:nth-child(3) .mui-stat-icon{background:#edf1ff;color:#596bb8}.mui-stat:nth-child(4) .mui-stat-icon{background:#fff0ee;color:#bd6658}
.mui-stat-label{color:var(--mui-muted);font-size:11px;font-weight:650;white-space:nowrap}
.mui-stat-value{margin-top:5px;font-size:clamp(15px,1.7vw,22px);font-weight:850;letter-spacing:-.04em;line-height:1.4}
.mui-stat-value small{font-size:10px;font-weight:700;letter-spacing:0;color:var(--mui-muted)}
.mui-section-head{display:flex;justify-content:space-between;align-items:end;gap:12px;margin:28px 2px 14px}
.mui-section-head h3{margin:0;font-size:18px;font-weight:850;color:var(--mui-ink)}
.mui-section-head p{margin:5px 0 0;color:var(--mui-muted);font-size:12px;line-height:1.8}
.mui-section-kicker{font-size:10px;font-weight:800;color:#287b68;background:#e9f6f0;border-radius:999px;padding:6px 10px;white-space:nowrap}
.mui-unit-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.mui-unit{min-width:0;background:var(--mui-card);border:1px solid var(--mui-line);border-radius:20px;overflow:hidden;box-shadow:0 6px 22px #172d3505;transition:box-shadow .2s,transform .2s}
.mui-unit:hover{box-shadow:0 15px 32px #172d3510;transform:translateY(-2px)}
.mui-unit-top{padding:19px 20px 17px;background:linear-gradient(135deg,#f8fbfa,#f1f8f5);border-bottom:1px solid var(--mui-line)}
.mui-unit-toprow{display:flex;justify-content:space-between;align-items:start;gap:12px}
.mui-unit-icon{width:48px;height:48px;border-radius:15px;background:#dcefe7;display:grid;place-items:center;color:#206b5c;flex:0 0 48px}
.mui-unit-icon svg{width:27px;height:27px;stroke:currentColor;stroke-width:1.5;fill:none;stroke-linecap:round;stroke-linejoin:round}
.mui-unit-title{display:flex;gap:12px;align-items:center;min-width:0}
.mui-unit-title h4{font-size:17px;margin:0;font-weight:850;line-height:1.6;overflow-wrap:anywhere}
.mui-unit-title p{font-size:11px;color:var(--mui-muted);margin:2px 0 0}
.mui-pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:6px 9px;font-size:10px;font-weight:800;white-space:nowrap}
.mui-status-ok{background:#e6f7ed;color:#23764c}.mui-status-info{background:#e9f2ff;color:#315e9a}.mui-status-neutral{background:#edf0f1;color:#5c6b70}.mui-status-warn{background:#fff0d9;color:#9b651a}
.mui-unit-body{padding:18px 20px}
.mui-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}
.mui-fact{display:flex;flex-direction:column;gap:5px;min-width:0}
.mui-fact-label{font-size:10px;color:var(--mui-muted);font-weight:650}
.mui-fact-value{font-size:13px;font-weight:800;overflow-wrap:anywhere}
.mui-fact-value small{font-size:10px;color:var(--mui-muted);font-weight:600}
.mui-unit-divider{height:1px;background:var(--mui-line);margin:17px 0}
.mui-subhead{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:12px;font-weight:850;margin-bottom:11px}
.mui-subhead span{color:var(--mui-muted);font-size:10px;font-weight:650}
.mui-people{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.mui-person{display:flex;align-items:center;gap:7px;padding:6px 9px 6px 11px;border-radius:11px;background:#f5f8f7;max-width:100%;font-size:10px;font-weight:750}
.mui-avatar{width:26px;height:26px;display:grid;place-items:center;border-radius:8px;background:#dcefe7;color:#206b5c;font-size:10px;font-weight:900;flex:0 0 26px}
.mui-person-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:150px}
.mui-person-role{color:var(--mui-muted);font-size:9px;font-weight:650}
.mui-amenities{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
.mui-amenity{display:flex;align-items:center;gap:9px;border:1px solid var(--mui-line);border-radius:12px;padding:10px;min-width:0}
.mui-amenity-icon{width:31px;height:31px;display:grid;place-items:center;border-radius:9px;background:#f0f4ff;color:#6572b4;flex:0 0 31px}
.mui-amenity-icon svg{width:17px;height:17px;stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round}
.mui-amenity strong{display:block;font-size:11px;overflow-wrap:anywhere}
.mui-amenity small{display:block;color:var(--mui-muted);font-size:9px;margin-top:3px}
.mui-unit-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 20px;border-top:1px solid var(--mui-line);background:#fcfdfd}
.mui-debt-label{font-size:10px;color:var(--mui-muted)}
.mui-debt-value{display:block;font-size:14px;font-weight:900;margin-top:4px}
.mui-text-link{display:inline-flex;align-items:center;gap:7px;font-size:11px;font-weight:850;color:#217564}
.mui-text-link:hover{color:#144f47}
.mui-lower{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(260px,.8fr);gap:16px;margin-top:22px}.mui-lower-single{grid-template-columns:1fr}
.mui-box{min-width:0;background:var(--mui-card);border:1px solid var(--mui-line);border-radius:20px;padding:20px}
.mui-box-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}
.mui-box-head h3{margin:0;font-size:15px;font-weight:850}
.mui-box-head a{font-size:11px;font-weight:800;color:#217564}
.mui-invoice-list{display:flex;flex-direction:column}
.mui-invoice{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:12px;padding:13px 0;border-bottom:1px solid var(--mui-line)}
.mui-invoice:last-child{border-bottom:0;padding-bottom:0}
.mui-invoice:first-child{padding-top:0}
.mui-invoice-title{font-size:12px;font-weight:850;overflow-wrap:anywhere}
.mui-invoice-meta{font-size:10px;color:var(--mui-muted);margin-top:5px;line-height:1.7}
.mui-invoice-side{text-align:left;white-space:nowrap}
.mui-invoice-amount{font-size:12px;font-weight:900}
.mui-bill-open{background:#fff1dc;color:#9b6417}.mui-bill-paid{background:#e7f7ed;color:#24764d}.mui-bill-void{background:#eff1f2;color:#6b767a}
.mui-empty-inline{padding:23px 10px;text-align:center;color:var(--mui-muted);font-size:12px;line-height:2}
.mui-contract{display:flex;align-items:flex-start;gap:11px;padding:12px 0;border-bottom:1px solid var(--mui-line)}
.mui-contract:first-of-type{padding-top:0}.mui-contract:last-child{padding-bottom:0;border-bottom:0}
.mui-contract-icon{width:38px;height:38px;display:grid;place-items:center;background:#eaf6f1;color:#287b68;border-radius:12px;flex:0 0 38px}
.mui-contract-icon svg{width:19px;height:19px;stroke:currentColor;stroke-width:1.8;fill:none;stroke-linecap:round;stroke-linejoin:round}
.mui-contract strong{font-size:11px;display:block;line-height:1.8}
.mui-contract small{font-size:10px;color:var(--mui-muted);display:block;margin-top:3px;line-height:1.8}
.mui-quicklinks{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:18px}
.mui-quicklink{display:flex;align-items:center;gap:12px;padding:15px;border:1px solid var(--mui-line);border-radius:16px;background:var(--mui-card);color:var(--mui-ink);transition:transform .2s,border-color .2s}
.mui-quicklink:hover{transform:translateY(-2px);border-color:#a5d7c5;color:var(--mui-ink)}
.mui-quicklink-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:#edf7f2;color:#287b68;flex:0 0 40px}
.mui-quicklink-icon svg{width:20px;height:20px;stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round}
.mui-quicklink strong{display:block;font-size:11px;font-weight:850}
.mui-quicklink small{display:block;color:var(--mui-muted);font-size:10px;margin-top:4px;line-height:1.6}
.mui-empty{padding:40px 24px;text-align:center;background:var(--mui-card);border:1px dashed #cbd9d5;border-radius:20px}
.mui-empty-icon{width:68px;height:68px;margin:0 auto 15px;border-radius:22px;background:#e7f5ed;color:#287b68;display:grid;place-items:center}
.mui-empty-icon svg{width:33px;height:33px;stroke:currentColor;stroke-width:1.5;fill:none;stroke-linecap:round;stroke-linejoin:round}
.mui-empty h3{margin:0 0 8px;font-size:17px;font-weight:850}
.mui-empty p{margin:0 auto 18px;max-width:440px;color:var(--mui-muted);font-size:12px;line-height:2}
.mui-muted{color:var(--mui-muted)}
@media(max-width:900px){.mui-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.mui-unit-list{grid-template-columns:1fr}.mui-lower{grid-template-columns:1fr}.mui-hero-mark{width:94px;height:94px;border-radius:24px}.mui-hero-mark svg{width:50px;height:50px}}
@media(max-width:560px){.mui-hero{border-radius:19px;padding:22px 18px}.mui-hero-grid{grid-template-columns:1fr}.mui-hero-mark{display:none}.mui-hero h2{font-size:25px}.mui-hero-actions{margin-top:18px}.mui-action{flex:1;padding:11px 9px}.mui-stats{gap:9px;margin:13px 0 20px}.mui-stat{padding:12px;gap:9px;border-radius:14px}.mui-stat-icon{width:35px;height:35px;flex-basis:35px;border-radius:11px}.mui-stat-label{font-size:10px}.mui-stat-value{font-size:16px}.mui-section-head{margin-top:23px}.mui-section-head h3{font-size:16px}.mui-unit-top,.mui-unit-body{padding:16px}.mui-unit-footer{padding:13px 16px}.mui-unit-title h4{font-size:15px}.mui-unit-title{gap:9px}.mui-unit-icon{width:42px;height:42px;flex-basis:42px}.mui-facts{gap:12px}.mui-amenities{gap:7px}.mui-amenity{padding:8px;gap:7px}.mui-quicklinks{grid-template-columns:1fr;gap:9px}.mui-quicklink{padding:12px}.mui-box{padding:16px}.mui-invoice{gap:7px}.mui-invoice-side{white-space:normal}.mui-invoice-amount{font-size:11px}}
@media(prefers-color-scheme:dark){.mui{--mui-ink:var(--text,#e8eeee);--mui-muted:var(--muted,#9eacad);--mui-line:var(--line,#2d3b3d);--mui-card:var(--card,#182426)}.mui-hero{background:linear-gradient(120deg,#102a31 0%,#124b49 58%,#1c6657 100%)}.mui-unit-top{background:linear-gradient(135deg,#1d2d2e,#1b302c)}.mui-person,.mui-unit-footer{background:#1b292a}.mui-amenity{background:#1b292a}.mui-stat-icon,.mui-quicklink-icon,.mui-contract-icon{background:#213d36}.mui-stat:nth-child(2) .mui-stat-icon{background:#3d3020}.mui-stat:nth-child(3) .mui-stat-icon{background:#282e4c}.mui-stat:nth-child(4) .mui-stat-icon{background:#432b29}.mui-empty-icon{background:#213d36}.mui-status-neutral,.mui-bill-void{background:#303a3c;color:#c1cbcd}.mui-status-ok,.mui-bill-paid{background:#1c3c2d;color:#a3e5b7}.mui-status-info{background:#23364e;color:#b6d4ff}.mui-status-warn,.mui-bill-open{background:#44351f;color:#f4cc85}}
</style>

<div class="mui">
  <section class="mui-hero">
    <div class="mui-hero-grid">
      <div>
        <div class="mui-eyebrow"><span class="mui-live-dot"></span> فضای شخصی شما در صابخونه</div>
        <h2>خانه‌ات، یک نگاه تا مدیریت. <span style="color:#a8e4c9">✦</span></h2>
        <p>اطلاعات واحد، وضعیت شارژ، اعضای ساکن و امکانات اختصاصی را یک‌جا ببین؛ شفاف، مرتب و همیشه در دسترس.</p>
        <div class="mui-hero-actions">
          <a class="mui-action primary" href="{{ route('my.invoices') }}">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg>
            صورت‌حساب‌های من
          </a>
          <a class="mui-action" href="{{ route('my.payments') }}">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M7 15h3"/></svg>
            ثبت فیش پرداخت
          </a>
        </div>
      </div>
      <div class="mui-hero-mark" aria-hidden="true">
        <svg viewBox="0 0 64 64"><path d="M8 28 32 8l24 20M15 25v29h34V25M25 54V37h14v17M23 27h4M37 27h4M23 34h4M37 34h4"/><path d="M29 17h6"/></svg>
      </div>
    </div>
  </section>

  <section class="mui-stats" aria-label="خلاصه وضعیت">
    <div class="mui-stat">
      <div class="mui-stat-icon"><svg viewBox="0 0 24 24"><path d="M3 21V7l9-4 9 4v14M7 21v-6h10v6M7 9h2m6 0h2m-10 4h2m6 0h2"/></svg></div>
      <div><div class="mui-stat-label">واحدهای من</div><div class="mui-stat-value">{{ $F::num($units->count()) }} <small>واحد</small></div></div>
    </div>
    <div class="mui-stat">
      <div class="mui-stat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M15 8.5c-.7-1-1.7-1.5-3-1.5-1.7 0-3 1-3 2.4s1.3 2.1 3 2.1 3 1 3 2.5S13.7 16.5 12 16.5c-1.3 0-2.4-.5-3-1.5M12 5v14"/></svg></div>
      <div><div class="mui-stat-label">مانده بدهی شما</div><div class="mui-stat-value">{{ $F::num($debt) }} <small>تومان</small></div></div>
    </div>
    <div class="mui-stat">
      <div class="mui-stat-icon"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="16" rx="3"/><path d="M8 3v4m8-4v4M4 10h16M8 14h3"/></svg></div>
      <div><div class="mui-stat-label">صورت‌حساب باز</div><div class="mui-stat-value">{{ $F::num($openInvoiceCount) }} <small>مورد</small></div></div>
    </div>
    <div class="mui-stat">
      <div class="mui-stat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2M8 2h8"/></svg></div>
      <div><div class="mui-stat-label">سررسید گذشته</div><div class="mui-stat-value">{{ $F::num($overdueCount) }} <small>صورت‌حساب</small></div></div>
    </div>
  </section>

  <div class="mui-section-head">
    <div><h3>فضای اختصاصی من</h3><p>جزئیات واحدها و امکاناتی که به شما تعلق دارند</p></div>
    <span class="mui-section-kicker">{{ $F::num($units->count()) }} واحد ثبت‌شده</span>
  </div>

  @if($units->isEmpty())
    <div class="mui-empty">
      <div class="mui-empty-icon"><svg viewBox="0 0 24 24"><path d="M3 10 12 3l9 7M5 9v12h14V9M9 21v-7h6v7"/></svg></div>
      <h3>هنوز واحدی به حساب شما متصل نشده</h3>
      <p>به‌محض اینکه مدیریت ساختمان، مالکیت یا سکونت شما را ثبت کند، کارت اختصاصی واحد، اطلاعات ساکنین و وضعیت مالی اینجا نمایش داده می‌شود.</p>
      @can('contract.view.own')<a class="mui-text-link" href="{{ route('my.contracts') }}">مشاهده قراردادهای من <span aria-hidden="true">←</span></a>@endcan
    </div>
  @else
    <div class="mui-unit-list">
      @foreach($units as $unit)
        @php
          $status = $unitStatus[$unit->status->value] ?? ['ثبت‌شده', 'mui-status-neutral'];
          $myInvoices = $invoices->where('unit_id', $unit->id);
          $myOpenInvoices = $myInvoices->filter(fn ($invoice) => in_array($invoice->status->value, ['unpaid', 'partial'], true));
          $unitDebt = (int) $myOpenInvoices->sum(fn ($invoice) => max(0, (int) $invoice->amount - (int) $invoice->paid_amount));
          $parties = $unit->activeParties;
          $myContracts = $contractsByUnit->get($unit->id, collect());
          $parking = $unit->parkingAssignments->first()?->parking;
          $storage = $unit->storageAssignments->first()?->storage;
          $direction = match ($unit->direction?->value) { 'north' => 'شمالی', 'south' => 'جنوبی', 'east' => 'شرقی', 'west' => 'غربی', default => 'ثبت نشده' };
        @endphp
        <article class="mui-unit">
          <div class="mui-unit-top">
            <div class="mui-unit-toprow">
              <div class="mui-unit-title">
                <div class="mui-unit-icon"><svg viewBox="0 0 32 32"><path d="M4 28V7l12-4 12 4v21M9 11h3m6 0h3M9 16h3m6 0h3M9 21h3m6 0h3M13 28v-5h6v5"/></svg></div>
                <div><h4>{{ $unit->block?->name ?? 'بلوک' }} · واحد {{ $F::digits($unit->number) }}</h4><p>{{ $currentBuilding?->name ?? 'ساختمان شما' }} @if($unit->floor !== null) · طبقه {{ $F::digits($unit->floor) }} @endif</p></div>
              </div>
              <span class="mui-pill {{ $status[1] }}">{{ $status[0] }}</span>
            </div>
          </div>
          @if($ownedUnitIds->contains($unit->id))
            <div style="padding:0 20px 14px;display:flex;justify-content:flex-start">
              <a class="mui-text-link" href="{{ route('my.units.occupancy.edit', $unit) }}">مدیریت وضعیت سکونت و سرپرست واحد ←</a>
            </div>
          @endif
          <div class="mui-unit-body">
            <div class="mui-facts">
              <div class="mui-fact"><span class="mui-fact-label">متراژ</span><span class="mui-fact-value">{{ $F::num((float) $unit->area_m2) }} <small>متر مربع</small></span></div>
              <div class="mui-fact"><span class="mui-fact-label">تعداد اتاق</span><span class="mui-fact-value">{{ $unit->bedrooms !== null ? $F::num($unit->bedrooms).' اتاق' : 'ثبت نشده' }}</span></div>
              <div class="mui-fact"><span class="mui-fact-label">جهت واحد</span><span class="mui-fact-value">{{ $direction }}</span></div>
              <div class="mui-fact"><span class="mui-fact-label">اعضای خانوار</span><span class="mui-fact-value">{{ $F::num($parties->count()) }} <small>نفر ثبت‌شده</small></span></div>
            </div>

            <div class="mui-unit-divider"></div>
            <div class="mui-subhead">ساکنین ثبت‌شده <span>{{ $F::num($parties->count()) }} نفر</span></div>
            @if($parties->isEmpty())
              <div class="mui-muted" style="font-size:11px;line-height:1.9">اطلاعات ساکنین هنوز توسط مدیریت تکمیل نشده است.</div>
            @else
              <div class="mui-people">
                @foreach($parties as $party)
                  @php $name = $party->user?->display_name ?: $party->user?->mobile ?: 'ساکن'; @endphp
                  <div class="mui-person" title="{{ $name }}">
                    <span class="mui-avatar">{{ mb_substr($name, 0, 1) }}</span>
                    <span class="mui-person-name">{{ $name }}</span>
                    <span class="mui-person-role">{{ $relationLabels[$party->relation] ?? 'ساکن' }}</span>
                  </div>
                @endforeach
              </div>
            @endif

            <div class="mui-unit-divider"></div>
            <div class="mui-subhead">امکانات اختصاصی <span>پارکینگ و انبار</span></div>
            <div class="mui-amenities">
              <div class="mui-amenity">
                <div class="mui-amenity-icon"><svg viewBox="0 0 24 24"><path d="M5 20V4h9a5 5 0 0 1 0 10H5M5 9h9a0 0 0 0 0 0 0"/></svg></div>
                <div><strong>{{ $parking ? 'پارکینگ '.$F::digits($parking->number) : 'پارکینگ تخصیص نیافته' }}</strong><small>{{ $parking ? ($parking->block?->name ?? 'پارکینگ ساختمان') : 'برای تخصیص با مدیریت تماس بگیرید' }}</small></div>
              </div>
              <div class="mui-amenity">
                <div class="mui-amenity-icon"><svg viewBox="0 0 24 24"><path d="M3 10 12 4l9 6v10H3zM8 20v-6h8v6M7 10h.01M17 10h.01"/></svg></div>
                <div><strong>{{ $storage ? 'انباری '.$F::digits($storage->number) : 'انباری تخصیص نیافته' }}</strong><small>{{ $storage ? ($storage->block?->name ?? 'انباری ساختمان') : 'در صورت نیاز از مدیریت پیگیری کنید' }}</small></div>
              </div>
            </div>
          </div>
          <div class="mui-unit-footer">
            <div><span class="mui-debt-label">مانده صورت‌حساب‌های این واحد</span><strong class="mui-debt-value" style="{{ $unitDebt > 0 ? 'color:#c45145' : 'color:#287b68' }}">{{ $unitDebt > 0 ? $F::money($unitDebt) : 'تسویه‌شده' }}</strong></div>
            <a class="mui-text-link" href="{{ route('my.invoices') }}">جزئیات مالی <span aria-hidden="true">←</span></a>
          </div>
        </article>
      @endforeach
    </div>
  @endif

  <div class="mui-lower @cannot('contract.view.own') mui-lower-single @endcannot">
    <section class="mui-box">
      <div class="mui-box-head"><h3>صورت‌حساب‌های اخیر</h3><a href="{{ route('my.invoices') }}">همه صورت‌حساب‌ها ←</a></div>
      @if($invoices->isEmpty())
        <div class="mui-empty-inline">صورت‌حسابی به نام شما ثبت نشده است.</div>
      @else
        <div class="mui-invoice-list">
          @foreach($invoices->take(4) as $invoice)
            @php
              $bill = $invoiceStatus[$invoice->status->value] ?? ['نامشخص', 'mui-bill-void'];
              $overdue = in_array($invoice->status->value, ['unpaid', 'partial'], true) && $invoice->due_date?->isPast();
              $periodLabel = $invoice->period ? ($F::MONTHS[$invoice->period->jalali_month - 1] ?? 'دوره').' '.$F::digits($invoice->period->jalali_year) : $F::date($invoice->issued_at, 'Y/m/d');
            @endphp
            <div class="mui-invoice">
              <div><div class="mui-invoice-title">{{ $invoiceType[$invoice->type->value] ?? 'صورت‌حساب' }} · {{ $invoice->unit?->block?->name }} / {{ $F::digits($invoice->unit?->number ?? '') }}</div><div class="mui-invoice-meta">{{ $periodLabel }} · سررسید {{ $F::date($invoice->due_date, 'Y/m/d') }} @if($overdue) · <span style="color:#c45145;font-weight:800">معوق</span>@endif</div></div>
              <div class="mui-invoice-side"><div class="mui-invoice-amount">{{ $F::money($invoice->amount) }}</div><span class="mui-pill {{ $bill[1] }}" style="margin-top:7px">{{ $bill[0] }}</span></div>
            </div>
          @endforeach
        </div>
      @endif
    </section>

    @can('contract.view.own')
    <section class="mui-box">
      <div class="mui-box-head"><h3>قراردادهای مرتبط</h3><a href="{{ route('my.contracts') }}">مشاهده همه ←</a></div>
      @php $visibleContracts = $contractsByUnit->flatten()->unique('id')->take(4); @endphp
      @forelse($visibleContracts as $contract)
        @php $typeLabel = $contract->type->value === 'rent' ? 'قرارداد اجاره' : 'قرارداد خرید'; @endphp
        <div class="mui-contract">
          <div class="mui-contract-icon"><svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6zM15 3v5h4M9 12h7M9 16h7"/></svg></div>
          <div><strong>{{ $typeLabel }} · {{ $contract->unit?->block?->name }} / {{ $F::digits($contract->unit?->number ?? '') }}</strong><small>تاریخ عقد: {{ $F::date($contract->signed_at, 'Y/m/d') }} · {{ $contract->status->label() }}</small></div>
        </div>
      @empty
        <div class="mui-empty-inline">قراردادی برای نمایش ثبت نشده است.</div>
      @endforelse
    </section>
    @endcan
  </div>

  <section class="mui-quicklinks" aria-label="دسترسی سریع">
    <a class="mui-quicklink" href="{{ route('my.invoices') }}"><span class="mui-quicklink-icon"><svg viewBox="0 0 24 24"><path d="M5 3h14v18l-3-2-4 2-4-2-3 2zM8 8h8M8 12h8M8 16h4"/></svg></span><span><strong>صورت‌حساب‌های من</strong><small>مبالغ، سررسید و وضعیت تسویه</small></span></a>
    <a class="mui-quicklink" href="{{ route('my.payments') }}"><span class="mui-quicklink-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M7 15h3"/></svg></span><span><strong>ثبت فیش پرداخت</strong><small>ارسال رسید برای بررسی مدیریت</small></span></a>
    @can('contract.view.own')
    <a class="mui-quicklink" href="{{ route('my.contracts') }}"><span class="mui-quicklink-icon"><svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6zM15 3v5h4M9 12h7M9 16h7"/></svg></span><span><strong>قراردادهای من</strong><small>مرور قراردادهای خرید و اجاره</small></span></a>
    @endcan
  </section>
</div>
@endsection
