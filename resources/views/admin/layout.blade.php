<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'پنل مدیریت') | برجیک</title>
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
@if(file_exists(public_path('vendor/sweetalert2/sweetalert2.min.css')))
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap-toast.rtl.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/notify.css') }}">
<style>
  .admin-shell{display:grid;grid-template-columns:240px 1fr;min-height:100vh}
  .admin-side{background:var(--card,#fff);border-inline-end:1px solid var(--line,#dbe6e3);padding:20px 16px}
  .admin-side .brand{display:flex;gap:10px;align-items:center;margin-bottom:24px}
  .admin-side .brand .logo{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#2a9b8f,#1f6a63);color:#fff;display:grid;place-items:center;font-weight:800}
  .admin-side nav a{display:block;padding:10px 12px;border-radius:12px;color:inherit;text-decoration:none;margin-bottom:4px;font-size:14px}
  .admin-side nav a.on,.admin-side nav a:hover{background:var(--brand-soft,#e8f3f1)}
  .admin-main{padding:24px;max-width:1100px}
  @media(max-width:800px){.admin-shell{grid-template-columns:1fr}.admin-side{border:0;border-bottom:1px solid var(--line)}}
</style>
</head>
<body>
@if(session('status'))<div data-borjik-flash="status" hidden>{{ session('status') }}</div>@endif
@if(session('success'))<div data-borjik-flash="success" hidden>{{ session('success') }}</div>@endif
@if(session('warning'))<div data-borjik-flash="warning" hidden>{{ session('warning') }}</div>@endif
@if(session('error'))<div data-borjik-flash="error" hidden>{{ session('error') }}</div>@endif

<div class="admin-shell">
  <aside class="admin-side">
    <div class="brand"><div class="logo">ب</div><div><b>برجیک</b><div style="font-size:12px;color:var(--muted)">پنل مدیریت سیستم</div></div></div>
    <nav>
      <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])>داشبورد</a>
      @php $adm = auth('admin')->user(); @endphp
      @if($adm->hasPermission('plans.manage'))<a href="{{ route('admin.plans.index') }}" @class(['on' => request()->routeIs('admin.plans.*')])>پلن‌های اشتراک</a>@endif
      @if($adm->hasPermission('admin.users.manage'))<a href="{{ route('admin.users.index') }}" @class(['on' => request()->routeIs('admin.users.*')])>کاربران ادمین</a>@endif
      @if($adm->hasPermission('admin.roles.manage'))<a href="{{ route('admin.roles.index') }}" @class(['on' => request()->routeIs('admin.roles.*')])>نقش‌ها و دسترسی‌ها</a>@endif
    </nav>
    <form method="POST" action="{{ route('admin.logout') }}" style="margin-top:24px">@csrf
      <button class="btn sec sm" type="submit" style="width:100%;justify-content:center">خروج</button>
    </form>
    <div style="margin-top:12px;font-size:12px;color:var(--muted)">{{ auth('admin')->user()->name ?: auth('admin')->user()->username }}</div>
  </aside>
  <main class="admin-main">
    <h1 style="margin:0 0 18px;font-size:1.35rem">@yield('title')</h1>
    @yield('content')
  </main>
</div>

@if(file_exists(public_path('vendor/sweetalert2/sweetalert2.all.min.js')))
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
@endif
<script src="{{ asset('js/ui.js') }}"></script>
</body>
</html>
