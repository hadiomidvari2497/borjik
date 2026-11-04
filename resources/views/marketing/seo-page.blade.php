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
.comparison-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:18px;background:#fff;box-shadow:0 12px 32px #0b6b5c08}.comparison-table{width:100%;min-width:850px;border-collapse:collapse;text-align:right;line-height:1.9}.comparison-table caption{text-align:right;padding:16px 18px;color:var(--muted);font-size:.9rem}.comparison-table th,.comparison-table td{padding:14px 15px;border-bottom:1px solid var(--line);vertical-align:top}.comparison-table thead th{background:#eaf5f1;color:var(--ink);font-weight:900}.comparison-table tbody th{min-width:145px;background:#f8fcfa;font-weight:850}.comparison-table td{font-size:.9rem;color:var(--muted)}.comparison-note{font-size:.85rem;color:var(--muted);margin:12px 2px 0}
.related{display:flex;flex-wrap:wrap;gap:11px;margin-top:22px}.related a{padding:9px 14px;border:1px solid #cde9df;border-radius:12px;background:#fff;color:var(--deep);font-weight:800;font-size:.9rem}
.cta{margin-top:30px;padding:26px;border-radius:22px;background:linear-gradient(120deg,#071c28,#0c4a4b);color:#fff;display:flex;justify-content:space-between;align-items:center;gap:18px;flex-wrap:wrap}.cta p{color:#c1d9d9;margin:5px 0 0;font-size:.92rem}.foot{background:#061722;color:#a9c3c7;padding:25px 0;text-align:center;font-size:.88rem}.foot a{color:#8cebd0;text-decoration:underline}
@media(max-width:700px){.top{padding-bottom:50px}.nav{margin-bottom:34px}.section{padding:42px 0}.cta{padding:21px}.brand{font-size:1rem}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
</style>
@include('marketing.partials.faq-styles')
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
@if(!empty($page['comparison_table']))
<section class="section" id="comparison"><div class="wrap">
<h2>جدول مقایسهٔ روش‌ها و امکانات</h2>
<p class="lead">این جدول تفاوت رویکردها را نشان می‌دهد، نه رتبه‌بندی قطعی محصولات بازار. امکانات هر ارائه‌دهنده را در نسخه و پلنی که قصد خرید آن را دارید، جداگانه بررسی کنید.</p>
<div class="comparison-wrap" role="region" aria-label="جدول مقایسه روش‌های مدیریت ساختمان" tabindex="0">
<table class="comparison-table">
<caption>{{ $page['comparison_table']['caption'] }}</caption>
<thead><tr>@foreach($page['comparison_table']['headers'] as $header)<th scope="col">{{ $header }}</th>@endforeach</tr></thead>
<tbody>@foreach($page['comparison_table']['rows'] as $row)
<tr><th scope="row">{{ $row['معیار'] }}</th><td>{{ $row['اکسل و فایل'] }}</td><td>{{ $row['گروه پیام‌رسان'] }}</td><td>{{ $row['سامانهٔ تخصصی'] }}</td><td>{{ $row['صابخونه'] }}</td></tr>
@endforeach</tbody>
</table>
</div>
<p class="comparison-note">نکته: وجود یک قابلیت در صفحهٔ معرفی محصول، به‌تنهایی کیفیت اجرا، دسترس‌پذیری در همهٔ پلن‌ها یا تناسب آن با نیاز ساختمان شما را اثبات نمی‌کند.</p>
</div></section>
@endif
@if(!empty($page['market_examples']))
<section class="section"><div class="wrap">
<h2>نمونه‌هایی از گزینه‌های بازار ایران</h2>
<p class="lead">{{ $page['market_examples']['intro'] }}</p>
<div class="cards">
@foreach($page['market_examples']['cards'] as $example)
<article class="card"><h3>{{ $example['name'] }}</h3><p>{{ $example['text'] }}</p><p style="margin-top:14px"><a href="{{ $example['url'] }}" target="_blank" rel="noopener noreferrer" style="color:var(--deep);font-weight:850;text-decoration:underline">{{ $example['source_label'] }} ↗</a></p></article>
@endforeach
</div>
</div></section>
@endif
<section class="section"><div class="wrap">
<h2>پرسش‌های متداول</h2>@include('marketing.partials.faq-list', ['items' => $page['faqs'], 'columns' => 2])
<h2 style="margin-top:34px">راهنماهای مرتبط</h2>
<nav class="related" aria-label="پیوندهای مرتبط">@foreach($relatedLinks as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }} ←</a>@endforeach</nav>
<div class="cta"><div><strong>مدیریت ساختمان را منظم‌تر شروع کنید</strong><p>نیازهای مجتمع خود را بررسی کنید و امکانات صابخونه را با آن‌ها بسنجید.</p></div><a class="btn primary" href="{{ route('login') }}">ورود به صابخونه ←</a></div>
</div></section>
</main>
<footer class="foot"><div class="wrap">© {{ date('Y') }} صابخونه · <a href="{{ route('marketing.articles.index') }}">مقالات و راهنماها</a> · <a href="{{ route('marketing.seo.trust') }}">مرکز اعتماد و امنیت</a> · <a href="{{ route('home') }}">صفحهٔ اصلی</a></div></footer>
@include('marketing.partials.faq-script')
</body>
</html>
