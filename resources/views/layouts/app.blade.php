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
        <h1>@yield('title', 'داشبورد')</h1>
      </div>
      <div class="acts">
        <a class="ib" href="{{ route('invitations.index') }}" aria-label="دعوت‌نامه‌ها" title="دعوت‌نامه‌ها"><svg class="i"><use href="#inbox"/></svg></a>
        <button class="ib" id="theme" type="button" aria-label="تغییر حالت تیره و روشن"><svg class="i"><use href="#moon"/></svg></button>
        <a class="ib" href="{{ route('notifications.index') }}" aria-label="اعلان‌ها" title="اعلان‌ها" style="position:relative"><svg class="i"><use href="#bell"/></svg>@php($unreadNotifications = auth()->user()->unreadNotifications()->where('data->building_id', $currentBuilding?->id())->count())@if($unreadNotifications > 0)<span class="bd" style="position:absolute;top:-4px;left:-4px">{{ \App\Support\Fa::num(min($unreadNotifications, 99)) }}</span>@endif</a>
        <a class="av" href="{{ route('profile.edit') }}" aria-label="پروفایل">{{ mb_substr(auth()->user()->display_name ?: auth()->user()->mobile, 0, 2) }}</a>
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
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
