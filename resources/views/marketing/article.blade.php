<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png"><link rel="icon" type="image/png" sizes="192x192" href="/pwa/icon-192.png"><link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png"><meta name="viewport" content="width=device-width,initial-scale=1">@php
$seoBase = rtrim((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), '/');
$seoScheme = strtolower((string) parse_url($seoBase, PHP_URL_SCHEME));
$seoHost = strtolower((string) parse_url($seoBase, PHP_URL_HOST));
$seoPublic = filter_var($seoBase, FILTER_VALIDATE_URL) && $seoScheme === 'https' && !in_array($seoHost, ['localhost', '127.0.0.1', '::1'], true) && empty(parse_url($seoBase, PHP_URL_USER)) && empty(parse_url($seoBase, PHP_URL_PASS)) && in_array((string) parse_url($seoBase, PHP_URL_PATH), ['', '/'], true) && parse_url($seoBase, PHP_URL_QUERY) === null && parse_url($seoBase, PHP_URL_FRAGMENT) === null;
$seoIndexable = $seoPublic && app()->environment('production') && (bool) \App\Models\SiteSetting::get('seo.indexing_enabled', false);
$seoDescription = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($article->excerpt ?: $article->body))), 155, '');
$seoTitle = $article->title.' | صابخونه';
$seoImage = (null) ?: $seoBase.'/images/og-sabkhoneh.png';
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
$seoAuthorName = trim((string) ($article->author_name ?: 'تیم صابخونه'));
$seoArticleSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article->title,
    'description' => $seoDescription,
    'mainEntityOfPage' => $seoBase.'/articles/'.rawurlencode($article->slug),
    'author' => $seoAuthorName === 'تیم صابخونه'
        ? ['@type' => 'Organization', 'name' => $seoAuthorName, 'url' => $seoBase.'/']
        : ['@type' => 'Person', 'name' => $seoAuthorName],
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
<meta name="twitter:card" content="summary_large_image">
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
</style><style>
body{background:#f3faf8}
.rp{position:fixed;top:0;right:0;left:0;height:3px;z-index:60;transform-origin:right;transform:scaleX(0);background:linear-gradient(90deg,#22c5aa,#f0b95a)}
.header{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.8);backdrop-filter:blur(18px) saturate(1.5)}
.back{background:#e4f7f0;padding:6px 14px;border-radius:99px;font-size:.88rem}
.article{border-radius:28px;border-color:#dcebe6;box-shadow:0 30px 70px #0b6b5c14}
.cover{max-height:460px}
.body h1{font-weight:900;color:#102b3a}
.tag{background:linear-gradient(135deg,#dff5ef,#fff4dc);border:1px solid #c6ebe0}
.meta{display:flex;flex-wrap:wrap;gap:8px 18px;font-size:.84rem;color:#6b7f87;margin-bottom:18px}
.excerpt{border-right-color:#22c5aa;background:#f0faf6;border-radius:12px;padding:14px 18px}
.content{font-size:1.06rem}
.cta{margin:34px clamp(22px,6vw,58px) clamp(24px,5vw,50px);padding:26px 28px;border-radius:22px;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;color:#fff;background:radial-gradient(60% 140% at 0 0,rgba(34,197,170,.4),transparent 62%),linear-gradient(135deg,#04121b,#0a4a4e)}
.cta strong{display:block;font-size:1.1rem}.cta span{color:#c9e6e3;font-size:.9rem}
.cta-btn{background:linear-gradient(135deg,#25cdb1,#087f78);color:#fff;font-weight:800;padding:11px 20px;border-radius:14px;box-shadow:0 12px 26px rgba(19,168,149,.4);transition:transform .2s}.cta-btn:hover{transform:translateY(-2px)}
.related{margin:0 clamp(22px,6vw,58px) 34px;padding:24px;border:1px solid #dcebe6;border-radius:22px;background:#f8fcfa}
.related h2{margin:0 0 16px;font-size:1.2rem;color:#102b3a}
.related-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.related-card{display:flex;flex-direction:column;align-items:flex-start;gap:8px;padding:16px;border:1px solid #dcebe6;border-radius:16px;background:#fff;transition:border-color .2s,transform .2s}
.related-card:hover{border-color:#22b99f;transform:translateY(-2px)}
.related-card strong{color:#087f78;line-height:1.8}
.related-card span{font-size:.88rem;color:#627582;line-height:1.9}
.related-card .related-more{font-weight:800;color:#087f78;margin-top:auto}
.related-index{display:inline-block;margin-top:16px;color:#087f78;font-weight:800;text-decoration:underline}
@media(max-width:600px){.related-grid{grid-template-columns:1fr}.related{padding:18px}}
.footer{background:#06232b;color:#a9c9c5;border:0;text-align:center;width:100%;padding:22px 16px}
@media(prefers-reduced-motion:reduce){.rp{display:none}}
</style></head><body><div class="rp" id="rp" aria-hidden="true"></div><header class="header"><div class="wrap nav"><a class="brand" href="{{ route('home') }}"><span class="mark" style="background:#fff;padding:5px;border:1px solid #e1eae8"><img src="{{ asset('images/sabkhoneh-mark.png') }}" alt="" style="display:block;width:100%;height:100%;object-fit:contain"></span><span>صابخونه</span></a><a class="back" href="{{ route('home') }}#articles">بازگشت به صفحهٔ اصلی ←</a></div></header><main class="wrap"><article class="article">@if($article->cover_image)<img class="cover" src="{{ preg_match('~^https?://|^/~i', $article->cover_image) ? $article->cover_image : \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}" alt="{{ $article->title }}">@endif<div class="body"><span class="tag">دانش‌نامهٔ صابخونه</span><h1>{{ $article->title }}</h1>@php $rt = max(1, (int) ceil(count(preg_split('/\s+/u', trim(strip_tags($article->body)), -1, PREG_SPLIT_NO_EMPTY)) / 180)); @endphp<div class="meta"><span>نویسنده: {{ $seoAuthorName }}</span>@if($article->published_at)<span>{{ $article->published_at->format('Y/m/d') }}</span>@endif<span>زمان مطالعه: {{ $rt }} دقیقه</span></div> @if($article->excerpt)<p class="excerpt">{{ $article->excerpt }}</p>@endif<div class="content">{!! \App\Support\ArticleHtmlSanitizer::sanitize($article->body) !!}</div></div><aside class="related" aria-label="خدمات مرتبط"><h2>مدیریت شارژ و هزینه‌های ساختمان</h2><p style="color:#627582;margin:0 0 12px">برای سامان‌دهی اطلاعات مالی و پیگیری امور ساختمان، با امکانات صابخونه آشنا شوید.</p><a class="related-index" href="{{ route('marketing.building-charges') }}">آشنایی با نرم‌افزار شارژ ساختمان ←</a><br><a class="related-index" href="{{ route('marketing.building-management') }}">آشنایی با نرم‌افزار مدیریت ساختمان ←</a></aside>@if($relatedArticles->isNotEmpty())<section class="related" aria-labelledby="related-heading"><h2 id="related-heading">مطالب مرتبط</h2><div class="related-grid">@foreach($relatedArticles as $relatedArticle)<a class="related-card" href="{{ route('marketing.articles.show', $relatedArticle->slug) }}"><strong>{{ $relatedArticle->title }}</strong><span>{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) ($relatedArticle->excerpt ?: $relatedArticle->body))), 135, '…') }}</span><span class="related-more">مطالعهٔ مقاله ←</span></a>@endforeach</div><a class="related-index" href="{{ route('marketing.articles.index') }}">مشاهدهٔ همهٔ مقالات ←</a></section>@endif<aside class="cta"><div><strong>مدیریت ساختمان را ساده‌تر کنید</strong><span>شارژ، پرداخت، درخواست‌ها و اطلاع‌رسانی را در صابخونه یک‌جا پیگیری کنید.</span></div><a class="cta-btn" href="{{ route('login') }}">ورود به صابخونه ←</a></aside></article></main><footer class="wrap footer">© {{ date('Y') }} صابخونه — مدیریت ساختمان، با آرامش بیشتر</footer><script>(function(){var r=document.getElementById("rp");function u(){var h=document.documentElement.scrollHeight-innerHeight;r.style.transform="scaleX("+(h>0?Math.min(scrollY/h,1):0)+")"}addEventListener("scroll",u,{passive:true});u()})();</script></body></html>
