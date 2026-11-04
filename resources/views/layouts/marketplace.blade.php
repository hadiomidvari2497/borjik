<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
@php
    $marketplaceIndexing = app()->environment('production')
        && (bool) \\App\\Models\\SiteSetting::get('seo.indexing_enabled', false)
        && filter_var((string) \\App\\Models\\SiteSetting::get('seo.site_url', config('app.url')), FILTER_VALIDATE_URL)
        && strtolower((string) parse_url((string) \\App\\Models\\SiteSetting::get('seo.site_url', config('app.url')), PHP_URL_SCHEME)) === 'https';
    $isFilteredMarketplace = request()->routeIs('properties.index') && request()->query() !== [];
    $canonicalUrl = request()->routeIs('properties.index') ? route('properties.index') : url()->current();
@endphp
<title>@yield('title', 'آگهی‌های ملکی') | صابخونه</title>
<meta name="description" content="@yield('meta_description', 'آگهی‌های خرید، فروش و اجاره ملک در بازار املاک صابخونه؛ مشاهده رایگان آگهی‌ها در شهرها و محله‌های مختلف.')">
<meta name="robots" content="{{ $marketplaceIndexing && !$isFilteredMarketplace ? 'index,follow,max-image-preview:large' : 'noindex,follow' }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:type" content="{{ request()->routeIs('properties.show') ? 'article' : 'website' }}">
<meta property="og:site_name" content="صابخونه">
<meta property="og:title" content="@yield('title', 'آگهی‌های ملکی') | صابخونه">
<meta property="og:description" content="@yield('meta_description', 'آگهی‌های خرید، فروش و اجاره ملک در بازار املاک صابخونه')">
<meta property="og:url" content="{{ $canonicalUrl }}">
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7f6;color:#193b38;font-family:Vazirmatn,Tahoma,sans-serif}a{color:inherit;text-decoration:none}.wrap{width:min(1160px,calc(100% - 32px));margin:auto}.top{background:#123b3b;color:white;padding:17px 0}.topin{display:flex;align-items:center;justify-content:space-between;gap:16px}.brand{font-size:20px;font-weight:900}.nav{display:flex;gap:18px;font-size:13px}.btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 17px;border:0;border-radius:12px;background:#217b6d;color:white;font:inherit;font-size:13px;font-weight:800;cursor:pointer}.btn.light{background:#e5f2ef;color:#17675c}.main{padding:30px 0 60px}.hero{padding:28px;border-radius:22px;background:linear-gradient(120deg,#123b3b,#268a78);color:white;margin-bottom:22px}.hero h1{margin:0 0 8px;font-size:27px}.hero p{margin:0;color:#d9eee8;font-size:13px;line-height:2}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:17px}.card{overflow:hidden;background:white;border:1px solid #e1eae7;border-radius:18px;box-shadow:0 8px 25px #173b3510}.photo{height:180px;background:#e7efec;display:grid;place-items:center;color:#78918a}.photo img{width:100%;height:100%;object-fit:cover}.body{padding:16px}.body h3{margin:0 0 8px;font-size:16px}.muted{color:#758984;font-size:12px}.price{margin-top:12px;color:#176b5d;font-weight:900}.filters,.panel{background:white;border:1px solid #e1eae7;border-radius:18px;padding:18px;margin-bottom:18px}.fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}.field{display:grid;gap:6px;font-size:12px;font-weight:700}.field input,.field select,.field textarea{width:100%;border:1px solid #d9e4e0;border-radius:10px;padding:11px;background:#fff;font:inherit;font-size:13px;color:#193b38}.field textarea{min-height:110px}.actions{display:flex;gap:10px;align-items:center;margin-top:16px;flex-wrap:wrap}.error{color:#b42318;font-size:12px}.pagination{margin-top:20px}.detail{display:grid;grid-template-columns:1.2fr 1fr;gap:22px}.photos{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}.photos img{width:100%;height:210px;object-fit:cover;border-radius:14px}.photos img:first-child{grid-column:span 2;height:300px}@media(max-width:700px){.detail{grid-template-columns:1fr}.nav{gap:9px}.hero h1{font-size:22px}}
</style>
</head><body>
<header class="top"><div class="wrap topin"><a class="brand" href="{{ route('properties.index') }}">صابخونه | بازار املاک</a><nav class="nav"><a href="{{ route('properties.index') }}">آگهی‌ها</a>@auth<a href="{{ route('properties.mine') }}">آگهی‌های من</a>@else<a href="{{ route('login') }}">ورود</a>@endauth</nav><a class="btn light" href="{{ route('properties.create') }}">ثبت آگهی رایگان</a></div></header>
<main class="wrap main">@if(session('status'))<div class="panel">{{ session('status') }}</div>@endif @yield('content')</main>
</body></html>
