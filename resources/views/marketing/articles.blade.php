<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png"><link rel="icon" type="image/png" sizes="192x192" href="/pwa/icon-192.png"><link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0b2434">@php
$seoBase = rtrim((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), '/');
$seoScheme = strtolower((string) parse_url($seoBase, PHP_URL_SCHEME));
$seoHost = strtolower((string) parse_url($seoBase, PHP_URL_HOST));
$seoPublic = filter_var($seoBase, FILTER_VALIDATE_URL) && $seoScheme === 'https' && !in_array($seoHost, ['localhost', '127.0.0.1', '::1'], true) && empty(parse_url($seoBase, PHP_URL_USER)) && empty(parse_url($seoBase, PHP_URL_PASS)) && in_array((string) parse_url($seoBase, PHP_URL_PATH), ['', '/'], true) && parse_url($seoBase, PHP_URL_QUERY) === null && parse_url($seoBase, PHP_URL_FRAGMENT) === null;
$seoIndexable = $seoPublic && app()->environment('production') && (bool) \App\Models\SiteSetting::get('seo.indexing_enabled', false);
$seoTitle = 'مقالات و راهنماها | صابخونه';
$seoDescription = 'مقالات و راهنماهای صابخونه دربارهٔ مدیریت ساختمان، شارژ و پرداخت‌ها، رسیدگی به درخواست‌های ساکنان و بهبود ارتباط میان مدیران و ساکنان.';
$seoCanonical = $seoBase.'/articles'.($articles->currentPage() > 1 ? '?page='.$articles->currentPage() : '');
$seoCollectionItems = $articles->getCollection()->values()->map(function ($article, $index) use ($articles, $seoBase) {
    return [
        '@type' => 'ListItem',
        'position' => (($articles->currentPage() - 1) * $articles->perPage()) + $index + 1,
        'url' => rtrim($seoBase, '/').'/articles/'.rawurlencode($article->slug),
        'name' => $article->title,
    ];
})->all();
$seoCollectionSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $seoTitle,
    'description' => $seoDescription,
    'url' => $seoCanonical,
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => 'صابخونه',
        'url' => rtrim($seoBase, '/').'/',
    ],
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'numberOfItems' => count($seoCollectionItems),
        'itemListElement' => $seoCollectionItems,
    ],
];
@endphp
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoIndexable ? 'index,follow' : 'noindex,nofollow' }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="صابخونه">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}"><meta property="og:image" content="{{ $seoBase }}/images/og-sabkhoneh.png"><meta property="og:locale" content="fa_IR"><meta name="twitter:image" content="{{ $seoBase }}/images/og-sabkhoneh.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
@if($seoPublic && app()->environment('production'))<link rel="canonical" href="{{ $seoCanonical }}">
<script type="application/ld+json">{!! json_encode($seoCollectionSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>@endif
<title>{{ $seoTitle }}</title><link rel="stylesheet" href="{{ asset('css/fonts.css') }}"><style>
body{margin:0;background:#f6faf8;color:#102b3a;font-family:Vazirmatn,Tahoma,sans-serif;line-height:1.9}.wrap{width:min(1120px,calc(100% - 32px));margin:auto}a{color:inherit;text-decoration:none}.top{background:linear-gradient(125deg,#091f2d,#105b58);color:#fff;padding:38px 0}.top a{color:#9df0d8}.top h1{font-size:clamp(2rem,5vw,3rem);margin:10px 0}.top p{color:#c0d7da;margin:0}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;padding:35px 0}.card{background:#fff;border:1px solid #e1eae8;border-radius:20px;overflow:hidden;transition:.2s}.card:hover{transform:translateY(-4px);box-shadow:0 18px 40px #163f3410}.card img{width:100%;height:190px;object-fit:cover}.inner{padding:21px}.inner h2{font-size:1.12rem;line-height:1.8;margin:0 0 9px}.inner p{font-size:.9rem;color:#627582}.more{font-weight:850;color:#087f78}.pagination{padding:0 0 40px}.pagination nav{display:flex;justify-content:center;gap:8px}.pagination a,.pagination span{display:inline-block;padding:7px 12px;border-radius:9px;background:#fff;border:1px solid #e1eae8}@media(max-width:800px){.grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.grid{grid-template-columns:1fr}.card img{height:210px}}
</style><style>
body{background:#f3faf8}
.top{position:relative;overflow:hidden;padding:26px 0 64px;background:radial-gradient(55% 90% at 90% -10%,rgba(34,197,170,.4),transparent 62%),radial-gradient(40% 70% at 0% 110%,rgba(240,185,90,.2),transparent 62%),linear-gradient(160deg,#04121b,#0a2a3a 58%,#06383e)}
.top:after{content:"";position:absolute;inset:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.06) 1px,transparent 1px);background-size:52px 52px;-webkit-mask-image:radial-gradient(70% 90% at 50% 30%,#000,transparent);mask-image:radial-gradient(70% 90% at 50% 30%,#000,transparent)}
.top .wrap{position:relative;z-index:1}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:44px}
.tb{display:flex;align-items:center;gap:10px;color:#fff!important;font-size:1.2rem}.tm{width:42px;height:42px;border-radius:14px;background:#fff;padding:5px;display:grid;place-items:center}.tm img{display:block;width:100%;height:100%;object-fit:contain}
.tback{color:#8ff0d8!important;font-weight:700;font-size:.9rem}
.top h1{color:#fff;font-weight:900;margin:0 0 8px}.top h1:after{content:"";display:block;width:64px;height:4px;margin-top:14px;border-radius:99px;background:linear-gradient(90deg,#8bf0d3,#22c5aa,#f0b95a)}
.top p{color:#bcd5d9}
.grid{margin-top:-34px;position:relative;z-index:2;padding-top:0}
.card{border-radius:24px;border-color:#dcebe6;box-shadow:0 14px 36px #0b6b5c12;transition:transform .35s cubic-bezier(.2,.8,.2,1),box-shadow .35s,border-color .35s}
.card:hover{transform:translateY(-8px);border-color:#13a89560;box-shadow:0 30px 60px #0b6b5c24}
.card img{transition:transform .6s}.card:hover img{transform:scale(1.06)}.card>a{display:block;overflow:hidden}
.inner h2 a{color:#102b3a}.more{display:inline-flex;align-items:center;gap:6px;color:#087f78}
.sfoot{background:#06232b;color:#a9c9c5;padding:22px 0;font-size:.85rem;text-align:center}
@media(prefers-reduced-motion:reduce){.card,.card img{transition:none}.card:hover{transform:none}}
</style></head><body><header class="top"><div class="wrap"><div class="topbar"><a class="tb" href="{{ route('home') }}"><span class="tm"><img src="{{ asset('images/sabkhoneh-mark.png') }}" alt="" width="40" height="40"></span><b>صابخونه</b></a><a class="tback" href="{{ route('home') }}">← بازگشت به صفحهٔ اصلی</a></div><h1>مقالات و راهنماها</h1><p>مطالبی برای مدیریت بهتر ساختمان و همکاری شفاف‌تر.</p></div></header><main class="wrap"><div class="grid">@forelse($articles as $article)<article class="card">@if($article->cover_image)<a href="{{ route('marketing.articles.show',$article->slug) }}"><img src="{{ preg_match('~^https?://|^/~i', $article->cover_image) ? $article->cover_image : \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}" alt="{{ $article->title }}" loading="lazy" decoding="async"></a>@endif<div class="inner"><h2><a href="{{ route('marketing.articles.show',$article->slug) }}">{{ $article->title }}</a></h2><p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body),150) }}</p><a class="more" href="{{ route('marketing.articles.show',$article->slug) }}">ادامهٔ مطلب ←</a></div></article>@empty<p>هنوز مقاله‌ای منتشر نشده است.</p>@endforelse</div><div class="pagination">{{ $articles->links() }}</div></main><footer class="sfoot"><div class="wrap">© {{ date('Y') }} صابخونه — مدیریت ساختمان، با آرامش بیشتر</div></footer></body></html>
