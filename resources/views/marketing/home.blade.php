<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png"><link rel="icon" type="image/png" sizes="192x192" href="/pwa/icon-192.png"><link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b2434">
    @php
        $seoBase = rtrim((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), '/');
        $seoScheme = strtolower((string) parse_url($seoBase, PHP_URL_SCHEME));
        $seoHost = strtolower((string) parse_url($seoBase, PHP_URL_HOST));
        $seoPublic = filter_var($seoBase, FILTER_VALIDATE_URL) && $seoScheme === 'https' && !in_array($seoHost, ['localhost', '127.0.0.1', '::1'], true) && empty(parse_url($seoBase, PHP_URL_USER)) && empty(parse_url($seoBase, PHP_URL_PASS)) && in_array((string) parse_url($seoBase, PHP_URL_PATH), ['', '/'], true) && parse_url($seoBase, PHP_URL_QUERY) === null && parse_url($seoBase, PHP_URL_FRAGMENT) === null;
        $seoIndexable = $seoPublic && app()->environment('production') && (bool) \App\Models\SiteSetting::get('seo.indexing_enabled', false);
        $seoTitle = (string) \App\Models\SiteSetting::get('seo.title', 'صابخونه | مدیریت ساختمان، با آرامش بیشتر');
        $seoDescription = (string) \App\Models\SiteSetting::get('seo.description', 'صابخونه؛ سامانه فارسی مدیریت ساختمان برای شارژ، پرداخت‌ها، درخواست‌ها، اطلاع‌رسانی و ارتباط بهتر میان مدیران و ساکنان.');
        $seoVerification = (string) \App\Models\SiteSetting::get('seo.google_verification', '');
    @endphp
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoIndexable ? 'index,follow' : 'noindex,nofollow' }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="صابخونه">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:image" content="{{ $seoBase.'/pwa/icon-512.png' }}">
    <meta property="og:url" content="{{ $seoBase }}/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $seoBase.'/pwa/icon-512.png' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    @if($seoPublic && app()->environment('production'))<link rel="canonical" href="{{ $seoBase }}/">@endif
    @if($seoVerification !== '')<meta name="google-site-verification" content="{{ $seoVerification }}">@endif
    @if($seoPublic && app()->environment('production'))
    <script type="application/ld+json">
        @php
            echo json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'صابخونه',
                'url' => $seoBase.'/',
                'logo' => $seoBase.'/pwa/icon-512.png',
                'description' => $seoDescription,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        @endphp
    </script>
    @endif
    <title>{{ $seoTitle }}</title>
    <link rel="preload" href="{{ asset('fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <style>
        :root{--ink:#102b3a;--muted:#627582;--teal:#13a895;--teal-dark:#087f78;--paper:#f7faf9;--line:#e1eae8;--white:#fff;--gold:#f0b95a}
        *{box-sizing:border-box}html{scroll-behavior:smooth;scroll-padding-top:92px}body{margin:0;background:var(--paper);color:var(--ink);font-family:Vazirmatn,Tahoma,sans-serif;line-height:1.9}a{color:inherit;text-decoration:none}.wrap{width:min(1160px,calc(100% - 40px));margin-inline:auto}
        .site-header{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.92);backdrop-filter:blur(16px);border-bottom:1px solid rgba(225,234,232,.8)}.nav{min-height:78px;display:flex;align-items:center;justify-content:space-between;gap:24px}.brand{display:flex;align-items:center;gap:11px;font-size:1.28rem;font-weight:900;letter-spacing:-.5px}.brand-mark{width:43px;height:43px;border-radius:15px;display:grid;place-items:center;background:linear-gradient(145deg,#22c5aa,#087f78);color:#fff;font-size:1.45rem;box-shadow:0 8px 18px #0b9b8730}.nav-links{display:flex;align-items:center;gap:23px;color:#4f6570;font-size:.9rem}.nav-links a:hover{color:var(--teal-dark)}.nav-actions{display:flex;align-items:center;gap:10px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:14px;padding:11px 18px;font-weight:800;font-size:.9rem;transition:transform .2s,box-shadow .2s,background .2s;border:1px solid transparent;cursor:pointer}.btn:hover{transform:translateY(-2px)}.btn-primary{background:var(--teal);color:#fff;box-shadow:0 10px 24px #13a89530}.btn-primary:hover{background:var(--teal-dark)}.btn-light{background:#fff;border-color:var(--line);color:var(--ink)}.btn-ghost{color:var(--ink);padding-inline:12px}
        .hero{position:relative;overflow:hidden;padding:86px 0 78px;background:radial-gradient(ellipse at 9% 12%,#21bca21c,transparent 36%),linear-gradient(130deg,#f7fbfa 0%,#eef8f5 52%,#f9fbfa 100%)}.hero:before{content:"";position:absolute;width:460px;height:460px;border-radius:50%;border:1px solid #13a89520;left:-160px;top:-190px;box-shadow:0 0 0 45px #13a89508,0 0 0 95px #13a89506}.hero-grid{position:relative;display:grid;grid-template-columns:1.02fr .98fr;align-items:center;gap:56px}.eyebrow{display:inline-flex;align-items:center;gap:9px;padding:6px 13px;border-radius:999px;background:#dff5ef;color:#087f78;font-size:.82rem;font-weight:800}.eyebrow i{width:7px;height:7px;border-radius:50%;background:var(--teal)}h1,h2,h3,p{margin-top:0}h1{font-size:clamp(2.25rem,4.1vw,4.1rem);line-height:1.42;letter-spacing:-1.5px;margin:19px 0 18px;font-weight:950}h1 span{color:var(--teal-dark);position:relative;white-space:nowrap}.hero-copy{font-size:1.08rem;color:#5c707a;max-width:580px;margin-bottom:28px}.hero-actions{display:flex;flex-wrap:wrap;gap:12px}.hero-proof{display:flex;align-items:center;gap:14px;margin-top:29px;color:#667b84;font-size:.84rem}.proof-avatars{display:flex;direction:ltr}.proof-avatars span{width:31px;height:31px;display:grid;place-items:center;border:2px solid #f2faf7;border-radius:50%;margin-left:-8px;background:#d4f1e9;color:#087f78;font-size:.78rem;font-weight:900}.hero-proof strong{color:var(--ink)}
        .dashboard-art{position:relative;min-height:425px;display:grid;place-items:center}.orb{position:absolute;width:330px;height:330px;border-radius:50%;background:linear-gradient(145deg,#c8f1e7,#e8f8f3);right:9%;top:6%;box-shadow:inset -20px -20px 55px #7bc9b51a}.mock-window{position:relative;width:min(440px,100%);background:#fff;border:1px solid #e0ece8;border-radius:24px;padding:22px;box-shadow:0 30px 70px #1b51451a;transform:rotate(-2deg)}.mock-top{display:flex;align-items:center;justify-content:space-between;padding-bottom:17px;border-bottom:1px solid #edf2f0}.mock-title{font-weight:900}.mock-sub{font-size:.75rem;color:#87969c}.live-dot{font-size:.7rem;color:#087f78;background:#e2f7f0;padding:4px 9px;border-radius:99px}.mock-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin:18px 0}.stat{padding:14px;border-radius:15px;background:#f6faf8;border:1px solid #edf3f0}.stat small{display:block;color:#788b91;font-size:.75rem}.stat strong{display:block;font-size:1.25rem;margin-top:5px}.stat em{font-size:.72rem;font-style:normal;color:#0d9a7f}.mock-chart{padding:15px 14px 8px;border:1px solid #edf2f0;border-radius:15px}.chart-head{display:flex;justify-content:space-between;font-size:.78rem;font-weight:800}.bars{height:90px;display:flex;align-items:end;gap:9px;padding:12px 8px 0}.bars i{flex:1;border-radius:7px 7px 2px 2px;background:linear-gradient(180deg,#36cbb1,#8fe0cd);min-height:18px}.bars i:nth-child(2n){background:linear-gradient(180deg,#80d8c6,#b8eee1)}.floating-note{position:absolute;left:0;bottom:47px;background:#fff;border:1px solid #e5efec;border-radius:17px;padding:13px 16px;display:flex;align-items:center;gap:11px;box-shadow:0 15px 38px #1b51451a;font-size:.82rem}.floating-icon{width:38px;height:38px;border-radius:12px;background:#fff1d9;color:#a56a0b;display:grid;place-items:center;font-size:1.15rem}.floating-note strong{display:block}.floating-note small{color:#829198}
        .trust-strip{background:#fff;border-block:1px solid var(--line)}.trust-items{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;padding:23px 0}.trust-item{display:flex;align-items:center;justify-content:center;gap:11px;color:#526973;font-size:.87rem;font-weight:750}.trust-item span{width:35px;height:35px;border-radius:12px;background:#eaf8f4;color:#0a9481;display:grid;place-items:center;font-size:1.1rem}
        section.content-section{padding:82px 0}.section-head{max-width:690px;margin:0 auto 38px;text-align:center}.section-kicker{color:var(--teal-dark);font-size:.82rem;font-weight:900;letter-spacing:.2px}.section-head h2{font-size:clamp(1.75rem,3vw,2.6rem);line-height:1.5;margin:9px 0 10px;font-weight:950;letter-spacing:-.7px}.section-head p{color:var(--muted);margin:0}.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.feature-card{background:#fff;border:1px solid var(--line);border-radius:22px;padding:26px;min-height:220px;transition:transform .22s,box-shadow .22s}.feature-card:hover{transform:translateY(-5px);box-shadow:0 20px 40px #173d3410}.feature-icon{width:51px;height:51px;border-radius:17px;display:grid;place-items:center;background:#e4f7f1;color:#078775;font-size:1.45rem;margin-bottom:17px}.feature-card:nth-child(3n+2) .feature-icon{background:#fff3df;color:#ad7517}.feature-card:nth-child(3n) .feature-icon{background:#e9edff;color:#5968bc}.feature-card h3{font-size:1.05rem;margin-bottom:8px}.feature-card p{font-size:.88rem;color:var(--muted);margin-bottom:0}
        .how{background:#0c2737;color:#fff;position:relative;overflow:hidden}.how:after{content:"";position:absolute;width:500px;height:500px;border-radius:50%;border:1px solid #ffffff0c;left:-170px;top:-220px;box-shadow:0 0 0 55px #ffffff05,0 0 0 110px #ffffff04}.how .section-head{position:relative;z-index:1}.how .section-head p{color:#b8c8ce}.how .section-kicker{color:#7de0cd}.steps{position:relative;z-index:1;display:grid;grid-template-columns:repeat(3,1fr);gap:22px}.step{padding:25px;border:1px solid #ffffff1c;background:#ffffff08;border-radius:22px}.step-number{display:grid;place-items:center;width:40px;height:40px;border-radius:13px;background:#1cae9a;color:#fff;font-weight:900;margin-bottom:17px}.step h3{font-size:1.05rem;margin-bottom:7px}.step p{color:#b8c8ce;font-size:.88rem;margin-bottom:0}
        .about-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:55px;align-items:center}.about-visual{border-radius:28px;min-height:340px;padding:32px;background:linear-gradient(145deg,#d8f4eb,#edf9f5);display:flex;align-items:end;position:relative;overflow:hidden}.about-visual:before{content:"ب";position:absolute;inset:0;display:grid;place-items:center;font-size:17rem;font-weight:950;color:#0b9c8410}.about-note{position:relative;background:#fff;border:1px solid #ffffff;border-radius:19px;padding:20px;width:100%;box-shadow:0 15px 35px #0c4b3d12}.about-note strong{display:block;font-size:1.1rem}.about-note p{color:var(--muted);font-size:.86rem;margin:6px 0 0}.about-copy h2{font-size:2.1rem;line-height:1.5;margin:10px 0 15px}.about-copy>p{color:var(--muted)}.check-list{list-style:none;padding:0;margin:23px 0 0;display:grid;gap:12px}.check-list li{display:flex;align-items:flex-start;gap:10px;color:#405b66;font-size:.91rem}.check{flex:0 0 23px;width:23px;height:23px;border-radius:8px;background:#ddf5ed;color:#078775;display:grid;place-items:center;font-weight:900;font-size:.8rem;margin-top:3px}
        .article-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.article-card{border:1px solid var(--line);background:#fff;border-radius:22px;overflow:hidden}.article-art{height:145px;display:grid;place-items:center;background:linear-gradient(135deg,#dff5ee,#f3fbf7);font-size:3.3rem}.article-card:nth-child(2) .article-art{background:linear-gradient(135deg,#fff0d7,#fff8eb)}.article-card:nth-child(3) .article-art{background:linear-gradient(135deg,#e5eaff,#f4f5ff)}.article-body{padding:21px}.article-tag{font-size:.74rem;font-weight:850;color:var(--teal-dark)}.article-body h3{font-size:1rem;line-height:1.8;margin:7px 0}.article-body p{font-size:.85rem;color:var(--muted);margin-bottom:0}
        .partners{padding:0 0 82px}.partner-panel{border-radius:28px;padding:42px;background:linear-gradient(120deg,#e4f8f2,#f4fbf8);border:1px solid #d7eee6;display:grid;grid-template-columns:1fr auto;align-items:center;gap:30px}.partner-panel h2{font-size:1.7rem;margin:0 0 8px}.partner-panel p{color:var(--muted);margin:0;max-width:690px}.partner-actions{display:flex;gap:10px;flex-wrap:wrap}.mini-label{display:inline-block;font-size:.75rem;font-weight:850;color:#087f78;background:#d4f2e8;padding:4px 10px;border-radius:999px;margin-bottom:11px}
        .contact-section{background:#fff;border-top:1px solid var(--line)}.contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:45px;align-items:center}.contact-grid h2{font-size:2rem;margin:10px 0}.contact-grid p{color:var(--muted)}.contact-card{padding:25px;border:1px solid var(--line);border-radius:22px;background:#fbfdfc}.contact-row{display:flex;gap:13px;align-items:center;padding:13px 0}.contact-row+.contact-row{border-top:1px solid var(--line)}.contact-symbol{width:42px;height:42px;border-radius:14px;background:#e5f7f1;color:#078775;display:grid;place-items:center;font-size:1.15rem}.contact-row strong{display:block;font-size:.92rem}.contact-row small{display:block;color:var(--muted);font-size:.8rem}.contact-email{color:var(--teal-dark);direction:ltr;display:inline-block}
        footer{background:#091f2c;color:#dce8e9;padding:35px 0 25px}.footer-main{display:flex;align-items:flex-start;justify-content:space-between;gap:35px;padding-bottom:26px}.footer-brand p{color:#a7bbc2;font-size:.83rem;max-width:360px;margin:13px 0 0}.footer-links{display:flex;flex-wrap:wrap;gap:18px;font-size:.83rem;color:#c0d0d4}.footer-links a:hover{color:#7de0cd}.footer-bottom{border-top:1px solid #ffffff19;padding-top:17px;display:flex;justify-content:space-between;gap:15px;color:#9bb0b8;font-size:.77rem}.footer-trust{display:flex;align-items:center;gap:12px;margin-top:22px;padding:13px 15px;width:max-content;max-width:100%;border:1px solid rgba(125,224,205,.22);border-radius:15px;background:rgba(255,255,255,.045)}.footer-trust img{display:block;max-width:110px;max-height:90px;object-fit:contain;border-radius:7px}.footer-trust-label{display:grid;gap:2px}.footer-trust-label strong{font-size:.82rem;color:#e9fffa}.footer-trust-label span{font-size:.72rem;color:#9bbabf}.footer-trust:hover{border-color:rgba(125,224,205,.5);background:rgba(255,255,255,.075)}
        @media(max-width:900px){.nav-links{gap:12px}.hero-grid{gap:25px}.dashboard-art{min-height:360px}.orb{width:270px;height:270px}.mock-window{padding:16px}.floating-note{left:-5px}.about-grid{gap:30px}.partner-panel{grid-template-columns:1fr}.partner-actions{margin-top:5px}}
        @media(max-width:700px){.wrap{width:min(100% - 28px,560px)}.nav{min-height:70px;flex-wrap:wrap;padding:10px 0}.nav-links{order:3;width:100%;overflow-x:auto;white-space:nowrap;padding:3px 0 7px;font-size:.82rem}.nav-actions .btn-ghost{display:none}.brand{font-size:1.12rem}.brand-mark{width:38px;height:38px}.nav-actions .btn{padding:9px 12px;font-size:.82rem}.hero{padding:50px 0 35px}.hero-grid{grid-template-columns:1fr;gap:12px}.hero-copy{font-size:1rem}.dashboard-art{min-height:350px;margin-top:3px}.orb{right:10%;width:280px;height:280px}.mock-window{width:min(430px,94%);transform:none}.floating-note{left:0;bottom:13px}.trust-items{grid-template-columns:repeat(2,1fr);gap:13px;padding:18px 0}.trust-item{justify-content:flex-start;font-size:.78rem}.trust-item span{width:31px;height:31px}.content-section,section.content-section{padding:60px 0}.feature-grid,.steps,.article-grid{grid-template-columns:1fr}.feature-card{min-height:0}.section-head{margin-bottom:27px}.about-grid,.contact-grid{grid-template-columns:1fr;gap:27px}.about-visual{min-height:260px}.about-copy h2,.contact-grid h2{font-size:1.7rem}.partner-panel{padding:25px}.partner-panel h2{font-size:1.35rem}.footer-main,.footer-bottom{flex-direction:column}.footer-main{gap:24px}.footer-links{gap:13px}.footer-bottom{gap:5px}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{transition:none!important}}
    
        /* Homepage visual refresh: a more distinctive, premium first impression. */
        .site-header{background:rgba(250,253,252,.88);backdrop-filter:blur(20px);box-shadow:0 5px 24px rgba(8,39,49,.035)}
        .brand-mark{position:relative;isolation:isolate;border:1px solid rgba(255,255,255,.45);box-shadow:0 10px 24px rgba(8,127,120,.25),inset 0 1px 0 rgba(255,255,255,.4)}
        .hero{padding:clamp(64px,8vw,104px) 0 clamp(56px,7vw,88px);color:#f4fffc;background:radial-gradient(ellipse at 8% 14%,rgba(32,202,171,.22),transparent 34%),radial-gradient(ellipse at 87% 87%,rgba(30,156,142,.2),transparent 35%),linear-gradient(122deg,#091f2d 0%,#103746 48%,#105b58 100%)}
        .hero:before{left:-150px;top:-205px;border-color:rgba(133,239,211,.17);box-shadow:0 0 0 45px rgba(133,239,211,.035),0 0 0 95px rgba(133,239,211,.025),0 0 0 150px rgba(133,239,211,.018)}
        .hero:after{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:46px 46px;mask-image:linear-gradient(90deg,transparent,#000 45%,#000);pointer-events:none}
        .hero-grid{z-index:1;gap:clamp(30px,5vw,66px)}
        .eyebrow{border:1px solid rgba(143,244,216,.24);background:rgba(91,219,183,.12);color:#a9f4df;box-shadow:inset 0 1px 0 rgba(255,255,255,.06)}
        .eyebrow i{box-shadow:0 0 0 5px rgba(43,213,169,.13),0 0 15px rgba(43,213,169,.65)}
        h1{font-size:clamp(2.45rem,4.4vw,4.35rem);line-height:1.38;letter-spacing:-1.8px;text-wrap:balance}
        h1 span{color:#6de2c4;text-shadow:0 0 35px rgba(77,223,188,.12)}
        .hero-copy{color:#c0d7da;max-width:560px;font-size:clamp(1rem,1.35vw,1.12rem);line-height:2.15}
        .hero .btn-primary{background:linear-gradient(135deg,#35d2b0,#0eaa91);color:#062d31;box-shadow:0 12px 30px rgba(19,201,163,.22),inset 0 1px 0 rgba(255,255,255,.32)}
        .hero .btn-primary:hover{background:linear-gradient(135deg,#68e6c7,#19bda0);box-shadow:0 16px 35px rgba(19,201,163,.3)}
        .hero .btn-light{background:rgba(255,255,255,.055);border-color:rgba(255,255,255,.2);color:#f3fffb;backdrop-filter:blur(10px)}
        .hero .btn-light:hover{background:rgba(255,255,255,.11)}
        .hero-proof{color:#adc6cc}.hero-proof strong{color:#f4fffc}
        .proof-avatars span{border-color:#123746;background:linear-gradient(145deg,#b5f2df,#64cdb4);color:#0d4e4a;box-shadow:0 5px 13px rgba(0,0,0,.12)}
        .dashboard-art{min-height:450px;perspective:1200px}
        .orb{width:360px;height:360px;right:6%;top:4%;background:radial-gradient(circle at 30% 25%,#c3ffec,#6ed8c1 48%,#218f88 100%);opacity:.88;filter:drop-shadow(0 25px 70px rgba(28,211,176,.16));box-shadow:inset -25px -28px 60px rgba(4,69,67,.17),0 0 0 22px rgba(105,229,196,.035),0 0 0 48px rgba(105,229,196,.025)}
        .mock-window{width:min(460px,100%);padding:23px;border-color:rgba(255,255,255,.82);box-shadow:0 38px 90px rgba(0,13,22,.38),0 0 0 8px rgba(255,255,255,.035);transform:rotateY(-5deg) rotateZ(-2deg);animation:borjik-float 7s ease-in-out infinite}
        .mock-top{padding-bottom:17px}.mock-title{font-size:1rem}.mock-sub{margin-top:2px}
        .stat{background:linear-gradient(145deg,#f8fcfa,#f0f8f5);border-color:#e4f0eb}
        .mock-chart{background:linear-gradient(180deg,#fff,#fbfefd)}
        .floating-note{left:-14px;bottom:35px;padding:14px 17px;border-color:rgba(255,255,255,.85);box-shadow:0 20px 48px rgba(0,13,22,.2);animation:borjik-note 5s ease-in-out infinite}
        .floating-icon{background:linear-gradient(145deg,#fff0d1,#ffe4a8);box-shadow:inset 0 1px 0 #fff}
        .trust-strip{position:relative;background:#fff;border-block-color:#e4eeeb}
        .trust-item{transition:transform .2s ease,color .2s ease}.trust-item:hover{transform:translateY(-2px);color:#087f78}
        .trust-item span{box-shadow:inset 0 1px 0 rgba(255,255,255,.9);border:1px solid #dff1eb}
        .content-section .section-head h2{letter-spacing:-.9px;text-wrap:balance}
        .feature-card{position:relative;overflow:hidden;box-shadow:0 8px 25px rgba(18,61,60,.025)}
        .feature-card:after{content:"";position:absolute;inset-inline:24px;bottom:0;height:3px;border-radius:3px 3px 0 0;background:linear-gradient(90deg,#13a895,#a1ead7);transform:scaleX(0);transform-origin:right;transition:transform .25s ease}
        .feature-card:hover:after{transform:scaleX(1)}
        .feature-icon{box-shadow:inset 0 1px 0 rgba(255,255,255,.8);border:1px solid rgba(13,151,128,.08)}
        .step{backdrop-filter:blur(12px);transition:transform .2s ease,border-color .2s ease,background .2s ease}
        .step:hover{transform:translateY(-4px);border-color:rgba(125,224,205,.4);background:rgba(255,255,255,.11)}
        .step-number{box-shadow:0 8px 20px rgba(28,174,154,.18)}
        .about-visual{box-shadow:inset 0 1px 0 rgba(255,255,255,.8)}
        .about-note{border-color:rgba(255,255,255,.9);box-shadow:0 22px 48px rgba(12,75,61,.14)}
        .article-card{transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}
        .article-card:hover{transform:translateY(-5px);border-color:#c8e5dc;box-shadow:0 18px 38px rgba(19,66,58,.08)}
        .article-art{position:relative;overflow:hidden}.article-art:after{content:"";position:absolute;width:160px;height:160px;border:1px solid rgba(18,112,96,.08);border-radius:50%;box-shadow:0 0 0 22px rgba(18,112,96,.035),0 0 0 44px rgba(18,112,96,.025);transform:translate(35px,40px)}
        .partner-panel{position:relative;overflow:hidden;box-shadow:0 18px 45px rgba(20,106,87,.055)}
        .partner-panel:after{content:"ب";position:absolute;left:24px;bottom:-90px;font-size:240px;line-height:1;font-weight:950;color:rgba(11,156,132,.045);pointer-events:none}
        .partner-panel>*{position:relative;z-index:1}
        .contact-card{box-shadow:0 14px 40px rgba(15,56,57,.04)}
        .contact-row{transition:transform .18s ease}.contact-row:hover{transform:translateX(-3px)}
        .contact-symbol{border:1px solid #d7f0e7}
        @keyframes borjik-float{0%,100%{transform:rotateY(-5deg) rotateZ(-2deg) translateY(0)}50%{transform:rotateY(-3deg) rotateZ(-1deg) translateY(-8px)}}
        @keyframes borjik-note{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
        @media(max-width:900px){.hero{padding-block:64px}.dashboard-art{min-height:390px}.orb{width:300px;height:300px}.mock-window{transform:rotateZ(-1deg)}}
        @media(max-width:700px){.hero{padding:47px 0 35px}.hero:after{background-size:32px 32px}.hero-grid{gap:8px}h1{font-size:clamp(2.15rem,8vw,3.1rem);letter-spacing:-1.2px}.hero-copy{font-size:.96rem}.dashboard-art{min-height:345px;margin-top:9px}.orb{width:270px;height:270px;right:8%;top:3%}.mock-window{padding:16px;width:min(430px,96%);transform:none;animation:none}.floating-note{left:0;bottom:4px;animation:none;padding:11px 13px}.hero-proof{font-size:.78rem}.partner-panel:after{font-size:170px;bottom:-62px;left:6px}}
        @media(prefers-reduced-motion:reduce){.mock-window,.floating-note{animation:none!important}.trust-item,.feature-card:after,.step,.article-card,.contact-row{transition:none!important}}

        /* Signature polish: cinematic hero, richer dashboard depth, and crisp section rhythm. */
        .hero{isolation:isolate}
        .hero-grid>div:first-child{position:relative;z-index:2}
        .hero-grid>div:first-child:before{content:"";position:absolute;z-index:-1;inset:-28px -30px -25px -18px;border-radius:32px;background:radial-gradient(ellipse at 68% 35%,rgba(69,216,181,.075),transparent 65%);pointer-events:none}
        .hero .eyebrow{font-size:.84rem;letter-spacing:.1px}
        .hero h1{max-width:650px;text-shadow:0 4px 28px rgba(0,0,0,.09)}
        .hero h1 span{display:inline-block;position:relative}
        .hero h1 span:after{content:"";position:absolute;right:0;left:0;bottom:-4px;height:3px;border-radius:10px;background:linear-gradient(90deg,transparent,#66e5c5 20%,#66e5c5 80%,transparent);opacity:.75}
        .hero-actions .btn{min-height:51px;padding-inline:21px}
        .hero .btn-primary{position:relative;overflow:hidden}
        .hero .btn-primary:after{content:"";position:absolute;top:-80%;bottom:-80%;right:-55%;width:35%;transform:rotate(22deg);background:linear-gradient(90deg,transparent,rgba(255,255,255,.42),transparent);transition:right .65s ease}
        .hero .btn-primary:hover:after{right:125%}
        .dashboard-art:before{content:"✦  تجربه‌ای یکپارچه برای ساختمان";position:absolute;z-index:3;top:5px;left:2px;padding:9px 14px;border:1px solid rgba(176,245,225,.22);border-radius:13px;background:rgba(8,36,47,.72);color:#c8f9e9;font-size:.74rem;font-weight:800;letter-spacing:.1px;box-shadow:0 12px 32px rgba(0,0,0,.16);backdrop-filter:blur(14px)}
        .dashboard-art .orb{animation:borjik-orb 12s ease-in-out infinite}
        .mock-window{border-radius:27px;background:linear-gradient(145deg,rgba(255,255,255,.99),rgba(245,252,249,.97));outline:1px solid rgba(112,234,203,.14);outline-offset:5px}
        .mock-top:before{content:"";width:8px;height:8px;border-radius:50%;background:#22b99b;box-shadow:0 0 0 4px rgba(34,185,155,.12);order:3;margin-inline-start:auto}
        .mock-stats .stat{transition:transform .2s ease,box-shadow .2s ease}
        .mock-stats .stat:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(11,112,92,.08)}
        .mock-chart{position:relative;overflow:hidden}
        .mock-chart:before{content:"";position:absolute;inset:0;background:linear-gradient(115deg,transparent 20%,rgba(31,188,155,.045),transparent 70%);pointer-events:none}
        .floating-note{z-index:4}
        .trust-strip{box-shadow:0 12px 35px rgba(12,51,51,.025)}
        .section-head h2{position:relative}
        .section-head h2:after{content:"";display:block;width:56px;height:4px;margin:13px auto 0;border-radius:99px;background:linear-gradient(90deg,#73ddc2,#0e9e87)}
        .feature-card{border-radius:24px;padding:28px;background:linear-gradient(155deg,#fff 0%,#fff 65%,#f8fcfa 100%)}
        .feature-card h3{font-size:1.08rem}
        .feature-card p{line-height:2}
        .how{background:radial-gradient(ellipse at 85% 15%,rgba(35,177,151,.14),transparent 34%),linear-gradient(130deg,#071e2b,#0c2c3b 55%,#104b49)}
        .step{border-radius:24px;box-shadow:inset 0 1px 0 rgba(255,255,255,.055)}
        .step-number{background:linear-gradient(145deg,#42d5b4,#0c9b87);color:#07352f}
        .about-visual{background:radial-gradient(circle at 78% 20%,#fff 0,transparent 27%),linear-gradient(145deg,#c9f0e5,#eaf9f4 65%,#d9f4eb)}
        .article-art{height:165px}
        .article-body{padding:23px}
        .partner-panel{border-radius:30px;background:radial-gradient(circle at 8% 8%,rgba(65,199,165,.14),transparent 32%),linear-gradient(120deg,#e2f8f0,#f7fcfa)}
        .contact-card{border-radius:26px;padding:28px;background:linear-gradient(145deg,#fff,#f8fcfa)}
        .contact-symbol{box-shadow:0 6px 15px rgba(13,151,128,.06)}
        footer{background:radial-gradient(ellipse at 90% 0%,rgba(24,142,124,.14),transparent 35%),#071c28}
        @keyframes borjik-orb{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-9px) scale(1.025)}}
        @media(max-width:900px){.dashboard-art:before{top:0;left:0}.hero-grid>div:first-child:before{inset:-15px}}
        @media(max-width:700px){.dashboard-art:before{top:-2px;left:0;font-size:.67rem;padding:7px 10px}.hero h1 span:after{bottom:-2px}.hero-actions .btn{min-height:47px;padding-inline:15px}.article-art{height:145px}.feature-card{padding:23px}}
        @media(prefers-reduced-motion:reduce){.dashboard-art .orb{animation:none!important}.hero .btn-primary:after{display:none}.mock-stats .stat{transition:none!important}}


        /* Final hero pass: clearer value cues and subtle premium motion. */
        .hero{overflow:hidden}
        .hero:before{animation:borjik-ring-drift 22s ease-in-out infinite}
        .hero-grid>div:first-child{animation:borjik-copy-enter .75s cubic-bezier(.2,.7,.2,1) both}
        .dashboard-art{animation:borjik-art-enter .9s .08s cubic-bezier(.2,.7,.2,1) both}
        .hero-highlights{display:flex;flex-wrap:wrap;gap:9px;margin-top:23px;max-width:620px}
        .hero-highlights span{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid rgba(193,244,227,.16);border-radius:12px;background:rgba(255,255,255,.045);color:#d0e5e4;font-size:.76rem;font-weight:750;backdrop-filter:blur(9px);transition:background .2s ease,border-color .2s ease,transform .2s ease}
        .hero-highlights span:hover{transform:translateY(-2px);background:rgba(255,255,255,.09);border-color:rgba(117,231,199,.36)}
        .hero-highlights i{font-style:normal;display:grid;place-items:center;width:20px;height:20px;border-radius:7px;background:rgba(79,221,184,.14);color:#79e7c8;font-size:.76rem}
        .dashboard-art .orb{background:radial-gradient(circle at 32% 25%,#d6fff3 0,#9af0d9 25%,#42bca9 57%,#176d70 100%);box-shadow:inset -25px -28px 60px rgba(4,69,67,.2),0 0 0 22px rgba(105,229,196,.045),0 0 0 48px rgba(105,229,196,.028),0 0 70px rgba(42,210,175,.12)}
        .mock-window{backdrop-filter:blur(14px);box-shadow:0 42px 100px rgba(0,13,22,.42),0 0 0 8px rgba(255,255,255,.045),0 0 50px rgba(49,215,180,.08)}
        .floating-note{border-radius:18px}
        @keyframes borjik-ring-drift{0%,100%{transform:translate3d(0,0,0)}50%{transform:translate3d(18px,12px,0)}}
        @keyframes borjik-copy-enter{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
        @keyframes borjik-art-enter{from{opacity:0;transform:translateY(15px) scale(.985)}to{opacity:1;transform:translateY(0) scale(1)}}
        @media(max-width:900px){.hero-highlights{gap:7px}.hero-highlights span{font-size:.72rem;padding:6px 8px}}
        @media(max-width:700px){.hero-highlights{margin-top:20px;gap:7px}.hero-highlights span{font-size:.7rem}.hero-grid>div:first-child{animation:none}.dashboard-art{animation:none}}
        @media(prefers-reduced-motion:reduce){.hero:before,.hero-grid>div:first-child,.dashboard-art{animation:none!important}.hero-highlights span{transition:none!important}}

    
        /* Bold, high-contrast homepage hero refresh */
        .hero{
            isolation:isolate;
            padding:clamp(62px,7vw,104px) 0 clamp(58px,6vw,88px);
            color:#f3fbfa;
            background:
                radial-gradient(ellipse at 78% 28%,rgba(37,204,169,.22),transparent 35%),
                radial-gradient(ellipse at 8% 92%,rgba(47,116,174,.19),transparent 36%),
                linear-gradient(128deg,#071b2a 0%,#0a2b3b 48%,#0b4546 100%);
        }
        .hero:before{
            width:590px;height:590px;left:-210px;top:-270px;
            border-color:rgba(132,239,211,.16);
            box-shadow:0 0 0 48px rgba(132,239,211,.035),0 0 0 100px rgba(132,239,211,.025),0 0 0 155px rgba(132,239,211,.016);
            pointer-events:none;
        }
        .hero:after{
            content:"";position:absolute;z-index:-1;width:440px;height:440px;right:24%;bottom:-350px;
            border-radius:50%;background:#22c9a5;filter:blur(100px);opacity:.13;pointer-events:none;
        }
        .hero-grid{gap:clamp(34px,5vw,72px)}
        .hero-grid>div:first-child{position:relative;z-index:2}
        .eyebrow{padding:8px 14px;background:rgba(101,228,194,.1);border:1px solid rgba(129,239,209,.22);color:#a8f2db;box-shadow:inset 0 1px rgba(255,255,255,.04)}
        .eyebrow i{box-shadow:0 0 0 5px rgba(44,218,173,.12),0 0 16px rgba(44,218,173,.55)}
        .hero h1{font-size:clamp(2.55rem,4.5vw,4.55rem);line-height:1.35;letter-spacing:-1.8px;margin:24px 0 22px;color:#fff}
        .hero h1 span{color:#7ce8c8;text-shadow:0 5px 35px rgba(65,223,181,.13)}
        .hero h1 span:after{content:"";position:absolute;right:0;left:0;bottom:-5px;height:5px;border-radius:8px;background:linear-gradient(90deg,rgba(124,232,200,0),rgba(124,232,200,.8),rgba(124,232,200,.1));opacity:.8}
        .hero-copy{max-width:560px;font-size:clamp(1rem,1.2vw,1.13rem);line-height:2.15;color:#c0d4da}
        .hero-actions{gap:13px}
        .hero-actions .btn{min-height:52px;padding:13px 21px;border-radius:15px}
        .hero-actions .btn-primary{background:linear-gradient(135deg,#4be0b6,#16ae99);color:#062b2d;box-shadow:0 12px 30px rgba(28,201,162,.25),inset 0 1px rgba(255,255,255,.38)}
        .hero-actions .btn-primary:hover{background:linear-gradient(135deg,#75edc9,#24c5a7);box-shadow:0 16px 38px rgba(28,201,162,.32)}
        .hero-actions .btn-light{background:rgba(255,255,255,.055);border-color:rgba(220,246,241,.2);color:#f0faf8;backdrop-filter:blur(12px)}
        .hero-actions .btn-light:hover{background:rgba(255,255,255,.1);border-color:rgba(132,239,211,.42)}
        .hero-proof{color:#b5cbd1}
        .hero-proof strong{color:#f3fbfa}
        .proof-avatars span{border-color:#0a2b3b;background:linear-gradient(145deg,#d5fff1,#73d9c0);color:#075347}
        .hero-highlights{margin-top:24px}
        .hero-highlights span{background:rgba(255,255,255,.055);border-color:rgba(195,235,227,.17);color:#d8e9e8}
        .dashboard-art{min-height:455px;isolation:isolate}
        .dashboard-art:before{content:"پیش‌نمایش محیط صابخونه";position:absolute;z-index:3;top:4px;right:8px;padding:8px 13px;border:1px solid rgba(153,238,215,.2);border-radius:12px;background:rgba(7,29,42,.7);color:#c6f8e7;font-size:.72rem;font-weight:800;letter-spacing:.1px;backdrop-filter:blur(12px)}
        .dashboard-art:after{content:"";position:absolute;z-index:-1;inset:8% 5% 4%;border-radius:45%;background:radial-gradient(ellipse,rgba(38,210,170,.2),rgba(38,210,170,0) 68%);filter:blur(20px)}
        .dashboard-art .orb{width:clamp(270px,31vw,365px);height:clamp(270px,31vw,365px);right:10%;top:8%;background:radial-gradient(circle at 30% 24%,#d9fff4 0,#9af0d9 25%,#42bca9 57%,#176d70 100%);box-shadow:inset -25px -28px 60px rgba(4,69,67,.2),0 0 0 22px rgba(105,229,196,.055),0 0 0 48px rgba(105,229,196,.03),0 0 85px rgba(42,210,175,.2)}
        .mock-window{transform:rotate(-2.2deg) perspective(1200px) rotateY(-3deg);border:1px solid rgba(255,255,255,.85);box-shadow:0 42px 100px rgba(0,8,18,.46),0 0 0 8px rgba(255,255,255,.045),0 0 55px rgba(49,215,180,.12);transition:transform .35s ease,box-shadow .35s ease}
        .dashboard-art:hover .mock-window{transform:rotate(0) perspective(1200px) rotateY(0);box-shadow:0 48px 110px rgba(0,8,18,.5),0 0 0 8px rgba(255,255,255,.06),0 0 70px rgba(49,215,180,.17)}
        .floating-note{left:-12px;bottom:33px;border-color:rgba(220,255,245,.78);box-shadow:0 22px 55px rgba(0,12,24,.28);animation:borjik-note-float 5s ease-in-out infinite}
        .trust-strip{position:relative;z-index:1}
        @keyframes borjik-note-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
        @media(max-width:900px){
            .hero{padding:58px 0 64px}
            .hero-grid{gap:30px}
            .hero h1{font-size:clamp(2.3rem,5.2vw,3.6rem)}
            .dashboard-art{min-height:390px}
            .dashboard-art:before{right:2px;top:0}
            .floating-note{left:0;bottom:22px}
        }
        @media(max-width:700px){
            .hero{padding:47px 0 48px}
            .hero-grid{grid-template-columns:1fr;gap:24px}
            .hero h1{font-size:clamp(2.35rem,9vw,3.25rem);letter-spacing:-1px;margin:20px 0 16px}
            .hero-copy{font-size:.98rem;line-height:2}
            .hero-actions{display:grid;grid-template-columns:1fr 1fr}
            .hero-actions .btn{padding:11px 10px;font-size:.82rem}
            .hero-proof{margin-top:23px}
            .hero-highlights{margin-top:19px}
            .hero-highlights span{font-size:.69rem;padding:6px 8px}
            .dashboard-art{min-height:340px;margin-top:4px}
            .dashboard-art .orb{width:250px;height:250px;right:14%;top:10%}
            .mock-window{width:94%;padding:16px;transform:rotate(-1deg)}
            .mock-stats{gap:8px}
            .stat{padding:10px}
            .dashboard-art:before{font-size:.65rem;padding:6px 9px}
            .floating-note{bottom:9px;padding:10px 12px;font-size:.74rem}
            .floating-icon{width:31px;height:31px}
        }
        @media(prefers-reduced-motion:reduce){
            .floating-note{animation:none!important}
            .mock-window,.hero-actions .btn{transition:none!important}
            .dashboard-art:hover .mock-window{transform:rotate(-2deg)}
        }

    
        /* Premium visual system for all homepage sections, aligned with the hero palette. */
        :root{--ink:#102b3a;--muted:#657d87;--teal:#17b99b;--teal-dark:#087f78;--line:#dcebe7;--paper:#f4f8f7}
        .trust-strip{padding:18px 0;background:linear-gradient(180deg,#f7fbfa,#fff);border:0;box-shadow:0 14px 36px rgba(5,36,46,.045)}
        .trust-items{gap:12px;padding:8px 0}
        .trust-item{justify-content:flex-start;padding:13px 15px;border:1px solid #e2efeb;border-radius:17px;background:rgba(255,255,255,.92);box-shadow:0 8px 24px rgba(12,53,54,.035);font-size:.84rem;min-height:62px}
        .trust-item span{flex:0 0 36px;background:linear-gradient(145deg,#e1fbf2,#c8f2e6);color:#087f78;border:1px solid #cceee3}
        section.content-section{position:relative;padding:clamp(66px,7vw,96px) 0}
        #features.content-section{background:radial-gradient(ellipse at 8% 20%,rgba(37,191,159,.075),transparent 32%),linear-gradient(180deg,#f7fbfa,#edf6f3)}
        .section-head{margin-bottom:44px}
        .section-kicker{display:inline-flex;align-items:center;gap:8px;padding:5px 12px;border:1px solid #cfece2;border-radius:999px;background:#e7f8f1;color:#087f78;font-size:.78rem;letter-spacing:0}
        .section-head h2{margin-top:15px;color:#102d3b;font-size:clamp(1.8rem,3.2vw,2.75rem);line-height:1.45}
        .section-head h2:after{width:64px;height:4px;margin-top:16px;box-shadow:0 3px 10px rgba(19,168,149,.16)}
        .section-head p{max-width:650px;margin-inline:auto;line-height:2;color:#647b85;font-size:.96rem}
        .feature-grid{gap:19px}
        .feature-card{min-height:235px;padding:29px;border-radius:25px;border-color:#e0eeea;background:linear-gradient(150deg,#fff 0%,#fff 62%,#f2faf7 100%);box-shadow:0 12px 32px rgba(9,48,51,.045)}
        .feature-card:before{content:"";position:absolute;width:120px;height:120px;left:-50px;top:-55px;border-radius:50%;background:rgba(37,191,159,.055);transition:transform .35s ease}
        .feature-card:hover{transform:translateY(-7px);border-color:#c4e7dc;box-shadow:0 24px 48px rgba(9,48,51,.09)}
        .feature-card:hover:before{transform:scale(1.45)}
        .feature-icon{position:relative;width:57px;height:57px;border-radius:19px;margin-bottom:20px;background:linear-gradient(145deg,#e3faf2,#c9f0e4);color:#087f78;font-size:1.55rem;box-shadow:0 9px 20px rgba(14,155,128,.08)}
        .feature-card:nth-child(3n+2) .feature-icon{background:linear-gradient(145deg,#fff5e4,#ffe7bb);color:#a66a0b}
        .feature-card:nth-child(3n) .feature-icon{background:linear-gradient(145deg,#edf0ff,#dfe5ff);color:#5364b8}
        .feature-card h3{position:relative;color:#123342;font-size:1.08rem;margin-bottom:10px}
        .feature-card p{position:relative;color:#647983;line-height:2}
        .how{background:radial-gradient(ellipse at 88% 10%,rgba(39,201,166,.18),transparent 33%),radial-gradient(ellipse at 5% 100%,rgba(54,119,167,.15),transparent 35%),linear-gradient(125deg,#071b2a,#0b2e3c 53%,#0c4b49);padding:clamp(70px,8vw,100px) 0!important}
        .how:before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:42px 42px;mask-image:linear-gradient(180deg,#000,transparent 95%);pointer-events:none}
        .how .section-head h2{color:#f3fffb}.how .section-head h2:after{background:linear-gradient(90deg,#8bf0d3,#29bfa4)}
        .how .section-head p{color:#b7ced2}
        .steps{gap:18px}
        .step{position:relative;min-height:220px;padding:29px;border:1px solid rgba(191,239,226,.17);border-radius:25px;background:linear-gradient(145deg,rgba(255,255,255,.09),rgba(255,255,255,.035));box-shadow:inset 0 1px rgba(255,255,255,.06),0 18px 35px rgba(0,0,0,.08)}
        .step:after{content:"";position:absolute;inset-inline:28px;bottom:0;height:3px;border-radius:3px 3px 0 0;background:linear-gradient(90deg,#25c9a6,#8bf0d3);transform:scaleX(0);transform-origin:right;transition:transform .25s ease}
        .step:hover:after{transform:scaleX(1)}
        .step-number{width:47px;height:47px;border-radius:16px;background:linear-gradient(145deg,#8cf0d2,#22b99b);color:#073b36;font-size:1.05rem;box-shadow:0 10px 26px rgba(24,197,159,.2)}
        .step h3{font-size:1.08rem;color:#f3fffb}.step p{color:#b8cdd1;line-height:2}
        #about{background:linear-gradient(180deg,#fff,#f6fbf9)}
        .about-grid{gap:clamp(32px,6vw,76px)}
        .about-visual{min-height:390px;border:1px solid #d2eee4;border-radius:32px;padding:30px;background:radial-gradient(circle at 78% 18%,rgba(255,255,255,.95),transparent 28%),radial-gradient(circle at 20% 90%,rgba(36,185,154,.16),transparent 36%),linear-gradient(145deg,#d7f6eb,#edf9f5 62%,#c7eee2);box-shadow:0 25px 65px rgba(10,84,69,.08)}
        .about-visual:before{font-size:clamp(13rem,24vw,20rem);color:rgba(11,156,132,.09);text-shadow:0 10px 45px rgba(11,156,132,.04)}
        .about-note{padding:25px;border-radius:22px;border:1px solid rgba(255,255,255,.95);box-shadow:0 24px 50px rgba(12,75,61,.13)}
        .about-note strong{font-size:1.16rem;color:#123342}
        .about-note p{line-height:2;color:#657c85}
        .about-copy h2{font-size:clamp(1.8rem,3vw,2.5rem);line-height:1.5;letter-spacing:-.7px;color:#102d3b;margin:15px 0}
        .about-copy>p{font-size:.94rem;line-height:2.15;color:#607782}
        .check-list{gap:14px;margin-top:25px}
        .check-list li{color:#355562;line-height:1.9}
        .check{flex-basis:25px;width:25px;height:25px;border-radius:9px;background:linear-gradient(145deg,#d8f8eb,#bdebdc);box-shadow:0 4px 12px rgba(16,156,126,.08)}
        #articles.content-section{background:radial-gradient(ellipse at 95% 10%,rgba(38,184,154,.08),transparent 30%),linear-gradient(180deg,#eff7f4,#f8fbfa)!important}
        .article-grid{gap:20px;align-items:stretch}
        .article-card{display:flex;flex-direction:column;border-radius:25px;border-color:#deece8;background:#fff;box-shadow:0 12px 30px rgba(10,53,52,.04)}
        .article-card>a{display:block;overflow:hidden}
        .article-art{height:185px;display:grid;place-items:center;background:radial-gradient(circle at 30% 25%,#fff,transparent 35%),linear-gradient(135deg,#c9f2e5,#eaf9f3);font-size:3.6rem}
        .article-card:nth-child(2) .article-art{background:radial-gradient(circle at 30% 25%,#fff,transparent 35%),linear-gradient(135deg,#ffe8bf,#fff6e7)}
        .article-card:nth-child(3) .article-art{background:radial-gradient(circle at 30% 25%,#fff,transparent 35%),linear-gradient(135deg,#dce3ff,#f1f3ff)}
        .article-art img{transition:transform .5s cubic-bezier(.2,.7,.2,1)}
        .article-card:hover .article-art img{transform:scale(1.045)}
        .article-body{display:flex;flex:1;flex-direction:column;align-items:flex-start;padding:24px}
        .article-tag{display:inline-flex;align-items:center;gap:6px;color:#087f78;font-size:.75rem}
        .article-body h3{font-size:1.06rem;line-height:1.9;margin:9px 0;color:#153645}
        .article-body h3 a:hover{color:#087f78}
        .article-body p{font-size:.86rem;line-height:2;color:#657b84;margin-bottom:18px}
        .article-body>a.article-tag{margin-top:auto;padding-top:3px}
        #articles .btn-light{background:#fff;border-color:#d9eae4;box-shadow:0 8px 20px rgba(12,53,54,.045)}
        .partners{position:relative;padding:8px 0 clamp(65px,7vw,90px);background:#f8fbfa}
        .partner-panel{position:relative;isolation:isolate;grid-template-columns:minmax(0,1fr) auto;gap:34px;padding:clamp(27px,4vw,46px);border:1px solid #cfe9df;border-radius:30px;background:radial-gradient(ellipse at 4% 8%,rgba(44,194,157,.18),transparent 34%),linear-gradient(120deg,#e0f7ee,#f7fcfa 70%);box-shadow:0 22px 55px rgba(10,92,74,.07)}
        .partner-panel h2{font-size:clamp(1.4rem,2.5vw,1.9rem);line-height:1.65;color:#103342;margin:0 0 10px}
        .partner-panel p{font-size:.92rem;line-height:2.1;color:#5e7881}
        .mini-label{padding:6px 12px;border:1px solid #c8eade;background:rgba(255,255,255,.65);color:#087f78}
        .partner-actions{align-items:center}
        .partner-actions .btn{white-space:nowrap;min-height:48px;box-shadow:0 10px 22px rgba(12,113,91,.12)}
        #partners.content-section{background:linear-gradient(180deg,#f8fbfa,#eef7f4);padding-bottom:clamp(65px,7vw,90px)!important}
        #partners .partner-panel{background:radial-gradient(ellipse at 100% 0%,rgba(83,128,213,.12),transparent 32%),linear-gradient(125deg,#fff,#f2f7ff);border-color:#dce5f2;box-shadow:0 22px 55px rgba(35,65,117,.06)}
        #partners .mini-label{color:#5266b3;border-color:#dce3fa;background:#f1f3ff}
        #partners .partner-actions .btn-light{background:#fff;border-color:#d9e3f0;color:#334b76}
        .contact-section{position:relative;overflow:hidden;background:radial-gradient(ellipse at 88% 15%,rgba(38,194,159,.14),transparent 34%),linear-gradient(125deg,#071c2a,#0c303d 58%,#0d4b49);border:0}
        .contact-section:before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);background-size:44px 44px;mask-image:linear-gradient(90deg,transparent,#000);pointer-events:none}
        .contact-grid{position:relative;z-index:1;gap:clamp(30px,6vw,75px)}
        .contact-grid .section-kicker{background:rgba(101,228,194,.1);border-color:rgba(129,239,209,.22);color:#a8f2db}
        .contact-grid h2{font-size:clamp(1.8rem,3vw,2.5rem);line-height:1.5;color:#f4fffc;margin:16px 0}
        .contact-grid>div:first-child>p{font-size:.95rem;line-height:2.15;color:#bfd3d7;max-width:510px;margin-bottom:25px}
        .contact-grid>div:first-child .btn-primary{background:linear-gradient(135deg,#4be0b6,#16ae99);color:#062b2d;box-shadow:0 12px 30px rgba(28,201,162,.2)}
        .contact-card{padding:22px 27px;border-radius:27px;border:1px solid rgba(208,244,233,.2);background:linear-gradient(145deg,rgba(255,255,255,.105),rgba(255,255,255,.055));box-shadow:0 28px 65px rgba(0,0,0,.16);backdrop-filter:blur(18px)}
        .contact-row{gap:15px;padding:17px 0;border-color:rgba(230,255,247,.12)}
        .contact-row strong{color:#f0fffb;font-size:.93rem}
        .contact-row small,.contact-row a:not(.contact-email){color:#b9d0d4;line-height:1.9}
        .contact-email{color:#81e8cb}
        .contact-symbol{flex:0 0 44px;width:44px;height:44px;border-radius:15px;background:linear-gradient(145deg,rgba(111,232,197,.18),rgba(111,232,197,.08));border:1px solid rgba(130,235,207,.2);color:#8cebd0;box-shadow:none}
        footer{background:radial-gradient(ellipse at 92% 0%,rgba(26,152,130,.18),transparent 36%),#061722;border-top:1px solid rgba(126,228,201,.12)}
        .footer-main{padding-bottom:31px}
        .footer-brand .brand{color:#f2fffb}
        .footer-brand .brand-mark{background:linear-gradient(145deg,#35d2b0,#087f78)}
        .footer-brand p{color:#a9c0c6;line-height:2}
        .footer-links{max-width:510px;gap:12px 20px}
        .footer-links a{padding:5px 0;transition:color .2s ease}
        .footer-bottom{border-color:rgba(255,255,255,.12);color:#91aab2}
        .footer-trust{background:rgba(255,255,255,.045);border-color:rgba(125,224,205,.22)}
        .footer-trust img{background:#fff;padding:5px}
        .btn:focus-visible,a:focus-visible{outline:3px solid rgba(39,198,162,.55);outline-offset:3px}
        @media(max-width:900px){
            .trust-items{grid-template-columns:repeat(2,minmax(0,1fr))}
            .feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .about-visual{min-height:330px}
            .article-art{height:155px}
            .partner-panel{grid-template-columns:1fr;gap:17px}
            .partner-actions{margin-top:0}
        }
        @media(max-width:700px){
            .trust-strip{padding:13px 0}
            .trust-items{gap:9px}
            .trust-item{padding:10px;gap:8px;font-size:.74rem;line-height:1.7;min-height:58px}
            .trust-item span{flex-basis:31px;width:31px;height:31px;border-radius:10px;font-size:.95rem}
            section.content-section{padding:59px 0}
            .section-head{margin-bottom:29px}
            .section-head h2{font-size:1.75rem;line-height:1.55}
            .section-head p{font-size:.9rem}
            .feature-grid{grid-template-columns:1fr;gap:13px}
            .feature-card{padding:22px;min-height:0;border-radius:21px}
            .feature-icon{width:49px;height:49px;border-radius:16px;margin-bottom:15px}
            .steps{grid-template-columns:1fr;gap:13px}
            .step{min-height:0;padding:23px;border-radius:21px}
            .about-grid{gap:25px}
            .about-visual{min-height:250px;padding:19px;border-radius:24px}
            .about-visual:before{font-size:13rem}
            .about-note{padding:19px;border-radius:18px}
            .about-copy h2{font-size:1.8rem}
            #articles.content-section{padding-block:58px}
            .article-grid{grid-template-columns:1fr;gap:15px}
            .article-card{border-radius:21px}
            .article-art{height:180px}
            .article-body{padding:20px}
            .partners{padding-bottom:58px}
            .partner-panel{padding:23px;border-radius:24px}
            .partner-panel h2{font-size:1.35rem}
            .partner-actions .btn{white-space:normal}
            #partners.content-section{padding-bottom:58px!important}
            .contact-grid{gap:25px}
            .contact-card{padding:16px 20px;border-radius:22px}
            .contact-row{padding:14px 0;gap:12px}
            .contact-symbol{flex-basis:39px;width:39px;height:39px;border-radius:13px}
            footer{padding-top:30px}
            .footer-links{gap:8px 16px}
        }
        @media(prefers-reduced-motion:reduce){
            .feature-card,.feature-card:before,.step:after,.article-art img,.footer-links a{transition:none!important}
            .feature-card:hover,.step:hover,.article-card:hover{transform:none}
        }

    </style>
</head>
<body>
@php
$defaultFeatures = [
 ['icon'=>'◉','title'=>'محاسبه و صدور شارژ','description'=>'قواعد شارژ و هزینه‌های ساختمان را مدیریت کنید و سهم هر واحد را با روشی روشن محاسبه کنید.'],
 ['icon'=>'⇄','title'=>'پرداخت و پیگیری مالی','description'=>'صورتحساب‌ها، ثبت پرداخت و وضعیت تأیید فیش‌ها را در یک مسیر مشخص دنبال کنید.'],
 ['icon'=>'☷','title'=>'درخواست‌ها و وظایف','description'=>'درخواست‌های ساکنان را ثبت، ارجاع و پیگیری کنید تا هیچ موردی میان پیام‌ها گم نشود.'],
 ['icon'=>'♧','title'=>'مدیریت اعضا و پرسنل','description'=>'نقش‌ها و دسترسی‌ها را متناسب با مسئولیت مدیر، ساکن و پرسنل سازمان‌دهی کنید.'],
 ['icon'=>'▦','title'=>'تقویم و اطلاع‌رسانی','description'=>'رویدادها و برنامه‌های ساختمان را منظم نگه دارید و ارتباطات را از مسیرهای مشخص پیش ببرید.'],
 ['icon'=>'▤','title'=>'اسناد و سوابق','description'=>'به اطلاعات و سوابق موردنیاز دسترسی داشته باشید و تصمیم‌ها را با دید روشن‌تری بگیرید.'],
];
$features = data_get($content ?? [], 'features', $defaultFeatures);
@endphp
<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="#home" aria-label="صابخونه، صفحه اصلی"><span class="brand-mark" style="background:#fff;padding:5px;border:1px solid #e1eae8"><img src="{{ asset('images/sabkhoneh-mark.png') }}" alt="" style="display:block;width:100%;height:100%;object-fit:contain"></span><span>صابخونه</span></a>
        <nav class="nav-links" aria-label="ناوبری اصلی">
            <a href="#features">امکانات</a><a href="#about">درباره ما</a><a href="#articles">مقالات</a><a href="#ads">تبلیغات</a><a href="#partners">همکاران ما</a><a href="#contact">تماس با ما</a>
        </nav>
        <div class="nav-actions"><a class="btn btn-ghost" href="#features">آشنایی با صابخونه</a><a class="btn btn-primary" href="{{ route('login') }}">ورود به سامانه <span aria-hidden="true">←</span></a></div>
    </div>
</header>

<main>
    <section class="hero" id="home">
        <div class="wrap hero-grid">
            <div>
                <span class="eyebrow"><i></i> {{ data_get($content ?? [], 'hero.badge', 'مدیریت نوین ساختمان، در یک جا') }}</span>
                <h1>{{ data_get($content ?? [], 'hero.title', 'مدیریت ساختمان،') }}<br><span>{{ data_get($content ?? [], 'hero.highlight', 'با آرامش بیشتر') }}</span></h1>
                <p class="hero-copy">{{ data_get($content ?? [], 'hero.description', 'صابخونه کمک می‌کند امور مالی، شارژ، درخواست‌های ساکنان و ارتباطات ساختمان را شفاف‌تر و منظم‌تر مدیریت کنید؛ بدون کاغذبازی و سردرگمی.') }}</p>
                <div class="hero-actions"><a class="btn btn-primary" href="{{ route('login') }}">{{ data_get($content ?? [], 'hero.primary_cta', 'شروع استفاده از صابخونه') }} <span aria-hidden="true">←</span></a><a class="btn btn-light" href="#features">{{ data_get($content ?? [], 'hero.secondary_cta', 'امکانات را ببینید') }} <span aria-hidden="true">⌄</span></a></div>
                <div class="hero-proof"><div class="proof-avatars" aria-hidden="true"><span>م</span><span>س</span><span>ب</span></div><div><strong>همه‌چیز شفاف‌تر</strong><br>برای مدیران، ساکنان و پرسنل ساختمان</div></div>
                <div class="hero-highlights" aria-label="مزیت‌های صابخونه">
                    <span><i aria-hidden="true">✓</i> امور مالی شفاف</span>
                    <span><i aria-hidden="true">↗</i> پیگیری مرحله‌به‌مرحله</span>
                    <span><i aria-hidden="true">⌘</i> دسترسی متناسب با نقش</span>
                </div>
            </div>
            <div class="dashboard-art" aria-label="پیش‌نمایش تصویری داشبورد صابخونه">
                <div class="orb"></div>
                <div class="mock-window">
                    <div class="mock-top"><div><div class="mock-title">نمای کلی ساختمان</div><div class="mock-sub">یک نگاه؛ تصویر روشن‌تر</div></div><span class="live-dot">● منظم و شفاف</span></div>
                    <div class="mock-stats">
                        <div class="stat"><small>مدیریت هزینه‌ها</small><strong>شفاف</strong><em>✓ قابل پیگیری</em></div>
                        <div class="stat"><small>درخواست‌های ساکنان</small><strong>منظم</strong><em>✓ مرحله‌به‌مرحله</em></div>
                        <div class="stat"><small>شارژ و پرداخت</small><strong>یکپارچه</strong><em>✓ سوابق مشخص</em></div>
                        <div class="stat"><small>اطلاع‌رسانی</small><strong>سریع</strong><em>✓ در دسترس</em></div>
                    </div>
                    <div class="mock-chart"><div class="chart-head"><span>تصویر کلی امور ساختمان</span><span>نمایشی</span></div><div class="bars" aria-hidden="true"><i style="height:40%"></i><i style="height:68%"></i><i style="height:52%"></i><i style="height:83%"></i><i style="height:61%"></i><i style="height:94%"></i><i style="height:73%"></i><i style="height:86%"></i></div></div>
                </div>
                <div class="floating-note"><span class="floating-icon">✓</span><div><strong>همه‌چیز سر جای خودش</strong><small>از شارژ تا درخواست و پیگیری</small></div></div>
            </div>
        </div>
    </section>

    <div class="trust-strip"><div class="wrap trust-items">
        @foreach(data_get($content ?? [], 'trust_items', [['icon'=>'◈','text'=>'شفافیت مالی'],['icon'=>'✓','text'=>'پیگیری درخواست‌ها'],['icon'=>'⌂','text'=>'مناسب انواع ساختمان'],['icon'=>'↗','text'=>'دسترسی ساده و آنلاین']]) as $item)<div class="trust-item"><span>{{ $item['icon'] ?? '✓' }}</span> {{ $item['text'] ?? '' }}</div>@endforeach
    </div></div>

    <section class="content-section" id="features">
        <div class="wrap">
            <div class="section-head"><span class="section-kicker">امکانات صابخونه</span><h2>{{ data_get($content ?? [], 'features_title', 'همه‌ی کارهای ساختمان، در یک تجربه‌ی یکپارچه') }}</h2><p>{{ data_get($content ?? [], 'features_description', 'ابزارهایی برای کمتر شدن کارهای تکراری، روشن‌تر شدن وضعیت‌ها و ارتباط بهتر میان اعضای ساختمان.') }}</p></div>
            <div class="feature-grid">
                @foreach($features as $feature)
                <article class="feature-card"><div class="feature-icon">{{ $feature['icon'] ?? '✦' }}</div><h3>{{ $feature['title'] ?? '' }}</h3><p>{{ $feature['description'] ?? '' }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="content-section how">
        <div class="wrap">
            <div class="section-head"><span class="section-kicker">شروعی ساده</span><h2>از آشفتگی روزمره تا مدیریت منظم</h2><p>صابخونه برای آن طراحی شده که مسیر مدیریت ساختمان روشن و قابل پیگیری باشد.</p></div>
            <div class="steps">
                @foreach(data_get($content ?? [], 'steps', [['title'=>'حساب خود را بسازید','description'=>'با شماره موبایل وارد شوید و اطلاعات اولیه‌ی حساب خود را تکمیل کنید.'],['title'=>'ساختمان را راه‌اندازی کنید','description'=>'ساختمان، بلوک‌ها و واحدها را تعریف کنید و اعضا و مسئولیت‌ها را مشخص کنید.'],['title'=>'همه‌چیز را یکپارچه پیگیری کنید','description'=>'شارژ، پرداخت‌ها، درخواست‌ها و برنامه‌های ساختمان را از یک سامانه مدیریت کنید.']]) as $i=>$step)<article class="step"><div class="step-number">{{ $i+1 }}</div><h3>{{ $step['title'] ?? '' }}</h3><p>{{ $step['description'] ?? '' }}</p></article>@endforeach
            </div>
        </div>
    </section>

    <section class="content-section" id="about">
        <div class="wrap about-grid">
            <div class="about-visual"><div class="about-note"><strong>{{ data_get($content ?? [], 'about.note_title', 'مدیریت بهتر، با اطلاعات روشن‌تر') }}</strong><p>{{ data_get($content ?? [], 'about.note_description', 'وقتی وضعیت‌ها مشخص باشند، تصمیم‌گیری و همکاری هم ساده‌تر می‌شود.') }}</p></div></div>
            <div class="about-copy"><span class="section-kicker">{{ data_get($content ?? [], 'about.eyebrow', 'درباره صابخونه') }}</span><h2>{{ data_get($content ?? [], 'about.title', 'ساختمان بهتر، با همکاری شفاف‌تر') }}</h2><p>{{ data_get($content ?? [], 'about.paragraph1', 'صابخونه یک سامانه‌ی فارسی برای سامان‌دهی کارهای روزمره‌ی ساختمان است؛ فضایی مشترک که مدیران، ساکنان و پرسنل بتوانند امور مرتبط را متناسب با نقش خود پیگیری کنند.') }}</p><p>{{ data_get($content ?? [], 'about.paragraph2', 'هدف ما ساده است: کاهش سردرگمی، روشن‌تر شدن سوابق و فراهم کردن تجربه‌ای منظم‌تر برای زندگی و کار در ساختمان.') }}</p><ul class="check-list">@foreach(data_get($content ?? [], 'about.points', ['اطلاعات و مسئولیت‌ها در مسیرهای مشخص و قابل پیگیری','تجربه‌ی فارسی و طراحی‌شده برای نیازهای مدیریت ساختمان','دسترسی متناسب با نقش هر کاربر در ساختمان']) as $point)<li><span class="check">✓</span><span>{{ $point }}</span></li>@endforeach</ul></div>
        </div>
    </section>

    <section class="content-section" id="articles" style="background:#f0f6f4">
        <div class="wrap"><div class="section-head"><span class="section-kicker">دانش‌نامه‌ی صابخونه</span><h2>{{ data_get($content ?? [], 'articles_title', 'مطالبی برای مدیریت بهتر ساختمان') }}</h2><p>{{ data_get($content ?? [], 'articles_description', 'راهنماهای کوتاه درباره‌ی موضوعاتی که هر مدیر و ساکن ساختمان با آن‌ها روبه‌رو می‌شود.') }}</p></div>
            <div class="article-grid">
                @forelse($articles as $article)
                <article class="article-card"><a href="{{ route('marketing.articles.show', $article->slug) }}" aria-label="مطالعهٔ {{ $article->title }}"><div class="article-art" aria-hidden="true">@if($article->cover_image)<img src="{{ preg_match('~^https?://|^/~i', $article->cover_image) ? $article->cover_image : \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}" alt="" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover;position:relative;z-index:1">@else 📚 @endif</div></a><div class="article-body"><span class="article-tag">دانش‌نامهٔ صابخونه</span><h3><a href="{{ route('marketing.articles.show', $article->slug) }}">{{ $article->title }}</a></h3><p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body), 140) }}</p><a class="article-tag" href="{{ route('marketing.articles.show', $article->slug) }}">ادامهٔ مطلب ←</a></div></article>
                @empty
                <p>مقالات جدید به‌زودی در این بخش منتشر می‌شوند.</p>
                @endforelse
            </div>
            <div style="text-align:center;margin-top:24px"><a class="btn btn-light" href="{{ route('marketing.articles.index') }}">مشاهدهٔ همهٔ مقالات ←</a></div>
        </div>
    </section>

    <section class="partners" id="ads">
        <div class="wrap"><div class="partner-panel"><div><span class="mini-label">{{ data_get($content ?? [], 'ads.eyebrow', 'تبلیغات و معرفی خدمات') }}</span><h2>{{ data_get($content ?? [], 'ads.title', 'کسب‌وکار شما به جامعه‌ی ساختمان‌ها خدمت می‌کند؟') }}</h2><p>{{ data_get($content ?? [], 'ads.description', 'این بخش برای معرفی خدمات مرتبط با ساختمان، نگهداری، تعمیرات و زندگی آپارتمانی در نظر گرفته شده است. برای فعال‌سازی همکاری تبلیغاتی با ما در ارتباط باشید.') }}</p></div><div class="partner-actions"><a class="btn btn-primary" href="#contact">{{ data_get($content ?? [], 'ads.button', 'درخواست همکاری تبلیغاتی') }}</a></div></div></div>
    </section>

    <section class="content-section" id="partners" style="padding-top:0">
        <div class="wrap"><div class="partner-panel"><div><span class="mini-label">{{ data_get($content ?? [], 'partners.eyebrow', 'همکاران ما') }}</span><h2>{{ data_get($content ?? [], 'partners.title', 'با همکاری هم، مدیریت ساختمان را بهتر می‌کنیم') }}</h2><p>{{ data_get($content ?? [], 'partners.description', 'از شرکت‌های خدمات ساختمانی و تعمیر و نگهداری تا کسب‌وکارهای مرتبط؛ این بخش برای معرفی همکاران و راهکارهای مکمل صابخونه آماده شده است.') }}</p></div><div class="partner-actions"><a class="btn btn-light" href="#contact">{{ data_get($content ?? [], 'partners.button', 'پیشنهاد همکاری') }} <span aria-hidden="true">←</span></a></div></div></div>
    </section>

    <section class="content-section contact-section" id="contact">
        <div class="wrap contact-grid">
            <div><span class="section-kicker">{{ data_get($content ?? [], 'contact.eyebrow', 'تماس با ما') }}</span><h2>{{ data_get($content ?? [], 'contact.title', 'برای شروع همکاری، در ارتباط باشیم') }}</h2><p>{{ data_get($content ?? [], 'contact.description', 'برای استفاده از سامانه یا پیگیری امور حساب خود، از ورود به صابخونه شروع کنید.') }}</p><a class="btn btn-primary" href="{{ route('login') }}">ورود به صابخونه <span aria-hidden="true">←</span></a></div>
            <div class="contact-card">
                <div class="contact-row"><span class="contact-symbol">✉</span><div><strong>ارتباط با تیم صابخونه</strong>
                    @if(data_get($content ?? [], 'contact.email'))<a class="contact-email" href="mailto:{{ data_get($content, 'contact.email') }}">{{ data_get($content, 'contact.email') }}</a>@else<small>راه ارتباطی عمومی هنوز در پنل مدیریت ثبت نشده است.</small>@endif
                </div></div>
                @if(data_get($content ?? [], 'contact.phone'))<div class="contact-row"><span class="contact-symbol">☎</span><div><strong>شماره تماس</strong><a href="tel:{{ data_get($content, 'contact.phone') }}" dir="ltr">{{ data_get($content, 'contact.phone') }}</a></div></div>@endif
                <div class="contact-row"><span class="contact-symbol">◎</span><div><strong>{{ data_get($content ?? [], 'contact.support_title', 'پشتیبانی حساب کاربری') }}</strong><small>{{ data_get($content ?? [], 'contact.support_description', 'برای شروع، وارد سامانه شوید و از امکانات حساب خود استفاده کنید.') }}</small></div></div>
                <div class="contact-row"><span class="contact-symbol">↗</span><div><strong>همکاری و تبلیغات</strong><small>{{ data_get($content ?? [], 'partners.description', 'برای پیشنهاد همکاری از راه ارتباطی عمومی صابخونه استفاده کنید.') }}</small></div></div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap"><div class="footer-main">
        <div class="footer-brand"><a class="brand" href="#home"><span class="brand-mark" style="background:#fff;padding:5px;border:1px solid #e1eae8"><img src="{{ asset('images/sabkhoneh-mark.png') }}" alt="" style="display:block;width:100%;height:100%;object-fit:contain"></span><span>صابخونه</span></a><p>{{ data_get($content ?? [], 'footer_description', 'سامانه‌ی فارسی مدیریت ساختمان؛ برای شفافیت بیشتر، پیگیری ساده‌تر و همکاری بهتر.') }}</p>@if(data_get($content ?? [], 'enamad.enabled') && (data_get($content ?? [], 'enamad.image_path') || data_get($content ?? [], 'enamad.image_url')) && data_get($content ?? [], 'enamad.verification_url'))<a class="footer-trust" href="{{ data_get($content, 'enamad.verification_url') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ data_get($content, 'enamad.alt', 'مشاهده نماد اعتماد الکترونیکی') }}"><img src="{{ data_get($content, 'enamad.image_path') ? route('marketing.enamad.image') : data_get($content, 'enamad.image_url') }}" alt="{{ data_get($content, 'enamad.alt', 'نماد اعتماد الکترونیکی') }}" loading="lazy"><span class="footer-trust-label"><strong>نماد اعتماد الکترونیکی</strong><span>برای مشاهدهٔ اعتبار نماد کلیک کنید</span></span></a>@endif</div>
        <nav class="footer-links" aria-label="پیوندهای پایین صفحه"><a href="#features">امکانات</a><a href="#about">درباره ما</a><a href="#articles">مقالات</a><a href="#ads">تبلیغات</a><a href="#partners">همکاران ما</a><a href="#contact">تماس با ما</a><a href="{{ route('login') }}">ورود</a></nav>
    </div><div class="footer-bottom"><span>© {{ date('Y') }} صابخونه — تمامی حقوق محفوظ است.</span><span>ساخته‌شده برای مدیریت شفاف‌تر ساختمان</span></div></div>
</footer>
</body>
</html>
