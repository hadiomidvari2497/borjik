<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#143a37">
<meta name="color-scheme" content="light">
<title>@yield('title', 'پنل مدیریت سیستم') | صابخونه</title>
<link rel="preload" href="{{ asset('fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap-toast.rtl.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/notify.css') }}">
<style>
  /* پنل ورود مدیر سیستم: تیره‌تر از ورود کاربران تا به‌وضوح یک پنل جداست */
  .brand-panel{background:linear-gradient(160deg,#1c4f4a 0%,#122f3a 100%)}
  .badge-admin{display:inline-block;font-size:12px;padding:5px 12px;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);margin-bottom:14px}
  .check{display:flex;gap:8px;align-items:center;font-size:13px;margin:2px 0 18px;cursor:pointer}
  .check input{width:16px;height:16px;accent-color:var(--brand);margin:0}
</style>
</head>
<body class="auth">
@if(session('status'))<div data-borjik-flash="status" hidden>{{ session('status') }}</div>@endif
@if(session('success'))<div data-borjik-flash="success" hidden>{{ session('success') }}</div>@endif
@if(session('warning'))<div data-borjik-flash="warning" hidden>{{ session('warning') }}</div>@endif
@if(session('error'))<div data-borjik-flash="error" hidden>{{ session('error') }}</div>@endif
<div class="shell">
  <aside class="brand-panel">
    <div class="bp-top"><div class="logo"><img src="{{ asset('images/sabkhoneh-mark-white.svg') }}" alt="" width="40" height="40"><b>صابخونه</b></div>
    <div class="bp-mid">
      <span class="badge-admin">ویژه‌ی مدیران سامانه</span>
      <h2>پنل مدیریت سیستم</h2>
      <p>مدیریت سراسری سامانه‌ی صابخونه؛ جدا از پنل ساختمان‌ها و فقط برای کاربران ادمین.</p>
      <ul class="bp-list">
        <li><i>✓</i><div><b>اشتراک و تعرفه‌ها</b><span>پلن‌ها، قیمت و اشتراک کاربران</span></div></li>
        <li><i>✓</i><div><b>پیامک و تنظیمات سایت</b><span>روش‌های ورود، پنل پیامکی و اطلاعات پشتیبانی</span></div></li>
        <li><i>✓</i><div><b>نظارت و دسترسی‌ها</b><span>کاربران ادمین، نقش‌ها و مجوزها</span></div></li>
      </ul>
    </div>
    <div class="bp-foot">© صابخونه — پنل مدیریت سیستم</div>
  </aside>

  <main class="form-panel">
    <div class="fp-inner">
      @yield('content')
    </div>
  </main>
</div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('js/ui.js') }}"></script>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
