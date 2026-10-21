<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#1f6a63">
<meta name="color-scheme" content="light dark">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="برجیک">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="192x192" href="/pwa/icon-192.png">
<link rel="apple-touch-icon" href="/pwa/icon-192.png">
<title>@yield('title', 'داشبورد') | برجیک</title>
<link rel="preload" href="{{ asset('fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('css/jdate.css') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap-toast.rtl.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/notify.css') }}">
<style>
.workspace-panel{display:block!important;padding:0!important;overflow:hidden;border:1px solid var(--gb);background:var(--panel);border-radius:28px}
.workspace-panel .workspace-hero{margin:0;padding:clamp(25px,4vw,38px);display:flex;align-items:center;justify-content:space-between;gap:24px;position:relative;isolation:isolate;overflow:hidden;border:0;border-radius:27px 27px 0 0;background:radial-gradient(ellipse at 8% 0%,rgba(139,239,213,.25),transparent 36%),radial-gradient(ellipse at 100% 100%,rgba(99,213,192,.2),transparent 38%),linear-gradient(118deg,#102b3b 0%,#155c5c 52%,#278778 100%);box-shadow:inset 0 -1px 0 rgba(255,255,255,.13);color:#fff}
.workspace-hero:before{content:"";position:absolute;z-index:-1;width:310px;height:310px;inset-inline-end:6%;top:-235px;border:1px solid rgba(255,255,255,.19);border-radius:50%;box-shadow:0 0 0 24px rgba(255,255,255,.035),0 0 0 54px rgba(255,255,255,.025),0 0 0 84px rgba(255,255,255,.018);pointer-events:none}
.workspace-hero:after{content:"";position:absolute;z-index:-1;width:5px;inset-block:26px;inset-inline-start:0;border-radius:8px;background:linear-gradient(180deg,#baf5df,#4bc6ac);box-shadow:0 0 24px rgba(129,242,207,.45);pointer-events:none}
.workspace-hero-copy{position:relative;z-index:1;min-width:0}
.workspace-hero-kicker{display:inline-flex;align-items:center;gap:8px;margin-bottom:12px;padding:6px 11px;border:1px solid rgba(255,255,255,.24);border-radius:999px;background:rgba(255,255,255,.09);color:#e1fff4;font-size:11px;font-weight:800;letter-spacing:.1px}
.workspace-hero-kicker:before{content:"";width:6px;height:6px;border-radius:50%;background:#9af0d0;box-shadow:0 0 0 4px rgba(154,240,208,.13)}
.workspace-hero h2{margin:0 0 8px;color:#fff;font-size:clamp(24px,3.1vw,34px);font-weight:900;line-height:1.45;letter-spacing:-.6px;text-wrap:balance}
.workspace-hero p{max-width:640px;margin:0;color:#d8f1e9;font-size:12px;line-height:2.1}
.workspace-hero-mark{position:relative;display:grid;place-items:center;flex:0 0 82px;width:82px;height:82px;border:1px solid rgba(255,255,255,.26);border-radius:24px;background:linear-gradient(145deg,rgba(255,255,255,.16),rgba(255,255,255,.045));box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 12px 28px rgba(4,34,39,.12);backdrop-filter:blur(10px)}
.workspace-hero-mark:before{content:"";position:absolute;inset:7px;border:1px solid rgba(255,255,255,.12);border-radius:18px}
.workspace-hero-mark .i{width:37px;height:37px;color:#e4fff4;stroke-width:1.4;filter:drop-shadow(0 4px 10px rgba(0,0,0,.12))}
.workspace-panel-content{padding:22px;min-width:0}
.workspace-panel-content>*+*{margin-top:20px}
@media(max-width:600px){.workspace-panel{border-radius:22px}.workspace-panel .workspace-hero{padding:22px 19px;border-radius:21px 21px 0 0;gap:14px}.workspace-hero-mark{flex-basis:55px;width:55px;height:55px;border-radius:17px}.workspace-hero-mark:before{inset:5px;border-radius:12px}.workspace-hero-mark .i{width:27px;height:27px}.workspace-hero h2{font-size:22px}.workspace-hero p{font-size:11px;line-height:1.95}.workspace-panel-content{padding:15px}}
@media(prefers-reduced-motion:no-preference){.workspace-hero-mark{transition:transform .25s ease,background .25s ease}.workspace-panel:hover .workspace-hero-mark{transform:translateY(-2px);background:linear-gradient(145deg,rgba(255,255,255,.2),rgba(255,255,255,.06))}}

/* Keep bespoke workspace heroes on specialized pages in the same visual system. */
.docs-hero,.poll-hero,.cal-hero,.sms-hero,.task-hero,.shift-hero,.notice-hero,.role-hero,.mui-hero,.phonebook-hero{
  border-radius:27px!important;
  background:radial-gradient(ellipse at 8% 0%,rgba(139,239,213,.22),transparent 36%),radial-gradient(ellipse at 100% 100%,rgba(99,213,192,.16),transparent 38%),linear-gradient(118deg,#102b3b 0%,#155c5c 52%,#278778 100%)!important;
  box-shadow:inset 0 -1px 0 rgba(255,255,255,.13),0 12px 30px rgba(16,65,65,.08)!important;
  color:#fff!important;
}
.docs-hero h2,.poll-hero h2,.cal-hero h2,.sms-hero h2,.task-hero h2,.shift-hero h2,.notice-hero h2,.role-hero h2,.mui-hero h2,.phonebook-hero h2{
  color:#fff!important;font-weight:900!important;letter-spacing:-.4px!important;line-height:1.5!important;
}
.docs-hero p,.poll-hero p,.cal-hero p,.sms-hero p,.task-hero p,.shift-hero p,.notice-hero p,.role-hero p,.mui-hero p,.phonebook-hero p{
  color:#d8f1e9!important;line-height:2!important;
}
.docs-hero:after,.poll-hero:after,.cal-hero:after,.role-hero:after,.mui-hero:before,.mui-hero:after{
  border-color:rgba(255,255,255,.16)!important;
}
.docs-stat,.poll-hero-stat,.cal-date-card,.sms-count,.shift-stat{
  border-color:rgba(255,255,255,.26)!important;background:rgba(255,255,255,.09)!important;
}
@media(max-width:600px){
  .docs-hero,.poll-hero,.cal-hero,.sms-hero,.task-hero,.shift-hero,.notice-hero,.role-hero,.mui-hero,.phonebook-hero{border-radius:22px!important}
}

</style>
</head>
<body>
@include('partials.icons')

@if(session('status'))<div data-borjik-flash="status" hidden>{{ session('status') }}</div>@endif
@if(session('success'))<div data-borjik-flash="success" hidden>{{ session('success') }}</div>@endif
@if(session('warning'))<div data-borjik-flash="warning" hidden>{{ session('warning') }}</div>@endif
@if(session('error'))<div data-borjik-flash="error" hidden>{{ session('error') }}</div>@endif
@if($errors->has('general'))<div data-borjik-flash="error" hidden>{{ $errors->first('general') }}</div>@endif

<div class="app">
  <div class="scrim" id="scrim"></div>

  <aside class="side" id="side">
    <div class="brand"><div class="logo">ب</div><div><b>برجیک</b><span>سامانه مدیریت ساختمان</span></div></div>

    <div class="sw" id="sw">
      <button type="button" id="swb" aria-haspopup="true" aria-expanded="false">
        <svg class="i"><use href="#bld"/></svg>
        <div><b>{{ $currentBuilding?->name ?? 'هنوز ساختمانی ندارید' }}</b><small>{{ $currentBuilding ? $currentRoles : 'برای شروع ساختمان بسازید' }}</small></div>
        @if($myBuildings->count() > 1)<svg class="i ch"><use href="#chv"/></svg>@endif
      </button>
      @if($myBuildings->count() > 1)
        <ul>
          @foreach($myBuildings as $b)
            <li>
              <form method="POST" action="{{ route('building.switch', $b['id']) }}">@csrf
                <input type="hidden" name="membership_id" value="{{ $b['membership_id'] }}">
                <button type="submit">{{ $b['name'] }}<small>{{ $b['roles'] }}</small></button>
              </form>
            </li>
          @endforeach
        </ul>
      @endif
    </div>

    <nav class="nav" aria-label="منوی اصلی">
      @foreach($menu as $group)
        <div class="grp">{{ $group['title'] }}</div>
        @foreach($group['items'] as $item)
          <a href="{{ $item['url'] }}" @class(['on' => $item['active'], 'lock' => $item['locked']]) @if($item['active']) aria-current="page" @endif @if($item['locked']) title="با تهیه‌ی اشتراک فعال می‌شود" @endif>
            <svg class="i"><use href="#{{ $item['icon'] }}"/></svg>{{ $item['label'] }}
            @if($item['locked'])<svg class="i" style="margin-inline-start:auto;width:15px;height:15px"><use href="#lock"/></svg>@endif
            @if($item['badge'] > 0)<span class="bd">{{ \App\Support\Fa::num($item['badge']) }}</span>@endif
          </a>
        @endforeach
      @endforeach
      <div class="grp">دعوت‌نامه‌ها</div>
      <a href="{{ route('invitations.index') }}" @class(['on' => request()->routeIs('invitations.*')]) @if(request()->routeIs('invitations.*')) aria-current="page" @endif>
        <svg class="i"><use href="#inbox"/></svg>دعوت‌نامه‌های من
      </a>
    </nav>

    <div class="grp">حساب کاربری</div>
    <div class="me">
      <div class="row">
        <div class="av">{{ mb_substr(auth()->user()->display_name ?: auth()->user()->mobile, 0, 2) }}</div>
        <div><b>{{ auth()->user()->display_name ?: auth()->user()->mobile }}</b><span>{{ $currentRoles ?: 'کاربر جدید' }}</span></div>
      </div>
      <form method="POST" action="{{ route('logout') }}">@csrf<button class="out" type="submit">خروج از سامانه</button></form>
    </div>
  </aside>

  <main class="main">
    <header class="top">
      <div style="display:flex;align-items:center;gap:10px">
        <button class="ib burger" id="burger" type="button" aria-label="باز کردن منو"><svg class="i"><use href="#menu"/></svg></button>
        {{-- Page titles live in the content panel so they never compete with the page header. --}}
      </div>
      <div class="acts">
        <a class="ib" href="{{ route('invitations.index') }}" aria-label="دعوت‌نامه‌ها" title="دعوت‌نامه‌ها"><svg class="i"><use href="#inbox"/></svg></a>
        <button class="ib" id="theme" type="button" aria-label="تغییر حالت تیره و روشن"><svg class="i"><use href="#moon"/></svg></button>
        <a class="ib" href="{{ $currentBuilding ? route('notifications.index') : '#' }}" aria-label="اعلان‌ها" title="اعلان‌ها" style="position:relative"><svg class="i"><use href="#bell"/></svg>@php($unreadNotifications = $currentBuilding ? auth()->user()->unreadNotifications()->where('data->building_id', $currentBuilding->id)->count() : 0)@if($unreadNotifications > 0)<span class="bd" style="position:absolute;top:-4px;left:-4px">{{ \App\Support\Fa::num(min($unreadNotifications, 99)) }}</span>@endif</a>
        <a class="av" href="{{ route('profile.edit') }}" aria-label="پروفایل">{{ mb_substr(auth()->user()->display_name ?: auth()->user()->mobile, 0, 2) }}</a>
      </div>
    </header>

    <div class="scroll">
      @if(!request()->routeIs('dashboard*', 'admin.*', 'staff-roles.*', 'documents.*', 'my.units', 'polls.index', 'polls.show', 'events.index', 'my.requests*', 'requests.*', 'tickets.*', 'shifts.index', 'notifications.index', 'sms-templates.*', 'phonebook.*', 'operations-center.index', 'maintenance.*', 'treasury.*'))
        <section class="panel workspace-panel">
          <header class="workspace-hero" aria-label="معرفی بخش">
            <div class="workspace-hero-copy">
              <div class="workspace-hero-kicker">برجیک <span aria-hidden="true">·</span> مدیریت هوشمند ساختمان</div>
              <h2>@yield('title', 'فضای کاری')</h2>
              <p>
                @if(request()->routeIs('buildings.*', 'building.*'))
                  اطلاعات ساختمان، دسترسی‌ها و تنظیمات اصلی را از این بخش مدیریت کنید.
                @elseif(request()->routeIs('units.*', 'blocks.*', 'parkings.*', 'storages.*'))
                  اطلاعات واحدها و فضاهای مشترک را مرتب، دقیق و یکجا مدیریت کنید.
                @elseif(request()->routeIs('charges.*', 'charge-settings.*', 'expenses.*', 'payments.*', 'my.invoices', 'my.payments*'))
                  صورت‌حساب‌ها، پرداخت‌ها و هزینه‌ها را شفاف و منظم پیگیری کنید.
                @elseif(request()->routeIs('contracts.*', 'my.contracts'))
                  جزئیات قراردادها و وضعیت آن‌ها را در یک نمای منظم دنبال کنید.
                @elseif(request()->routeIs('team.*', 'members.*', 'staff.*', 'staff-roles.*', 'invitations.*'))
                  اعضای ساختمان، نقش‌ها و دعوت‌نامه‌ها را از همین بخش مدیریت کنید.
                @elseif(request()->routeIs('tickets.*', 'requests.*', 'my.requests*'))
                  درخواست‌ها و کارهای جاری را مرحله‌به‌مرحله پیگیری کنید.
                @elseif(request()->routeIs('notifications.*'))
                  پیام‌ها و رویدادهای مهم ساختمان را یکجا ببینید و پیگیری کنید.
                @elseif(request()->routeIs('subscription.*'))
                  وضعیت اشتراک و امکانات در دسترس ساختمان را بررسی کنید.
                @elseif(request()->routeIs('profile.*', 'account.*'))
                  اطلاعات حساب و تنظیمات شخصی خود را مدیریت کنید.
                @elseif(request()->routeIs('audit.*'))
                  رویدادها و تغییرات ثبت‌شده را برای پیگیری و شفافیت بررسی کنید.
                @else
                  همه‌ی اطلاعات موردنیاز ساختمان، با تجربه‌ای ساده‌تر و منظم‌تر در دسترس شماست.
                @endif
              </p>
            </div>
            <div class="workspace-hero-mark" aria-hidden="true">
              <svg class="i"><use href="#bld"/></svg>
            </div>
          </header>
          <div class="workspace-panel-content">
            @yield('content')
          </div>
        </section>
      @else
        <section class="panel">
          @yield('content')
        </section>
      @endif
    </div>

    <nav class="bnav" aria-label="ناوبری موبایل">
      @foreach(collect($menu)->flatMap(fn ($g) => $g['items'])->take(4) as $item)
        <a href="{{ $item['url'] }}" @class(['on' => $item['active']])><svg class="i"><use href="#{{ $item['icon'] }}"/></svg>{{ \Illuminate\Support\Str::limit($item['label'], 12) }}</a>
      @endforeach
      <button id="more" type="button"><svg class="i"><use href="#menu"/></svg>بیشتر</button>
    </nav>
  </main>
</div>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('js/jdate.js') }}"></script>
<script src="{{ asset('js/ui.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
(() => {
  if (window.__borjikLivePagination) return;
  window.__borjikLivePagination = true;
  const load = async (url, pushState) => {
    const current = document.querySelector('.scroll');
    if (!current) return;
    current.setAttribute('aria-busy', 'true');
    try {
      const response = await fetch(url, {credentials:'same-origin', headers:{'Accept':'text/html','X-Requested-With':'XMLHttpRequest'}});
      if (!response.ok) throw new Error('بارگذاری صفحه انجام نشد.');
      const html = await response.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const next = doc.querySelector('.scroll');
      if (!next) { window.location.href = url; return; }
      current.innerHTML = next.innerHTML;
      current.removeAttribute('aria-busy');
      document.title = doc.title || document.title;
      if (pushState) history.pushState({livePagination:true}, '', url);
      current.querySelectorAll('script').forEach(oldScript => {
        const script = document.createElement('script');
        for (const attr of oldScript.attributes) script.setAttribute(attr.name, attr.value);
        script.textContent = oldScript.textContent;
        oldScript.replaceWith(script);
      });
      current.scrollTop = 0;
      window.dispatchEvent(new CustomEvent('borjik:content-updated'));
    } catch (error) {
      current.removeAttribute('aria-busy');
      window.location.href = url;
    }
  };
  document.addEventListener('click', event => {
    const link = event.target.closest('.scroll .pagination a[href], .scroll nav[role="navigation"] a[href*="page="]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target === '_blank') return;
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) return;
    event.preventDefault();
    load(url.href, true);
  });
  history.replaceState({livePagination:true}, '', window.location.href);
  window.addEventListener('popstate', () => load(window.location.href, false));
})();
</script>
<script>
(function () {
  const nav = document.querySelector('.nav');
  if (!nav) return;

  const storageKey = 'borjik.sidebar.nav.scrollTop';
  const restoreScroll = function () {
    try {
      const saved = sessionStorage.getItem(storageKey);
      if (saved !== null) nav.scrollTop = Number(saved) || 0;
    } catch (e) {}
  };
  const saveScroll = function () {
    try {
      sessionStorage.setItem(storageKey, String(nav.scrollTop));
    } catch (e) {}
  };

  restoreScroll();
  window.addEventListener('pageshow', restoreScroll);
  window.addEventListener('pagehide', saveScroll);
  nav.addEventListener('click', function (event) {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 ||
        event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ||
        link.target === '_blank' || link.hasAttribute('download')) return;
    saveScroll();
  });
})();
</script>
</body>
</html>
