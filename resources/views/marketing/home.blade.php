<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b2434">
    <meta name="description" content="برجیک؛ سامانه فارسی مدیریت ساختمان برای شارژ، پرداخت‌ها، درخواست‌ها، اطلاع‌رسانی و ارتباط بهتر میان مدیران و ساکنان.">
    <title>برجیک | مدیریت ساختمان، با آرامش بیشتر</title>
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
        footer{background:#091f2c;color:#dce8e9;padding:35px 0 25px}.footer-main{display:flex;align-items:flex-start;justify-content:space-between;gap:35px;padding-bottom:26px}.footer-brand p{color:#a7bbc2;font-size:.83rem;max-width:360px;margin:13px 0 0}.footer-links{display:flex;flex-wrap:wrap;gap:18px;font-size:.83rem;color:#c0d0d4}.footer-links a:hover{color:#7de0cd}.footer-bottom{border-top:1px solid #ffffff19;padding-top:17px;display:flex;justify-content:space-between;gap:15px;color:#9bb0b8;font-size:.77rem}
        @media(max-width:900px){.nav-links{gap:12px}.hero-grid{gap:25px}.dashboard-art{min-height:360px}.orb{width:270px;height:270px}.mock-window{padding:16px}.floating-note{left:-5px}.about-grid{gap:30px}.partner-panel{grid-template-columns:1fr}.partner-actions{margin-top:5px}}
        @media(max-width:700px){.wrap{width:min(100% - 28px,560px)}.nav{min-height:70px;flex-wrap:wrap;padding:10px 0}.nav-links{order:3;width:100%;overflow-x:auto;white-space:nowrap;padding:3px 0 7px;font-size:.82rem}.nav-actions .btn-ghost{display:none}.brand{font-size:1.12rem}.brand-mark{width:38px;height:38px}.nav-actions .btn{padding:9px 12px;font-size:.82rem}.hero{padding:50px 0 35px}.hero-grid{grid-template-columns:1fr;gap:12px}.hero-copy{font-size:1rem}.dashboard-art{min-height:350px;margin-top:3px}.orb{right:10%;width:280px;height:280px}.mock-window{width:min(430px,94%);transform:none}.floating-note{left:0;bottom:13px}.trust-items{grid-template-columns:repeat(2,1fr);gap:13px;padding:18px 0}.trust-item{justify-content:flex-start;font-size:.78rem}.trust-item span{width:31px;height:31px}.content-section,section.content-section{padding:60px 0}.feature-grid,.steps,.article-grid{grid-template-columns:1fr}.feature-card{min-height:0}.section-head{margin-bottom:27px}.about-grid,.contact-grid{grid-template-columns:1fr;gap:27px}.about-visual{min-height:260px}.about-copy h2,.contact-grid h2{font-size:1.7rem}.partner-panel{padding:25px}.partner-panel h2{font-size:1.35rem}.footer-main,.footer-bottom{flex-direction:column}.footer-main{gap:24px}.footer-links{gap:13px}.footer-bottom{gap:5px}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{transition:none!important}}
    </style>
</head>
<body>
<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="#home" aria-label="برجیک، صفحه اصلی"><span class="brand-mark">ب</span><span>برجیک</span></a>
        <nav class="nav-links" aria-label="ناوبری اصلی">
            <a href="#features">امکانات</a><a href="#about">درباره ما</a><a href="#articles">مقالات</a><a href="#ads">تبلیغات</a><a href="#partners">همکاران ما</a><a href="#contact">تماس با ما</a>
        </nav>
        <div class="nav-actions"><a class="btn btn-ghost" href="#features">آشنایی با برجیک</a><a class="btn btn-primary" href="{{ route('login') }}">ورود به سامانه <span aria-hidden="true">←</span></a></div>
    </div>
</header>

<main>
    <section class="hero" id="home">
        <div class="wrap hero-grid">
            <div>
                <span class="eyebrow"><i></i> مدیریت نوین ساختمان، در یک جا</span>
                <h1>مدیریت ساختمان،<br><span>با آرامش بیشتر</span></h1>
                <p class="hero-copy">برجیک کمک می‌کند امور مالی، شارژ، درخواست‌های ساکنان و ارتباطات ساختمان را شفاف‌تر و منظم‌تر مدیریت کنید؛ بدون کاغذبازی و سردرگمی.</p>
                <div class="hero-actions"><a class="btn btn-primary" href="{{ route('login') }}">شروع استفاده از برجیک <span aria-hidden="true">←</span></a><a class="btn btn-light" href="#features">امکانات را ببینید <span aria-hidden="true">⌄</span></a></div>
                <div class="hero-proof"><div class="proof-avatars" aria-hidden="true"><span>م</span><span>س</span><span>ب</span></div><div><strong>همه‌چیز شفاف‌تر</strong><br>برای مدیران، ساکنان و پرسنل ساختمان</div></div>
            </div>
            <div class="dashboard-art" aria-label="پیش‌نمایش تصویری داشبورد برجیک">
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
        <div class="trust-item"><span>◈</span> شفافیت مالی</div><div class="trust-item"><span>✓</span> پیگیری درخواست‌ها</div><div class="trust-item"><span>⌂</span> مناسب انواع ساختمان</div><div class="trust-item"><span>↗</span> دسترسی ساده و آنلاین</div>
    </div></div>

    <section class="content-section" id="features">
        <div class="wrap">
            <div class="section-head"><span class="section-kicker">امکانات برجیک</span><h2>همه‌ی کارهای ساختمان،<br>در یک تجربه‌ی یکپارچه</h2><p>ابزارهایی برای کمتر شدن کارهای تکراری، روشن‌تر شدن وضعیت‌ها و ارتباط بهتر میان اعضای ساختمان.</p></div>
            <div class="feature-grid">
                <article class="feature-card"><div class="feature-icon">◉</div><h3>محاسبه و صدور شارژ</h3><p>قواعد شارژ و هزینه‌های ساختمان را مدیریت کنید و سهم هر واحد را با روشی روشن محاسبه کنید.</p></article>
                <article class="feature-card"><div class="feature-icon">⇄</div><h3>پرداخت و پیگیری مالی</h3><p>صورتحساب‌ها، ثبت پرداخت و وضعیت تأیید فیش‌ها را در یک مسیر مشخص دنبال کنید.</p></article>
                <article class="feature-card"><div class="feature-icon">☷</div><h3>درخواست‌ها و وظایف</h3><p>درخواست‌های ساکنان را ثبت، ارجاع و پیگیری کنید تا هیچ موردی میان پیام‌ها گم نشود.</p></article>
                <article class="feature-card"><div class="feature-icon">♧</div><h3>مدیریت اعضا و پرسنل</h3><p>نقش‌ها و دسترسی‌ها را متناسب با مسئولیت مدیر، ساکن و پرسنل سازمان‌دهی کنید.</p></article>
                <article class="feature-card"><div class="feature-icon">▦</div><h3>تقویم و اطلاع‌رسانی</h3><p>رویدادها و برنامه‌های ساختمان را منظم نگه دارید و ارتباطات را از مسیرهای مشخص پیش ببرید.</p></article>
                <article class="feature-card"><div class="feature-icon">▤</div><h3>اسناد و سوابق</h3><p>به اطلاعات و سوابق موردنیاز دسترسی داشته باشید و تصمیم‌ها را با دید روشن‌تری بگیرید.</p></article>
            </div>
        </div>
    </section>

    <section class="content-section how">
        <div class="wrap">
            <div class="section-head"><span class="section-kicker">شروعی ساده</span><h2>از آشفتگی روزمره تا مدیریت منظم</h2><p>برجیک برای آن طراحی شده که مسیر مدیریت ساختمان روشن و قابل پیگیری باشد.</p></div>
            <div class="steps">
                <article class="step"><div class="step-number">۱</div><h3>حساب خود را بسازید</h3><p>با شماره موبایل وارد شوید و اطلاعات اولیه‌ی حساب خود را تکمیل کنید.</p></article>
                <article class="step"><div class="step-number">۲</div><h3>ساختمان را راه‌اندازی کنید</h3><p>ساختمان، بلوک‌ها و واحدها را تعریف کنید و اعضا و مسئولیت‌ها را مشخص کنید.</p></article>
                <article class="step"><div class="step-number">۳</div><h3>همه‌چیز را یکپارچه پیگیری کنید</h3><p>شارژ، پرداخت‌ها، درخواست‌ها و برنامه‌های ساختمان را از یک سامانه مدیریت کنید.</p></article>
            </div>
        </div>
    </section>

    <section class="content-section" id="about">
        <div class="wrap about-grid">
            <div class="about-visual"><div class="about-note"><strong>مدیریت بهتر، با اطلاعات روشن‌تر</strong><p>وقتی وضعیت‌ها مشخص باشند، تصمیم‌گیری و همکاری هم ساده‌تر می‌شود.</p></div></div>
            <div class="about-copy"><span class="section-kicker">درباره برجیک</span><h2>ساختمان بهتر، با همکاری شفاف‌تر</h2><p>برجیک یک سامانه‌ی فارسی برای سامان‌دهی کارهای روزمره‌ی ساختمان است؛ فضایی مشترک که مدیران، ساکنان و پرسنل بتوانند امور مرتبط را متناسب با نقش خود پیگیری کنند.</p><p>هدف ما ساده است: کاهش سردرگمی، روشن‌تر شدن سوابق و فراهم کردن تجربه‌ای منظم‌تر برای زندگی و کار در ساختمان.</p><ul class="check-list"><li><span class="check">✓</span><span>اطلاعات و مسئولیت‌ها در مسیرهای مشخص و قابل پیگیری</span></li><li><span class="check">✓</span><span>تجربه‌ی فارسی و طراحی‌شده برای نیازهای مدیریت ساختمان</span></li><li><span class="check">✓</span><span>دسترسی متناسب با نقش هر کاربر در ساختمان</span></li></ul></div>
        </div>
    </section>

    <section class="content-section" id="articles" style="background:#f0f6f4">
        <div class="wrap"><div class="section-head"><span class="section-kicker">دانش‌نامه‌ی برجیک</span><h2>مطالبی برای مدیریت بهتر ساختمان</h2><p>راهنماهای کوتاه درباره‌ی موضوعاتی که هر مدیر و ساکن ساختمان با آن‌ها روبه‌رو می‌شود.</p></div>
            <div class="article-grid">
                <article class="article-card"><div class="article-art" aria-hidden="true">🧾</div><div class="article-body"><span class="article-tag">مدیریت مالی</span><h3>چطور محاسبه‌ی شارژ را شفاف‌تر کنیم؟</h3><p>آشنایی با اهمیت قواعد روشن، ثبت هزینه‌ها و دسترسی اعضا به سوابق مالی ساختمان.</p></div></article>
                <article class="article-card"><div class="article-art" aria-hidden="true">🛠️</div><div class="article-body"><span class="article-tag">رسیدگی و نگهداری</span><h3>هیچ درخواست ساکنی نباید گم شود</h3><p>چرا ثبت، ارجاع و پیگیری منظم درخواست‌ها به بهبود تجربه‌ی ساکنان کمک می‌کند.</p></div></article>
                <article class="article-card"><div class="article-art" aria-hidden="true">🤝</div><div class="article-body"><span class="article-tag">ارتباطات</span><h3>نقش ارتباط شفاف در ساختمان آرام‌تر</h3><p>چند اصل ساده برای اطلاع‌رسانی بهتر و همکاری مؤثرتر میان مدیر و ساکنان.</p></div></article>
            </div>
        </div>
    </section>

    <section class="partners" id="ads">
        <div class="wrap"><div class="partner-panel"><div><span class="mini-label">تبلیغات و معرفی خدمات</span><h2>کسب‌وکار شما به جامعه‌ی ساختمان‌ها خدمت می‌کند؟</h2><p>این بخش برای معرفی خدمات مرتبط با ساختمان، نگهداری، تعمیرات و زندگی آپارتمانی در نظر گرفته شده است. برای فعال‌سازی همکاری تبلیغاتی با ما در ارتباط باشید.</p></div><div class="partner-actions"><a class="btn btn-primary" href="#contact">درخواست همکاری تبلیغاتی</a></div></div></div>
    </section>

    <section class="content-section" id="partners" style="padding-top:0">
        <div class="wrap"><div class="partner-panel"><div><span class="mini-label">همکاران ما</span><h2>با همکاری هم، مدیریت ساختمان را بهتر می‌کنیم</h2><p>از شرکت‌های خدمات ساختمانی و تعمیر و نگهداری تا کسب‌وکارهای مرتبط؛ این بخش برای معرفی همکاران و راهکارهای مکمل برجیک آماده شده است.</p></div><div class="partner-actions"><a class="btn btn-light" href="#contact">پیشنهاد همکاری <span aria-hidden="true">←</span></a></div></div></div>
    </section>

    <section class="content-section contact-section" id="contact">
        <div class="wrap contact-grid">
            <div><span class="section-kicker">تماس با ما</span><h2>برای شروع همکاری،<br>در ارتباط باشیم</h2><p>برای استفاده از سامانه یا پیگیری امور حساب خود، از ورود به برجیک شروع کنید. راه ارتباطی عمومی تیم برجیک نیز در صورت پیکربندی در سامانه نمایش داده می‌شود.</p><a class="btn btn-primary" href="{{ route('login') }}">ورود به برجیک <span aria-hidden="true">←</span></a></div>
            <div class="contact-card">
                <div class="contact-row"><span class="contact-symbol">✉</span><div><strong>ارتباط با تیم برجیک</strong>
                    @if(config('mail.from.address'))
                        <a class="contact-email" href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
                    @else
                        <small>راه ارتباطی عمومی هنوز در تنظیمات سامانه ثبت نشده است.</small>
                    @endif
                </div></div>
                <div class="contact-row"><span class="contact-symbol">◎</span><div><strong>پشتیبانی حساب کاربری</strong><small>برای شروع، وارد سامانه شوید و از امکانات حساب خود استفاده کنید.</small></div></div>
                <div class="contact-row"><span class="contact-symbol">↗</span><div><strong>همکاری و تبلیغات</strong><small>برای پیشنهاد همکاری، از راه ارتباطی عمومی اعلام‌شده توسط برجیک استفاده کنید.</small></div></div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap"><div class="footer-main">
        <div class="footer-brand"><a class="brand" href="#home"><span class="brand-mark">ب</span><span>برجیک</span></a><p>سامانه‌ی فارسی مدیریت ساختمان؛ برای شفافیت بیشتر، پیگیری ساده‌تر و همکاری بهتر.</p></div>
        <nav class="footer-links" aria-label="پیوندهای پایین صفحه"><a href="#features">امکانات</a><a href="#about">درباره ما</a><a href="#articles">مقالات</a><a href="#ads">تبلیغات</a><a href="#partners">همکاران ما</a><a href="#contact">تماس با ما</a><a href="{{ route('login') }}">ورود</a></nav>
    </div><div class="footer-bottom"><span>© {{ date('Y') }} برجیک — تمامی حقوق محفوظ است.</span><span>ساخته‌شده برای مدیریت شفاف‌تر ساختمان</span></div></div>
</footer>
</body>
</html>
