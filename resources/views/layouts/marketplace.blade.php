<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
@php
    $marketplaceIndexing = app()->environment('production')
        && (bool) \App\Models\SiteSetting::get('seo.indexing_enabled', false)
        && filter_var((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), FILTER_VALIDATE_URL)
        && strtolower((string) parse_url((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), PHP_URL_SCHEME)) === 'https';
    $isFilteredMarketplace = request()->routeIs('properties.index') && request()->query() !== [];
    $canonicalUrl = request()->routeIs('properties.index') ? route('properties.index') : url()->current();
@endphp
<title>@yield('title', 'آگهی‌های ملکی') | صابخونه</title>
<meta name="description" content="@yield('meta_description', 'آگهی‌های خرید، فروش و اجاره ملک در بازار املاک صابخونه؛ مشاهده رایگان آگهی‌ها در شهرها و محله‌های مختلف.')">
<meta name="robots" content="{{ $marketplaceIndexing && !$isFilteredMarketplace ? 'index,follow,max-image-preview:large' : 'noindex,follow' }}">
<meta name="theme-color" content="#0f4a45">
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="icon" href="{{ asset('favicon.ico') }}">
<meta property="og:type" content="{{ request()->routeIs('properties.show') ? 'article' : 'website' }}">
<meta property="og:site_name" content="صابخونه">
<meta property="og:title" content="@yield('title', 'آگهی‌های ملکی') | صابخونه">
<meta property="og:description" content="@yield('meta_description', 'آگهی‌های خرید، فروش و اجاره ملک در بازار املاک صابخونه')">
<meta property="og:url" content="{{ $canonicalUrl }}">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('css/marketplace.css') }}?v={{ @filemtime(public_path('css/marketplace.css')) }}">
@stack('head')
</head><body>
{{-- آیکون‌های مشترک؛ با <use href="#i-..."> استفاده می‌شوند --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></symbol>
    <symbol id="i-area" viewBox="0 0 24 24"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></symbol>
    <symbol id="i-bed" viewBox="0 0 24 24"><path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 15h18M7 9V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2"/></symbol>
    <symbol id="i-bath" viewBox="0 0 24 24"><path d="M4 12h16v2a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2ZM6 12V6a2 2 0 0 1 3.5-1.3M7 19l-1 2M17 19l1 2"/></symbol>
    <symbol id="i-car" viewBox="0 0 24 24"><path d="M5 16V11l2-5h10l2 5v5M3 16h18v2H3zM7.5 13h.01M16.5 13h.01"/></symbol>
    <symbol id="i-floor" viewBox="0 0 24 24"><path d="M4 20h16M6 20V9l6-4 6 4v11M10 20v-5h4v5"/></symbol>
    <symbol id="i-cal" viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></symbol>
    <symbol id="i-doc" viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7zM14 3v4h4M10 13h5M10 17h5"/></symbol>
    <symbol id="i-swap" viewBox="0 0 24 24"><path d="M7 7h12l-3-3M17 17H5l3 3"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-9 9"/></symbol>
    <symbol id="i-chevron" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></symbol>
    <symbol id="i-chev-l" viewBox="0 0 24 24"><path d="m15 6-6 6 6 6"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
</svg>

<header class="top"><div class="wrap topin">
    <a class="brand" href="{{ route('properties.index') }}">
        <img src="{{ asset('images/sabkhoneh-mark.png') }}" alt="" width="34" height="34">
        <span>صابخونه<small>بازار املاک</small></span>
    </a>
    <nav class="nav" aria-label="منوی اصلی">
        <a href="{{ route('properties.index') }}" @if(request()->routeIs('properties.index','properties.show')) aria-current="page" @endif>آگهی‌ها</a>
        @auth<a href="{{ route('properties.mine') }}" @if(request()->routeIs('properties.mine')) aria-current="page" @endif>آگهی‌های من</a><a href="{{ route('properties.inquiries.index') }}" @if(request()->routeIs('properties.inquiries.*','properties.chat.*')) aria-current="page" @endif>پیام‌ها</a>@else<a href="{{ route('login') }}">ورود</a>@endauth
    </nav>
    <a class="btn" href="{{ route('properties.create') }}"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>ثبت آگهی رایگان</a>
</div></header>

<main class="wrap main">
    @if(session('status') && ! request()->routeIs('properties.mine'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @yield('content')
</main>
@stack('scripts')
</body></html>
