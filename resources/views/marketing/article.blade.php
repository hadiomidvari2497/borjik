<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png"><link rel="icon" type="image/png" sizes="192x192" href="/pwa/icon-192.png"><link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png"><meta name="viewport" content="width=device-width,initial-scale=1">@php
$seoBase = rtrim((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), '/');
$seoScheme = strtolower((string) parse_url($seoBase, PHP_URL_SCHEME));
$seoHost = strtolower((string) parse_url($seoBase, PHP_URL_HOST));
$seoPublic = filter_var($seoBase, FILTER_VALIDATE_URL) && $seoScheme === 'https' && !in_array($seoHost, ['localhost', '127.0.0.1', '::1'], true) && empty(parse_url($seoBase, PHP_URL_USER)) && empty(parse_url($seoBase, PHP_URL_PASS)) && in_array((string) parse_url($seoBase, PHP_URL_PATH), ['', '/'], true) && parse_url($seoBase, PHP_URL_QUERY) === null && parse_url($seoBase, PHP_URL_FRAGMENT) === null;
$seoIndexable = $seoPublic && app()->environment('production') && (bool) \App\Models\SiteSetting::get('seo.indexing_enabled', false);
$seoDescription = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($article->excerpt ?: $article->body))), 155, '');
$seoTitle = $article->title.' | صابخونه';
$seoImage = null;
if ($article->cover_image) {
    $cover = $article->cover_image;
    if (preg_match('~^https?://~i', $cover)) {
        $seoImage = $cover;
    } elseif (str_starts_with($cover, '/')) {
        $seoImage = $seoBase.$cover;
    } else {
        $storageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($cover);
        $seoImage = preg_match('~^https?://~i', $storageUrl)
            ? $storageUrl
            : $seoBase.'/'.ltrim($storageUrl, '/');
    }
}
$seoArticleSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article->title,
    'description' => $seoDescription,
    'mainEntityOfPage' => $seoBase.'/articles/'.rawurlencode($article->slug),
    'publisher' => ['@type' => 'Organization', 'name' => 'صابخونه', 'url' => $seoBase.'/'],
];
if ($article->published_at) $seoArticleSchema['datePublished'] = $article->published_at->toAtomString();
if ($article->updated_at) $seoArticleSchema['dateModified'] = $article->updated_at->toAtomString();
if ($seoImage) $seoArticleSchema['image'] = [$seoImage];
@endphp
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoIndexable ? 'index,follow' : 'noindex,nofollow' }}">
<meta property="og:type" content="article">
<meta property="og:site_name" content="صابخونه">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoBase }}/articles/{{ rawurlencode($article->slug) }}">
@if($seoImage)<meta property="og:image" content="{{ $seoImage }}">@endif
@if($article->published_at)<meta property="article:published_time" content="{{ $article->published_at->toAtomString() }}">@endif
<meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
@if($seoImage)<meta name="twitter:image" content="{{ $seoImage }}">@endif
@if($seoPublic && app()->environment('production'))<link rel="canonical" href="{{ $seoBase }}/articles/{{ rawurlencode($article->slug) }}">@endif
@if($seoPublic && app()->environment('production'))
<script type="application/ld+json">@php echo json_encode($seoArticleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); @endphp</script>
@endif
<meta name="theme-color" content="#0b2434">
<title>{{ $seoTitle }}</title><link rel="stylesheet" href="{{ asset('css/fonts.css') }}"><style>
:root{--ink:#102b3a;--muted:#627582;--teal:#13a895;--line:#e1eae8}*{box-sizing:border-box}body{margin:0;background:#f6faf8;color:var(--ink);font-family:Vazirmatn,Tahoma,sans-serif;line-height:2}.wrap{width:min(900px,calc(100% - 32px));margin:auto}a{color:inherit;text-decoration:none}.header{background:#fff;border-bottom:1px solid var(--line)}.nav{height:76px;display:flex;align-items:center;justify-content:space-between}.brand{display:flex;align-items:center;gap:10px;font-weight:900;font-size:1.2rem}.mark{width:42px;height:42px;border-radius:14px;background:linear-gradient(145deg,#22c5aa,#087f78);color:#fff;display:grid;place-items:center}.back{color:#087f78;font-weight:800}.article{margin:38px auto 70px;background:#fff;border:1px solid var(--line);border-radius:26px;overflow:hidden;box-shadow:0 24px 60px rgba(15,60,52,.06)}.cover{width:100%;max-height:420px;object-fit:cover;display:block}.body{padding:clamp(24px,6vw,58px)}.tag{display:inline-block;color:#087f78;background:#e4f7f0;border-radius:99px;padding:5px 12px;font-size:.8rem;font-weight:800}.body h1{font-size:clamp(1.8rem,4vw,3rem);line-height:1.5;margin:15px 0}.excerpt{font-size:1.08rem;color:#536c75;border-right:4px solid #19b99b;padding-right:16px;margin-bottom:28px}.content{font-size:1.02rem;color:#314c58;overflow-wrap:anywhere}.content h2,.content h3,.content h4{line-height:1.6;margin:1.6em 0 .6em;color:#102b3a}.content p{margin:0 0 1.1em}.content ul,.content ol{padding-right:28px;margin:0 0 1.2em}.content blockquote{border-right:4px solid #19b99b;background:#f0faf6;border-radius:10px;padding:12px 18px;margin:1.3em 0}.content a{color:#087f78;text-decoration:underline}.content pre{direction:ltr;text-align:left;overflow:auto;background:#f1f5f4;padding:14px;border-radius:12px}.content table{width:100%;border-collapse:collapse;margin:1.2em 0}.content th,.content td{border:1px solid #dce8e4;padding:8px;text-align:right}.footer{padding:22px 0;color:var(--muted);font-size:.85rem;border-top:1px solid var(--line)}@media(max-width:600px){.nav{height:66px}.article{margin-top:20px;border-radius:18px}.body{padding:22px}.excerpt{font-size:1rem}}
</style></head><body><header class="header"><div class="wrap nav"><a class="brand" href="{{ route('home') }}"><span class="mark" style="background:#fff;padding:5px;border:1px solid #e1eae8"><img src="{{ asset('images/sabkhoneh-mark.png') }}" alt="" style="display:block;width:100%;height:100%;object-fit:contain"></span><span>صابخونه</span></a><a class="back" href="{{ route('home') }}#articles">بازگشت به صفحهٔ اصلی ←</a></div></header><main class="wrap"><article class="article">@if($article->cover_image)<img class="cover" src="{{ preg_match('~^https?://|^/~i', $article->cover_image) ? $article->cover_image : \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}" alt="{{ $article->title }}">@endif<div class="body"><span class="tag">دانش‌نامهٔ صابخونه</span><h1>{{ $article->title }}</h1>@if($article->published_at)<div style="font-size:.82rem;color:#84949a;margin-bottom:18px">{{ $article->published_at->format('Y/m/d') }}</div>@endif @if($article->excerpt)<p class="excerpt">{{ $article->excerpt }}</p>@endif<div class="content">{!! \App\Support\ArticleHtmlSanitizer::sanitize($article->body) !!}</div></div></article></main><footer class="wrap footer">© {{ date('Y') }} صابخونه — مدیریت ساختمان، با آرامش بیشتر</footer></body></html>
