<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#1c4f4a">
<meta name="color-scheme" content="light dark">
<title>@yield('title', 'پنل مدیریت') | برجیک</title>
<link rel="preload" href="{{ asset('fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('css/jdate.css') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap-toast.rtl.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/notify.css') }}">
<script>try{var t=localStorage.getItem('borjik-theme');if(t)document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
</head>
<body class="adm">
@include('partials.icons')
@php
  $adm = auth('admin')->user();
  $menu = \App\Support\AdminMenu::for($adm);
  $initials = mb_substr($adm->name ?: $adm->username, 0, 2);
  $roleLabel = $adm->is_super ? 'سوپرادمین' : ($adm->roles->pluck('name')->join('، ') ?: 'بدون نقش');
@endphp

@if(session('status'))<div data-borjik-flash="status" hidden>{{ session('status') }}</div>@endif
@if(session('success'))<div data-borjik-flash="success" hidden>{{ session('success') }}</div>@endif
@if(session('warning'))<div data-borjik-flash="warning" hidden>{{ session('warning') }}</div>@endif
@if(session('error'))<div data-borjik-flash="error" hidden>{{ session('error') }}</div>@endif

<div class="app">
  <div class="scrim" id="scrim"></div>

  <aside class="side" id="side">
    <div class="brand"><div class="logo">ب</div><div><b>برجیک</b><span>پنل مدیریت سیستم</span></div></div>

    <nav class="nav" aria-label="منوی مدیریت">
      @foreach($menu as $group)
        <div class="grp">{{ $group['title'] }}</div>
        @foreach($group['items'] as $item)
          <a href="{{ $item['url'] }}" @class(['on' => $item['active']]) @if($item['active']) aria-current="page" @endif>
            <svg class="i"><use href="#{{ $item['icon'] }}"/></svg>{{ $item['label'] }}
          </a>
        @endforeach
      @endforeach
    </nav>

    <div class="grp">حساب کاربری</div>
    <div class="me">
      <div class="row">
        <div class="av">{{ $initials }}</div>
        <div><b>{{ $adm->name ?: $adm->username }}</b><span>{{ $roleLabel }}</span></div>
      </div>
      <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="out" type="submit">خروج از پنل مدیریت</button></form>
    </div>
  </aside>

  <main class="main">
    <header class="top">
      <div style="display:flex;align-items:center;gap:10px">
        <button class="ib burger" id="burger" type="button" aria-label="باز کردن منو"><svg class="i"><use href="#menu"/></svg></button>
        <h1>@yield('title', 'پنل مدیریت')</h1>
      </div>
      <div class="acts">
        <span class="tag-adm hm">مدیر سیستم</span>
        <button class="ib" id="theme" type="button" aria-label="تغییر حالت تیره و روشن"><svg class="i"><use href="#moon"/></svg></button>
        <a class="av" href="{{ route('admin.account') }}" aria-label="حساب من">{{ $initials }}</a>
      </div>
    </header>

    <div class="scroll">
      <section class="panel">
        @yield('content')
      </section>
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
<script src="{{ asset('js/admin.js') }}"></script>
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
  window.addEventListener('popstate', event => {
    if (event.state && event.state.livePagination) load(window.location.href, false);
  });
})();
</script>
@stack('scripts')
</body>
</html>
