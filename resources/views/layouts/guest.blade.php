<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1f6a63">
<title>@yield('title', 'برجیک')</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
<style>
:root{--brand:#2a9b8f;--brand-d:#1f6a63;--text:#17302d;--muted:#667a77;--card:rgba(255,255,255,.72);--line:#d5e0dd;--bg1:#d9e6e3;--bg2:#eef2f0}
@media (prefers-color-scheme:dark){:root{--text:#f1f8f6;--muted:#a9c0bc;--card:rgba(24,43,41,.8);--line:#35544f;--bg1:#08110f;--bg2:#0f1d1b;--brand:#3cc2b3}}
*{box-sizing:border-box;margin:0}
body{font-family:'Vazirmatn',Tahoma,sans-serif;color:var(--text);min-height:100vh;display:grid;place-items:center;padding:20px;
  background:radial-gradient(60% 45% at 85% 8%,rgba(42,155,143,.28),transparent 70%),linear-gradient(135deg,var(--bg1),var(--bg2)) fixed}
.card{width:min(420px,100%);background:var(--card);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.4);border-radius:24px;padding:28px;box-shadow:0 10px 30px rgba(31,106,99,.15)}
.logo{width:52px;height:52px;border-radius:15px;background:linear-gradient(135deg,var(--brand),var(--brand-d));color:#fff;display:grid;place-items:center;font-weight:800;font-size:22px;margin-bottom:14px}
h1{font-size:19px;margin-bottom:6px}p.sub{color:var(--muted);font-size:13px;margin-bottom:18px}
label{display:block;font-size:13px;margin:12px 0 6px}
input,select,textarea{width:100%;padding:12px 14px;border-radius:12px;border:1px solid var(--line);background:rgba(255,255,255,.6);font:inherit;color:inherit}
input:focus,select:focus{outline:2px solid var(--brand);outline-offset:1px}
button{width:100%;margin-top:16px;padding:13px;border:0;border-radius:12px;background:linear-gradient(135deg,var(--brand),var(--brand-d));color:#fff;font:inherit;font-weight:700;cursor:pointer}
.err{color:#e0453a;font-size:12.5px;margin-top:6px}.ok{background:rgba(42,155,143,.15);padding:10px 12px;border-radius:10px;font-size:13px;margin-bottom:12px}
.link{background:none;color:var(--brand);margin-top:10px;font-weight:500;padding:6px}
.tabs{display:flex;gap:8px;margin-bottom:6px}.tabs button{margin:0;background:rgba(42,155,143,.12);color:var(--text)}.tabs button.on{background:var(--brand-d);color:#fff}
code{direction:ltr;display:block;word-break:break-all;background:rgba(0,0,0,.06);padding:10px;border-radius:10px;font-size:12px;margin:8px 0}
</style>
</head>
<body>
<main class="card">
  <div class="logo">ب</div>
  @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
  @if(session('warning'))<div class="err">{{ session('warning') }}</div>@endif
  @yield('content')
</main>
</body>
</html>
