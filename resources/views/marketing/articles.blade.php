<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0b2434">@php
$seoBase = rtrim((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), '/');
$seoScheme = strtolower((string) parse_url($seoBase, PHP_URL_SCHEME));
$seoHost = strtolower((string) parse_url($seoBase, PHP_URL_HOST));
$seoPublic = filter_var($seoBase, FILTER_VALIDATE_URL) && in_array($seoScheme, ['http', 'https'], true) && !in_array($seoHost, ['localhost', '127.0.0.1', '::1'], true);
$seoIndexable = $seoPublic && app()->environment('production') && (bool) \App\Models\SiteSetting::get('seo.indexing_enabled', false);
$seoTitle = 'مقالات و راهنماها | برجیک';
$seoDescription = (string) \App\Models\SiteSetting::get('seo.description', 'مقالات و راهنماهای برجیک برای مدیریت بهتر ساختمان.');
$seoCanonical = $seoBase.'/articles'.($articles->currentPage() > 1 ? '?page='.$articles->currentPage() : '');
@endphp
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoIndexable ? 'index,follow' : 'noindex,nofollow' }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="برجیک">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
@if($seoPublic && app()->environment('production'))<link rel="canonical" href="{{ $seoCanonical }}">@endif
<title>{{ $seoTitle }}</title><link rel="stylesheet" href="{{ asset('css/fonts.css') }}"><style>
body{margin:0;background:#f6faf8;color:#102b3a;font-family:Vazirmatn,Tahoma,sans-serif;line-height:1.9}.wrap{width:min(1120px,calc(100% - 32px));margin:auto}a{color:inherit;text-decoration:none}.top{background:linear-gradient(125deg,#091f2d,#105b58);color:#fff;padding:38px 0}.top a{color:#9df0d8}.top h1{font-size:clamp(2rem,5vw,3rem);margin:10px 0}.top p{color:#c0d7da;margin:0}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;padding:35px 0}.card{background:#fff;border:1px solid #e1eae8;border-radius:20px;overflow:hidden;transition:.2s}.card:hover{transform:translateY(-4px);box-shadow:0 18px 40px #163f3410}.card img{width:100%;height:190px;object-fit:cover}.inner{padding:21px}.inner h2{font-size:1.12rem;line-height:1.8;margin:0 0 9px}.inner p{font-size:.9rem;color:#627582}.more{font-weight:850;color:#087f78}.pagination{padding:0 0 40px}.pagination nav{display:flex;justify-content:center;gap:8px}.pagination a,.pagination span{display:inline-block;padding:7px 12px;border-radius:9px;background:#fff;border:1px solid #e1eae8}@media(max-width:800px){.grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.grid{grid-template-columns:1fr}.card img{height:210px}}
</style></head><body><header class="top"><div class="wrap"><a href="{{ route('home') }}">← بازگشت به برجیک</a><h1>مقالات و راهنماها</h1><p>مطالبی برای مدیریت بهتر ساختمان و همکاری شفاف‌تر.</p></div></header><main class="wrap"><div class="grid">@forelse($articles as $article)<article class="card">@if($article->cover_image)<a href="{{ route('marketing.articles.show',$article->slug) }}"><img src="{{ preg_match('~^https?://|^/~i', $article->cover_image) ? $article->cover_image : \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}" alt="{{ $article->title }}" loading="lazy" decoding="async"></a>@endif<div class="inner"><h2><a href="{{ route('marketing.articles.show',$article->slug) }}">{{ $article->title }}</a></h2><p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body),150) }}</p><a class="more" href="{{ route('marketing.articles.show',$article->slug) }}">ادامهٔ مطلب ←</a></div></article>@empty<p>هنوز مقاله‌ای منتشر نشده است.</p>@endforelse</div><div class="pagination">{{ $articles->links() }}</div></main></body></html>
