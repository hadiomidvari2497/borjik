<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b2434">
    @php
        $seoBase = rtrim((string) \App\Models\SiteSetting::get('seo.site_url', config('app.url')), '/');
        $seoScheme = strtolower((string) parse_url($seoBase, PHP_URL_SCHEME));
        $seoHost = strtolower((string) parse_url($seoBase, PHP_URL_HOST));
        $seoPublic = filter_var($seoBase, FILTER_VALIDATE_URL) && $seoScheme === 'https'
            && !in_array($seoHost, ['localhost', '127.0.0.1', '::1'], true)
            && empty(parse_url($seoBase, PHP_URL_USER)) && empty(parse_url($seoBase, PHP_URL_PASS))
            && in_array((string) parse_url($seoBase, PHP_URL_PATH), ['', '/'], true)
            && parse_url($seoBase, PHP_URL_QUERY) === null && parse_url($seoBase, PHP_URL_FRAGMENT) === null;
        $seoIndexable = $seoPublic && app()->environment('production') && (bool) \App\Models\SiteSetting::get('seo.indexing_enabled', false);
        $seoTitle = 'نرم‌افزار شارژ ساختمان و مدیریت هزینه‌ها | صابخونه';
        $seoDescription = 'با صابخونه، شارژ و هزینه‌های ساختمان را منظم‌تر مدیریت کنید؛ وضعیت پرداخت‌ها را پیگیری کنید و اطلاعات مالی را شفاف‌تر در اختیار اعضای مجاز ساختمان قرار دهید.';
        $seoUrl = $seoBase.'/software-building-charges';
        $faqItems = [
            ['q' => 'نرم‌افزار شارژ ساختمان چه کمکی می‌کند؟', 'a' => 'به مدیر ساختمان کمک می‌کند اطلاعات مالی و شارژها را منظم‌تر پیگیری کند و اعضای مجاز بتوانند وضعیت‌ها و اطلاعیه‌های مرتبط را در سامانه دنبال کنند.'],
            ['q' => 'آیا صابخونه فقط برای محاسبه شارژ است؟', 'a' => 'خیر؛ صابخونه برای مدیریت امور ساختمان طراحی شده و علاوه بر امور مالی، امکاناتی مانند پیگیری درخواست‌های ساکنان و اطلاع‌رسانی را نیز در بر می‌گیرد. امکانات قابل استفاده به تنظیمات و دسترسی‌های هر ساختمان بستگی دارد.'],
            ['q' => 'چطور استفاده از صابخونه را شروع کنم؟', 'a' => 'از طریق ورود به سامانه شروع کنید و پس از تکمیل اطلاعات حساب، ساختمان و واحدها را طبق نیاز خود تعریف کنید.'],
            ['q' => 'چطور هزینه‌های ساختمان را شفاف‌تر کنیم؟', 'a' => 'هزینه‌ها و پرداخت‌ها را به‌صورت منظم ثبت کنید، شرح هر مورد را روشن بنویسید، گزارش‌ها را در زمان مناسب به اشتراک بگذارید و دسترسی به اطلاعات را بر اساس نقش کاربران تنظیم کنید.']
        ];
        $serviceSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $seoTitle,
            'description' => $seoDescription,
            'url' => $seoUrl,
            'inLanguage' => 'fa-IR',
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'صابخونه', 'url' => $seoBase.'/'],
            'about' => ['@type' => 'Thing', 'name' => 'نرم‌افزار شارژ و مدیریت هزینه‌های ساختمان']
        ];
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoIndexable ? 'index,follow' : 'noindex,nofollow' }}">
    @if($seoPublic && app()->environment('production'))<link rel="canonical" href="{{ $seoUrl }}">@endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="صابخونه">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoBase }}/images/og-sabkhoneh.png">
    <meta property="og:locale" content="fa_IR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoBase }}/images/og-sabkhoneh.png">
    @if($seoPublic && app()->environment('production'))
        <script type="application/ld+json">{!! json_encode($serviceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]], $faqItems)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    @endif
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <style>
        :root{--ink:#102b3a;--muted:#627582;--teal:#13a895;--deep:#087f78;--line:#dcebe6;--paper:#f3faf8}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--paper);color:var(--ink);font-family:Vazirmatn,Tahoma,sans-serif;line-height:2}a{color:inherit;text-decoration:none}.wrap{width:min(1080px,calc(100% - 32px));margin:auto}
        .top{background:radial-gradient(ellipse at 90% 0,rgba(34,197,170,.24),transparent 42%),linear-gradient(135deg,#071b28,#0b3b43);color:#fff;padding:20px 0 76px}.nav{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:54px}.brand{display:flex;align-items:center;gap:10px;font-weight:900;font-size:1.15rem}.brand img{width:42px;height:42px;object-fit:contain;background:#fff;border-radius:13px;padding:5px}.back{color:#9cebd9;font-weight:750;font-size:.9rem}.hero{max-width:800px}.eyebrow{display:inline-block;border:1px solid #73d9c344;background:#ffffff0b;border-radius:99px;padding:5px 13px;color:#9cebd9;font-size:.84rem;font-weight:800}h1{font-size:clamp(2rem,5vw,3.45rem);line-height:1.5;letter-spacing:-.7px;margin:15px 0;font-weight:950}.hero p{font-size:1.08rem;color:#c5dcdf;max-width:750px;margin-bottom:25px}.actions{display:flex;flex-wrap:wrap;gap:11px}.btn{display:inline-flex;align-items:center;justify-content:center;border-radius:13px;padding:11px 19px;font-weight:850}.primary{background:linear-gradient(135deg,#29c9ad,#087f78);color:white;box-shadow:0 12px 28px #13a89533}.secondary{border:1px solid #ffffff40;color:#fff}.section{padding:55px 0}.section h2{font-size:clamp(1.45rem,3vw,2.15rem);line-height:1.6;margin:0 0 13px}.lead{color:var(--muted);max-width:820px}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:25px}.card{background:#fff;border:1px solid var(--line);border-radius:19px;padding:22px;box-shadow:0 12px 32px #0b6b5c08}.card h3{font-size:1.04rem;margin:0 0 7px}.card p{font-size:.93rem;color:var(--muted);margin:0}.num{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:#e3f7f0;color:var(--deep);font-weight:900;margin-bottom:13px}.steps{counter-reset:step}.steps .card{position:relative}.steps .num{background:#fff2db;color:#95630c}.note{background:#e9f7f2;border:1px solid #ccece1;border-radius:20px;padding:23px;margin-top:25px}.faq{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.faq details{background:white;border:1px solid var(--line);border-radius:16px;padding:17px 19px}.faq summary{cursor:pointer;font-weight:850}.faq p{color:var(--muted);margin:10px 0 0;font-size:.94rem}.links{display:flex;flex-wrap:wrap;gap:12px 22px;margin-top:20px}.links a{color:var(--deep);font-weight:800;text-decoration:underline;text-underline-offset:4px}.bottom{background:#071c28;color:#b8d0d3;text-align:center;padding:25px 16px;font-size:.88rem}.bottom a{color:#9cebd9;text-decoration:underline}
        @media(max-width:760px){.grid{grid-template-columns:1fr}.faq{grid-template-columns:1fr}.top{padding-bottom:52px}.nav{margin-bottom:35px}.section{padding:40px 0}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
    </style>
</head>
<body>
<header class="top">
    <div class="wrap">
        <nav class="nav" aria-label="ناوبری اصلی">
            <a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/sabkhoneh-mark.png') }}" alt=""><span>صابخونه</span></a>
            <a class="back" href="{{ route('home') }}">← صفحهٔ اصلی</a>
        </nav>
        <div class="hero">
            <span class="eyebrow">مدیریت مالی ساختمان، منظم‌تر و شفاف‌تر</span>
            <h1>نرم‌افزار شارژ ساختمان برای پیگیری بهتر هزینه‌ها و پرداخت‌ها</h1>
            <p>وقتی اطلاعات شارژ و هزینه‌های مشترک پراکنده باشد، پاسخ‌دادن به پرسش‌های ساکنان و پیگیری پرداخت‌ها سخت می‌شود. صابخونه کمک می‌کند امور مالی ساختمان را در کنار درخواست‌ها و اطلاع‌رسانی‌های روزمره، در یک سامانه مدیریت کنید.</p>
            <div class="actions"><a class="btn primary" href="{{ route('login') }}">شروع استفاده از صابخونه ←</a><a class="btn secondary" href="#how-it-works">روش کار را ببینید</a></div>
        </div>
    </div>
</header>
<main>
    <section class="section">
        <div class="wrap">
            <h2>مدیریت شارژ ساختمان فقط دریافت پول نیست</h2>
            <p class="lead">مدیریت قابل پیگیری یعنی مبلغ‌ها و هزینه‌ها توضیح روشن داشته باشند، وضعیت پرداخت‌ها گم نشود و اطلاعات متناسب با مسئولیت هر فرد در دسترس باشد. ابزار مناسب، کارهای تکراری را کمتر و گفت‌وگو دربارهٔ حساب‌وکتاب را دقیق‌تر می‌کند.</p>
            <div class="grid">
                <article class="card"><div class="num">۱</div><h3>سامان‌دهی اطلاعات مالی</h3><p>اطلاعات شارژ و هزینه‌ها را منظم نگه دارید تا هنگام پیگیری یا گزارش‌گیری مجبور نباشید بین پیام‌ها و یادداشت‌های پراکنده بگردید.</p></article>
                <article class="card"><div class="num">۲</div><h3>پیگیری وضعیت پرداخت‌ها</h3><p>وضعیت‌های مالی را با نظم بیشتری دنبال کنید و برای پرسش‌های مربوط به شارژ، اطلاعات روشن‌تری در اختیار افراد مجاز داشته باشید.</p></article>
                <article class="card"><div class="num">۳</div><h3>ارتباط بهتر با ساکنان</h3><p>اطلاع‌رسانی دربارهٔ امور ساختمان را کنار روندهای روزمره نگه دارید تا ساکنان برای پیگیری موضوعات به مسیرهای پراکنده وابسته نباشند.</p></article>
            </div>
        </div>
    </section>
    <section class="section" id="how-it-works" style="background:#eaf5f1">
        <div class="wrap">
            <h2>چطور مدیریت شارژ را منظم‌تر کنیم؟</h2>
            <p class="lead">نرم‌افزار به‌تنهایی جایگزین تصمیم‌گیری درست یا ثبت دقیق اطلاعات نمی‌شود؛ نتیجهٔ بهتر از یک روال روشن و استفادهٔ منظم به دست می‌آید.</p>
            <div class="grid steps">
                <article class="card"><div class="num">۱</div><h3>ساختمان و واحدها را تعریف کنید</h3><p>اطلاعات پایه و دسترسی افراد را متناسب با نقش آن‌ها تنظیم کنید.</p></article>
                <article class="card"><div class="num">۲</div><h3>اطلاعات هزینه و شارژ را منظم ثبت کنید</h3><p>برای هر هزینه شرح قابل فهم و اطلاعات دقیق ثبت کنید و وضعیت‌ها را به‌روز نگه دارید.</p></article>
                <article class="card"><div class="num">۳</div><h3>گزارش دهید و پیگیری کنید</h3><p>اطلاعات مرتبط را در زمان مناسب با افراد مجاز به اشتراک بگذارید و پرسش‌ها را از مسیر مشخص دنبال کنید.</p></article>
            </div>
            <div class="note"><strong>نکته:</strong> قوانین ساختمان، توافق مالکان و مستأجران و تصمیم‌های مجمع باید مبنای تعیین شارژ باشند. نرم‌افزار ابزار ثبت و پیگیری است و جایگزین توافق‌ها یا مشاورهٔ حقوقی و حسابداری نیست.</div>
        </div>
    </section>
    <section class="section">
        <div class="wrap">
            <h2>چرا صابخونه برای مدیریت ساختمان؟</h2>
            <p class="lead">صابخونه برای سامان‌دهی امور روزمرهٔ ساختمان طراحی شده است؛ از امور مالی و اطلاع‌رسانی تا پیگیری درخواست‌های ساکنان. بخش‌هایی که هر کاربر می‌بیند به نقش و دسترسی تعریف‌شدهٔ او بستگی دارد.</p>
            <div class="links">
                <a href="{{ route('marketing.articles.index') }}">مطالعهٔ راهنماهای مدیریت ساختمان ←</a>
                <a href="{{ route('marketing.articles.show', 'transparent-building-charges') }}">چطور محاسبهٔ شارژ را شفاف‌تر کنیم؟ ←</a>
                <a href="{{ route('marketing.articles.show', 'resident-requests-follow-up') }}">پیگیری درخواست‌های ساکنان ←</a>
                <a href="{{ route('home') }}#features">مشاهدهٔ امکانات صابخونه ←</a>
            </div>
            <div class="note"><h2 style="font-size:1.3rem;margin:0 0 8px">برای مدیریت شارژ ساختمان آماده‌اید؟</h2><p style="margin:0 0 15px;color:var(--muted)">با ورود به صابخونه می‌توانید استفاده از سامانه را شروع کنید و امور ساختمان را در یک مسیر مشخص‌تر پیگیری کنید.</p><a class="btn primary" href="{{ route('login') }}">ورود به صابخونه ←</a></div>
        </div>
    </section>
    <section class="section" style="background:#eaf5f1">
        <div class="wrap">
            <h2>پرسش‌های رایج دربارهٔ نرم‌افزار شارژ ساختمان</h2>
            <div class="faq">
                @foreach($faqItems as $item)
                    <details><summary>{{ $item['q'] }}</summary><p>{{ $item['a'] }}</p></details>
                @endforeach
            </div>
        </div>
    </section>
</main>
<footer class="bottom"><div class="wrap">© {{ date('Y') }} صابخونه — <a href="{{ route('home') }}">مدیریت ساختمان، با آرامش بیشتر</a> · <a href="{{ route('marketing.articles.index') }}">مقالات و راهنماها</a></div></footer>
</body>
</html>
