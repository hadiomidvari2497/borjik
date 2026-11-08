<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="@yield('viewport', 'width=device-width,initial-scale=1')">
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
</head><body class="@yield('body_class')">
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
    <symbol id="i-expand" viewBox="0 0 24 24"><path d="M9 4H4v5M15 4h5v5M9 20H4v-5M15 20h5v-5"/></symbol>
    <symbol id="i-share" viewBox="0 0 24 24"><circle cx="18" cy="5.5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="18.5" r="2.5"/><path d="m8.2 10.8 7.6-4M8.2 13.2l7.6 4"/></symbol>
    <symbol id="i-phone" viewBox="0 0 24 24"><path d="M6.6 3.5h2.7l1.4 4-1.8 1.2a11 11 0 0 0 5.4 5.4l1.2-1.8 4 1.4v2.7a2 2 0 0 1-2.2 2A15.5 15.5 0 0 1 4.6 5.7a2 2 0 0 1 2-2.2Z"/></symbol>
    <symbol id="i-chat" viewBox="0 0 24 24"><path d="M4 5h16v11H9l-5 4V5Z"/><path d="M8 9.5h8M8 12.5h5"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3 5 6v5.5c0 4.3 2.9 7.6 7 9 4.1-1.4 7-4.7 7-9V6l-7-3Z"/><path d="m9 12 2.2 2.2L15.5 10"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></symbol>
    <symbol id="i-link" viewBox="0 0 24 24"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></symbol>
    <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></symbol>
    <symbol id="i-hash" viewBox="0 0 24 24"><path d="M9 4 7 20M17 4l-2 16M4.5 9h16M3.5 15h16"/></symbol>
    <symbol id="i-ruler" viewBox="0 0 24 24"><path d="M3 15.5 15.5 3 21 8.5 8.5 21 3 15.5ZM7 11.5l2 2M10 8.5l2 2M13 5.5l2 2"/></symbol>
</svg>

@php $bare = trim($__env->yieldContent('bare')) !== ''; @endphp
@unless($bare)
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
@endunless

<main class="{{ $bare ? 'bare-main' : 'wrap main' }}">
    @if(! $bare && session('status') && ! request()->routeIs('properties.mine'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @yield('content')
</main>
@stack('scripts')
</body></html>
