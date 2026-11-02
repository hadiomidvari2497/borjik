@extends('layouts.admin-guest')
@section('title', 'تأیید دومرحله‌ای مدیر')
@section('content')
<div class="two-factor-page">
  <div class="two-factor-heading">
    <span class="two-factor-icon" aria-hidden="true">⌁</span>
    <h1 class="h1">تأیید دومرحله‌ای</h1>
    <p class="lead">برای ورود به پنل مدیریت، یکی از روش‌های زیر را انتخاب کنید.</p>
  </div>

  <div class="two-factor-tabs" role="tablist" aria-label="روش تأیید هویت">
    <button class="two-factor-tab {{ session('two_factor_tab', 'authenticator') === 'authenticator' ? 'is-active' : '' }}" id="authenticator-tab" type="button" role="tab" aria-selected="{{ session('two_factor_tab', 'authenticator') === 'authenticator' ? 'true' : 'false' }}" aria-controls="authenticator-panel" data-tab="authenticator">
      <span class="tab-symbol" aria-hidden="true">◈</span>
      <span><b>Authenticator</b><small>کد برنامه احراز هویت</small></span>
    </button>
    @if($smsAvailable)
      <button class="two-factor-tab {{ session('two_factor_tab') === 'sms' ? 'is-active' : '' }}" id="sms-tab" type="button" role="tab" aria-selected="{{ session('two_factor_tab') === 'sms' ? 'true' : 'false' }}" aria-controls="sms-panel" data-tab="sms">
        <span class="tab-symbol" aria-hidden="true">✉</span>
        <span><b>پیامک</b><small>ارسال کد به موبایل</small></span>
      </button>
    @endif
  </div>

  <section class="two-factor-panel {{ session('two_factor_tab', 'authenticator') === 'authenticator' ? 'is-visible' : '' }}" id="authenticator-panel" role="tabpanel" aria-labelledby="authenticator-tab" data-panel="authenticator" {{ session('two_factor_tab', 'authenticator') === 'authenticator' ? '' : 'hidden' }}>
    <div class="method-title"><span class="method-dot"></span><b>کد برنامه احراز هویت</b></div>
    <p class="method-description">کد ۶ رقمی نمایش‌داده‌شده در Google Authenticator یا برنامه مشابه را وارد کنید.</p>
    <form method="POST" action="{{ route('admin.two-factor.verify') }}">@csrf
      <div class="field">
        <input class="otp" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="••••••" data-digits required autofocus>
        @error('code')<div class="err">{{ $message }}</div>@enderror
      </div>
      <button class="btn" type="submit">تأیید و ورود</button>
    </form>
  </section>

  @if($smsAvailable)
    <section class="two-factor-panel {{ session('two_factor_tab') === 'sms' ? 'is-visible' : '' }}" id="sms-panel" role="tabpanel" aria-labelledby="sms-tab" data-panel="sms" {{ session('two_factor_tab') === 'sms' ? '' : 'hidden' }}>
      <div class="method-title"><span class="method-dot"></span><b>ورود با کد پیامکی</b></div>
      <p class="method-description">کد یک‌بارمصرف برای شماره زیر ارسال می‌شود.</p>
      <div class="masked-mobile" dir="ltr"><span aria-hidden="true">✉</span>{{ $maskedMobile }}</div>
      <form method="POST" action="{{ route('admin.two-factor.sms.request') }}">@csrf
        <button class="btn btn-outline" type="submit">ارسال کد پیامکی</button>
      </form>
      <div class="sms-code-divider"><span>پس از دریافت پیامک</span></div>
      <form method="POST" action="{{ route('admin.two-factor.sms.verify') }}">@csrf
        <div class="field">
          <input class="otp" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="کد پیامکی ۶ رقمی" data-digits required>
          @error('code')<div class="err">{{ $message }}</div>@enderror
        </div>
        <button class="btn" type="submit">تأیید پیامک و ورود</button>
      </form>
    </section>
  @endif

  <p class="fine"><a href="{{ route('admin.login') }}">بازگشت به ورود</a></p>
</div>

<style>
.two-factor-page{width:100%;max-width:440px;margin-inline:auto}
.two-factor-heading{text-align:center;margin-bottom:26px}
.two-factor-icon{display:grid;place-items:center;width:54px;height:54px;margin:0 auto 14px;border-radius:18px;background:rgba(28,79,74,.09);color:#1c4f4a;font-size:27px}
.two-factor-heading .h1{margin:0 0 9px}
.two-factor-heading .lead{margin:0;line-height:1.9}
.two-factor-tabs{display:grid;grid-template-columns:repeat({{ $smsAvailable ? 2 : 1 }},minmax(0,1fr));gap:9px;padding:6px;background:#f1f5f4;border:1px solid #e3ebe8;border-radius:16px;margin-bottom:18px}
.two-factor-tab{display:flex;align-items:center;justify-content:center;gap:10px;min-height:68px;padding:10px 8px;border:1px solid transparent;border-radius:12px;background:transparent;color:#60716e;cursor:pointer;font:inherit;text-align:right;transition:background .25s ease,border-color .25s ease,box-shadow .25s ease,color .25s ease}
.two-factor-tab b,.two-factor-tab small{display:block}
.two-factor-tab b{font-size:13px;font-weight:800}
.two-factor-tab small{font-size:10px;margin-top:4px;color:#879591}
.two-factor-tab .tab-symbol{display:grid;place-items:center;width:34px;height:34px;flex:0 0 34px;border-radius:10px;background:#e5ece9;font-size:18px}
.two-factor-tab.is-active{background:#fff;border-color:#dce8e3;color:#173f3a;box-shadow:0 3px 12px rgba(20,58,55,.07)}
.two-factor-tab.is-active .tab-symbol{background:#e4f1eb;color:#1c6a54}
.two-factor-panel{padding:22px 20px;border:1px solid #e6ece9;border-radius:16px;background:#fff;box-shadow:0 8px 28px rgba(20,58,55,.045)}
.two-factor-panel[hidden]{display:none}
.two-factor-panel.is-visible{animation:twoFactorEnter .32s cubic-bezier(.2,.75,.25,1) both}
@keyframes twoFactorEnter{from{opacity:0;transform:translateY(9px) scale(.99)}to{opacity:1;transform:translateY(0) scale(1)}}
.method-title{display:flex;align-items:center;gap:9px;color:#203d38;font-size:14px}
.method-dot{width:8px;height:8px;border-radius:50%;background:#2d8a6e;box-shadow:0 0 0 4px #e7f3ed}
.method-description{font-size:12px;line-height:1.9;color:#71807c;margin:11px 0 18px}
.two-factor-panel .field{margin-bottom:14px}
.two-factor-panel .otp{text-align:center;letter-spacing:.35em;font-size:21px;direction:ltr}
.two-factor-panel .btn{width:100%;min-height:46px}
.two-factor-panel .btn-outline{background:#fff!important;color:#1c6253!important;border:1px solid #b9d5ca!important;margin-bottom:3px}
.masked-mobile{display:flex;justify-content:center;align-items:center;gap:9px;padding:12px;margin:-3px 0 16px;border-radius:11px;background:#f5f8f7;color:#315c51;font-weight:700;letter-spacing:.08em}
.sms-code-divider{display:flex;align-items:center;gap:10px;margin:17px 0;color:#9aa7a3;font-size:11px}
.sms-code-divider:before,.sms-code-divider:after{content:"";height:1px;flex:1;background:#e8eeeb}
.two-factor-page .fine{text-align:center;margin-top:20px}
@media(max-width:480px){.two-factor-panel{padding:18px 14px}.two-factor-tab{gap:7px}.two-factor-tab b{font-size:12px}}
@media(prefers-reduced-motion:reduce){.two-factor-tab{transition:none}.two-factor-panel.is-visible{animation:none}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const tabs=[...document.querySelectorAll('[data-tab]')];
  const panels=[...document.querySelectorAll('[data-panel]')];
  function activate(name,moveFocus){
    tabs.forEach(function(tab){
      const active=tab.dataset.tab===name;
      tab.classList.toggle('is-active',active);
      tab.setAttribute('aria-selected',active?'true':'false');
      tab.tabIndex=active?0:-1;
      if(active&&moveFocus)tab.focus();
    });
    panels.forEach(function(panel){
      const active=panel.dataset.panel===name;
      panel.hidden=!active;
      panel.classList.toggle('is-visible',active);
    });
  }
  tabs.forEach(function(tab,index){
    tab.addEventListener('click',function(){activate(tab.dataset.tab,false)});
    tab.addEventListener('keydown',function(event){
      if(event.key!=='ArrowLeft'&&event.key!=='ArrowRight')return;
      event.preventDefault();
      const next=(index+(event.key==='ArrowLeft'?1:-1)+tabs.length)%tabs.length;
      activate(tabs[next].dataset.tab,true);
    });
  });
  const initial=tabs.find(tab=>tab.classList.contains('is-active'))||tabs[0];
  if(initial)activate(initial.dataset.tab,false);
});
</script>
@endsection
