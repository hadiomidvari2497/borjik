<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#2a9b8f">
<meta name="color-scheme" content="light">
<title>@yield('title', 'برجیک')</title>
<link rel="preload" href="{{ asset('fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
<link rel="stylesheet" href="{{ asset('css/jdate.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap-toast.rtl.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/notify.css') }}">
</head>
<body class="auth">
@if(session('status'))<div data-borjik-flash="status" hidden>{{ session('status') }}</div>@endif
@if(session('success'))<div data-borjik-flash="success" hidden>{{ session('success') }}</div>@endif
@if(session('warning'))<div data-borjik-flash="warning" hidden>{{ session('warning') }}</div>@endif
@if(session('error'))<div data-borjik-flash="error" hidden>{{ session('error') }}</div>@endif
@if($errors->has('general'))<div data-borjik-flash="error" hidden>{{ $errors->first('general') }}</div>@endif
<div class="shell">
  <aside class="brand-panel">
    <div class="bp-top"><div class="logo">ب</div><b>برجیک</b></div>
    <div class="bp-mid">
      <h2>مدیریت ساختمان،<br>ساده و شفاف</h2>
      <p>شارژ، پرداخت، درخواست‌ها و اطلاع‌رسانی ساختمان شما در یک سامانه‌ی فارسی.</p>
      <ul class="bp-list">
        <li><i>✓</i><div><b>شارژ خودکار</b><span>۱۰ روش محاسبه و صدور صورت‌حساب با یک کلیک</span></div></li>
        <li><i>✓</i><div><b>پرداخت و تأیید فیش</b><span>وضعیت مالی هر واحد همیشه به‌روز</span></div></li>
        <li><i>✓</i><div><b>برای مدیران و ساکنین</b><span>هر کس فقط چیزی را می‌بیند که به او مربوط است</span></div></li>
      </ul>
    </div>
    <div class="bp-foot">© برجیک — سامانه‌ی مدیریت ساختمان</div>
  </aside>

  <main class="form-panel">
    <div class="fp-inner @yield('wide')">
      @yield('content')
    </div>
  </main>
</div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('js/ui.js') }}"></script>
<script src="{{ asset('js/jdate.js') }}"></script>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
