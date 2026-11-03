<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0b2434">
@php
    $seoBase = rtrim((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), '/');
    $seoHost = strtolower((string) parse_url($seoBase, PHP_URL_HOST));
    $seoPublic = filter_var($seoBase, FILTER_VALIDATE_URL)
        && strtolower((string) parse_url($seoBase, PHP_URL_SCHEME)) === 'https'
        && !in_array($seoHost, ['localhost', '127.0.0.1', '::1'], true)
        && empty(parse_url($seoBase, PHP_URL_USER)) && empty(parse_url($seoBase, PHP_URL_PASS))
        && in_array((string) parse_url($seoBase, PHP_URL_PATH), ['', '/'], true)
        && parse_url($seoBase, PHP_URL_QUERY) === null && parse_url($seoBase, PHP_URL_FRAGMENT) === null;
    $seoIndexable = $seoPublic && app()->environment('production') && (bool) \App\Models\SiteSetting::get('seo.indexing_enabled', false);
    $pageUrl = $seoBase.'/'.ltrim($slug, '/');
    $pageSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $page['title'],
        'description' => $page['description'],
        'url' => $pageUrl,
        'inLanguage' => 'fa-IR',
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'صابخونه', 'url' => $seoBase.'/'],
        'about' => ['@type' => 'Thing', 'name' => $page['heading']],
    ];
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'صفحهٔ اصلی', 'item' => $seoBase.'/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $page['heading'], 'item' => $pageUrl],
        ],
    ];
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
        ], $page['faqs']),
    ];
@endphp
<title>{{ $page['title'] }}</title>
<meta name="description" content="{{ $page['description'] }}">
<meta name="robots" content="{{ $seoIndexable ? 'index,follow' : 'noindex,nofollow' }}">
@if($seoPublic && app()->environment('production'))<link rel="canonical" href="{{ $pageUrl }}">@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="صابخونه">
<meta property="og:locale" content="fa_IR">
<meta property="og:title" content="{{ $page['title'] }}">
<meta property="og:description" content="{{ $page['description'] }}">
<meta property="og:url" content="{{ $pageUrl }}">
<meta property="og:image" content="{{ $seoBase }}/images/og-sabkhoneh.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $page['title'] }}">
<meta name="twitter:description" content="{{ $page['description'] }}">
<meta name="twitter:image" content="{{ $seoBase }}/images/og-sabkhoneh.png">
@if($seoPublic && app()->environment('production'))
<script type="application/ld+json">{!! json_encode($pageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endif
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<style>
:root{--ink:#102b3a;--muted:#607681;--teal:#13a895;--deep:#087f78;--line:#dcebe6;--paper:#f3faf8}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--paper);color:var(--ink);font-family:Vazirmatn,Tahoma,sans-serif;line-height:2}
a{color:inherit;text-decoration:none}.wrap{width:min(1080px,calc(100% - 32px));margin:auto}
.top{position:relative;overflow:hidden;background:radial-gradient(55% 90% at 90% -10%,rgba(34,197,170,.34),transparent 62%),radial-gradient(40% 70% at 0% 110%,rgba(240,185,90,.17),transparent 62%),linear-gradient(145deg,#04121b,#0a2a3a 58%,#06383e);color:#fff;padding:20px 0 72px}
.nav{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:48px}
.brand{display:flex;align-items:center;gap:10px;font-weight:900;font-size:1.15rem}.brand img{width:42px;height:42px;object-fit:contain;background:#fff;border-radius:13px;padding:5px}
.back{color:#9cebd9;font-weight:800;font-size:.9rem}.crumbs{display:flex;flex-wrap:wrap;gap:8px;color:#b9d4d7;font-size:.84rem;margin-bottom:20px}.crumbs a{color:#8cebd0}
.eyebrow{display:inline-block;padding:5px 13px;border:1px solid #8cebd044;border-radius:99px;background:#ffffff0b;color:#9cebd9;font-size:.84rem;font-weight:800}
h1{max-width:900px;font-size:clamp(2rem,5vw,3.45rem);line-height:1.5;letter-spacing:-.7px;margin:15px 0;font-weight:950}
.hero-copy{max-width:850px;color:#c5dcdf;font-size:1.06rem;margin:0 0 25px}.actions{display:flex;flex-wrap:wrap;gap:11px}
.btn{display:inline-flex;align-items:center;justify-content:center;border-radius:13px;padding:11px 19px;font-weight:850}.primary{background:linear-gradient(135deg,#22c5aa,#087f78);color:#fff;box-shadow:0 12px 28px #13a89533}.secondary{border:1px solid #ffffff45;color:#fff}
.section{padding:58px 0}.section:nth-child(even){background:#eaf5f1}.section h2{font-size:clamp(1.45rem,3vw,2.15rem);line-height:1.6;margin:0 0 12px}.lead{color:var(--muted);max-width:880px;margin:0 0 24px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,245px),1fr));gap:16px}
.card{background:#fff;border:1px solid var(--line);border-radius:20px;padding:23px;box-shadow:0 12px 32px #0b6b5c08}.card h3{font-size:1.04rem;margin:0 0 8px}.card p{font-size:.93rem;color:var(--muted);margin:0}
.faq-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.faq-list details{background:#fff;border:1px solid var(--line);border-radius:17px;padding:17px 19px}.faq-list summary{cursor:pointer;font-weight:850}.faq-list details p{color:var(--muted);margin:10px 0 0;font-size:.94rem}
.related{display:flex;flex-wrap:wrap;gap:11px;margin-top:22px}.related a{padding:9px 14px;border:1px solid #cde9df;border-radius:12px;background:#fff;color:var(--deep);font-weight:800;font-size:.9rem}
.cta{margin-top:30px;padding:26px;border-radius:22px;background:linear-gradient(120deg,#071c28,#0c4a4b);color:#fff;display:flex;justify-content:space-between;align-items:center;gap:18px;flex-wrap:wrap}.cta p{color:#c1d9d9;margin:5px 0 0;font-size:.92rem}.foot{background:#061722;color:#a9c3c7;padding:25px 0;text-align:center;font-size:.88rem}.foot a{color:#8cebd0;text-decoration:underline}
@media(max-width:700px){.top{padding-bottom:50px}.nav{margin-bottom:34px}.section{padding:42px 0}.faq-list{grid-template-columns:1fr}.cta{padding:21px}.brand{font-size:1rem}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
</style>
</head>
<body>
<header class="top"><div class="wrap">
<nav class="nav" aria-label="ناوبری اصلی">
<a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/sabkhoneh-mark.png') }}" alt=""><span>صابخونه</span></a>
<a class="back" href="{{ route('home') }}">← بازگشت به صفحهٔ اصلی</a>
</nav>
<nav class="crumbs" aria-label="مسیر صفحه"><a href="{{ route('home') }}">خانه</a><span aria-hidden="true">/</span><span>{{ $page['heading'] }}</span></nav>
<span class="eyebrow">{{ $page['eyebrow'] }}</span>
<h1>{{ $page['heading'] }}</h1>
<p class="hero-copy">{{ $page['intro'] }}</p>
<div class="actions"><a class="btn primary" href="{{ route('login') }}">ورود و شروع استفاده ←</a><a class="btn secondary" href="#details">مطالعهٔ جزئیات</a></div>
</div></header>
<main>
@foreach($page['sections'] as $section)
<section class="section" id="{{ $loop->first ? 'details' : 'details-'.$loop->iteration }}"><div class="wrap">
<h2>{{ $section['heading'] }}</h2><p class="lead">{{ $section['text'] }}</p>
<div class="cards">@foreach($section['cards'] as $card)<article class="card"><h3>{{ $card['title'] }}</h3><p>{{ $card['text'] }}</p></article>@endforeach</div>
</div></section>
@endforeach
<section class="section"><div class="wrap">
<h2>پرسش‌های متداول</h2><div class="faq-list">
@foreach($page['faqs'] as $faq)<details><summary>{{ $faq['q'] }}</summary><p>{{ $faq['a'] }}</p></details>@endforeach
</div>
<h2 style="margin-top:34px">راهنماهای مرتبط</h2>
<nav class="related" aria-label="پیوندهای مرتبط">@foreach($relatedLinks as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }} ←</a>@endforeach</nav>
<div class="cta"><div><strong>مدیریت ساختمان را منظم‌تر شروع کنید</strong><p>نیازهای مجتمع خود را بررسی کنید و امکانات صابخونه را با آن‌ها بسنجید.</p></div><a class="btn primary" href="{{ route('login') }}">ورود به صابخونه ←</a></div>
</div></section>
</main>
<footer class="foot"><div class="wrap">© {{ date('Y') }} صابخونه · <a href="{{ route('marketing.articles.index') }}">مقالات و راهنماها</a> · <a href="{{ route('marketing.seo.trust') }}">مرکز اعتماد و امنیت</a> · <a href="{{ route('home') }}">صفحهٔ اصلی</a></div></footer>
</body>
</html>
