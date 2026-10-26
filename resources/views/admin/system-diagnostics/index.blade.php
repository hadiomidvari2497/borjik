@extends('admin.layout')
@section('title', 'مرکز خطا و عیب‌یابی')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $okCount = collect($health)->where('status', 'ok')->count();
  $badCount = collect($health)->where('status', 'fail')->count();
@endphp
<style>
.sd-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:18px 0}.sd-stat{border:1px solid var(--line);border-radius:14px;padding:14px}.sd-stat small{display:block;color:var(--muted);font-size:12px;margin-bottom:7px}.sd-stat strong{font-size:24px}
.sd-health{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:16px 0}.sd-check{border:1px solid var(--line);border-radius:12px;padding:13px;min-width:0}.sd-check h4{margin:0 0 6px;font-size:14px}.sd-check p{font-size:12px;color:var(--muted);line-height:1.7;margin:0 0 8px}.sd-state{font-size:12px;font-weight:800}.sd-state.ok{color:#16804a}.sd-state.fail{color:#c0392b}
.sd-filters{display:grid;grid-template-columns:minmax(180px,2fr) repeat(3,minmax(120px,1fr));gap:10px;align-items:end}.sd-filters label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}.sd-filters input,.sd-filters select{width:100%;min-width:0}
.sd-muted{font-size:12px;color:var(--muted)}.sd-message{max-width:360px;white-space:normal;overflow-wrap:anywhere;font-size:12px}.sd-path{direction:ltr;text-align:right;overflow-wrap:anywhere;font-size:12px}.sd-date{white-space:nowrap;font-size:12px}.sd-copy{border:1px solid var(--line);background:transparent;border-radius:8px;padding:6px 9px;cursor:pointer;font:inherit;font-size:12px;white-space:nowrap}
.sd-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.sd-health-head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
@media(max-width:950px){.sd-health{grid-template-columns:repeat(2,minmax(0,1fr))}.sd-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.sd-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.sd-health,.sd-filters{grid-template-columns:1fr}.sd-stats{gap:7px}.sd-stat{padding:10px}.sd-stat strong{font-size:20px}}
.sd-incident-list{display:grid;gap:9px;margin-top:12px}.sd-incident{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;padding:13px;border:1px solid var(--line);border-radius:12px}.sd-incident-title{font-size:13px;font-weight:800;line-height:1.7}.sd-incident-detail{font-size:12px;color:var(--muted);line-height:1.8;overflow-wrap:anywhere;margin-top:3px}.sd-incident-meta{display:flex;gap:7px;align-items:center;flex-wrap:wrap;margin-top:7px;font-size:11px;color:var(--muted)}.sd-priority{display:inline-flex;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:800;background:rgba(220,38,38,.1);color:#b42318}.sd-priority.medium{background:rgba(217,119,6,.12);color:#9a5b08}.sd-incident-link{white-space:nowrap;text-decoration:none;font-size:12px;font-weight:800}.sd-incident-empty{border:1px dashed var(--line);border-radius:12px;padding:22px;text-align:center;color:var(--muted);font-size:13px}@media(max-width:650px){.sd-incident{grid-template-columns:1fr}.sd-incident-link{justify-self:start}}
/* Refined diagnostics experience */
.sd-hero{position:relative;isolation:isolate;display:flex;justify-content:space-between;align-items:center;gap:24px;overflow:hidden;padding:clamp(20px,3vw,30px);border:1px solid rgba(255,255,255,.14);border-radius:22px;background:radial-gradient(ellipse at 95% 0%,rgba(117,229,197,.2),transparent 38%),radial-gradient(ellipse at 0% 100%,rgba(73,163,170,.16),transparent 42%),linear-gradient(120deg,#102a3b 0%,#15575b 56%,#227f73 100%);color:#fff;box-shadow:0 16px 38px rgba(12,52,58,.12)}
.sd-hero:after{content:"";position:absolute;z-index:-1;inset-inline-end:28%;top:-155px;width:260px;height:260px;border:1px solid rgba(255,255,255,.13);border-radius:50%;box-shadow:0 0 0 25px rgba(255,255,255,.025),0 0 0 52px rgba(255,255,255,.02)}
.sd-hero-copy{min-width:0;max-width:700px}.sd-kicker{display:flex;align-items:center;gap:8px;margin-bottom:10px;color:#c8f8e8;font-size:11px;font-weight:850;letter-spacing:.15px}.sd-kicker-dot{width:7px;height:7px;border-radius:50%;background:#8cf0c7;box-shadow:0 0 0 4px rgba(140,240,199,.14)}.sd-kicker-separator{opacity:.6}
.sd-hero h2{margin:0;color:#fff;font-size:clamp(22px,2.7vw,31px);font-weight:950;line-height:1.45;letter-spacing:-.45px}.sd-hero p{max-width:620px;margin:8px 0 0;color:#d6eeea;font-size:12px;line-height:2}
.sd-hero-side{display:flex;flex-direction:column;align-items:flex-end;gap:12px;flex:none}.sd-overall-state{display:grid;grid-template-columns:8px auto;align-items:center;gap:7px 9px;padding:10px 13px;border:1px solid rgba(255,255,255,.2);border-radius:14px;background:rgba(255,255,255,.09);font-size:11px;font-weight:800;color:#fff}.sd-overall-indicator{grid-row:span 2;width:8px;height:8px;border-radius:50%;background:#9af0ca;box-shadow:0 0 0 4px rgba(154,240,202,.13)}.sd-overall-state.is-warning .sd-overall-indicator{background:#ffd17a;box-shadow:0 0 0 4px rgba(255,209,122,.14)}.sd-overall-state strong{font-size:13px;color:#fff;font-weight:950;direction:rtl}
.sd-action{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:38px;border-radius:11px;white-space:nowrap}.sd-action span{font-size:16px;line-height:1}
.sd-stats{gap:13px;margin:16px 0 24px}.sd-stat{position:relative;overflow:hidden;min-width:0;padding:16px 17px 14px;border-radius:17px;background:var(--card);box-shadow:0 5px 18px rgba(20,60,55,.035);transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease}.sd-stat:hover{transform:translateY(-2px);border-color:rgba(42,155,143,.45);box-shadow:0 10px 25px rgba(20,70,65,.075)}.sd-stat:after{content:"";position:absolute;inset-inline-end:-28px;bottom:-42px;width:100px;height:100px;border-radius:50%;background:var(--sd-tint,rgba(37,143,130,.08))}.sd-stat-top{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;gap:8px}.sd-stat small{margin:0;font-weight:750}.sd-stat strong{position:relative;z-index:1;display:block;margin-top:8px;font-size:clamp(24px,2.5vw,31px);font-weight:950;line-height:1.35;letter-spacing:-.5px;font-variant-numeric:tabular-nums}.sd-stat-icon{display:grid;place-items:center;flex:none;width:36px;height:36px;border:1px solid var(--line);border-radius:12px;background:var(--sd-tint,rgba(37,143,130,.08));color:var(--sd-accent,#248c7f)}.sd-stat-icon svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}.sd-stat-foot{position:relative;z-index:1;display:block;margin-top:4px;color:var(--muted);font-size:10px;line-height:1.7}.sd-stat-today{--sd-accent:#c04436;--sd-tint:rgba(210,72,59,.09)}.sd-stat-week{--sd-accent:#b77b19;--sd-tint:rgba(224,161,55,.12)}.sd-stat-total{--sd-accent:#3778bd;--sd-tint:rgba(55,120,189,.1)}.sd-stat-sms{--sd-accent:#9c4eae;--sd-tint:rgba(156,78,174,.1)}
.sd-incidents,.sd-health-section,.sd-history-section{padding-top:2px}.sd-health-head{margin-bottom:12px}.sd-health-head h3{font-weight:900;letter-spacing:-.2px}.sd-incident-list{gap:10px;margin-top:14px}.sd-incident{position:relative;padding:15px 16px;border-radius:15px;background:var(--card);transition:border-color .18s ease,transform .18s ease,box-shadow .18s ease}.sd-incident:hover{transform:translateY(-1px);border-color:rgba(42,155,143,.4);box-shadow:0 8px 20px rgba(20,60,55,.05)}.sd-incident:before{content:"";position:absolute;inset-block:13px;inset-inline-start:0;width:3px;border-radius:3px;background:var(--sd-incident-accent,#c04436)}.sd-incident:has(.sd-priority.medium){--sd-incident-accent:#c18a2a}.sd-incident-title{font-size:13px;font-weight:900}.sd-priority{padding:4px 9px;letter-spacing:.05px}.sd-incident-link{display:inline-flex;align-items:center;justify-content:center;padding:9px 12px;border:1px solid var(--line);border-radius:10px;background:var(--panel);transition:background .15s ease,border-color .15s ease}.sd-incident-link:hover{background:var(--brand-soft);border-color:var(--brand);text-decoration:none}.sd-incident-empty{padding:30px 18px;border-radius:16px;background:var(--panel);line-height:2}
.sd-check{position:relative;overflow:hidden;padding:15px;border-radius:15px;background:var(--card);transition:border-color .18s ease,transform .18s ease}.sd-check:hover{transform:translateY(-1px);border-color:rgba(42,155,143,.42)}.sd-check h4{font-weight:900;line-height:1.7}.sd-check p{overflow-wrap:anywhere}.sd-state{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:rgba(22,128,74,.09)}.sd-state.fail{background:rgba(192,57,43,.09)}.sd-state.ok{color:#16804a}:root[data-theme="dark"] .sd-state.ok{color:#8ee0af}:root[data-theme="dark"] .sd-state.fail{color:#ffaaa3}
.sd-filters{gap:12px}.sd-filters label{font-weight:750}.sd-filters input,.sd-filters select{min-height:42px;border-radius:11px}.sd-copy{border-radius:9px;padding:7px 10px;transition:background .15s ease,border-color .15s ease}.sd-copy:hover{background:var(--brand-soft);border-color:var(--brand)}.sd-date{font-variant-numeric:tabular-nums}.sd-message{line-height:1.9}.sd-path{max-width:230px;line-height:1.8}.sd-incident-meta{gap:8px}.sd-incident-meta>span:not(:first-child){padding-inline-start:8px;border-inline-start:1px solid var(--line)}
@media(max-width:900px){.sd-hero{align-items:flex-start;flex-direction:column}.sd-hero-side{align-items:flex-start;width:100%}.sd-overall-state{grid-template-columns:8px auto auto}.sd-overall-indicator{grid-row:auto}.sd-hero-side .sd-toolbar{width:100%}}
@media(max-width:650px){.sd-hero{padding:20px 17px;border-radius:18px;gap:18px}.sd-hero h2{font-size:23px}.sd-hero-side .sd-toolbar{display:grid;grid-template-columns:1fr 1fr}.sd-action{white-space:normal;font-size:11px;padding:8px}.sd-stats{gap:8px;margin:12px 0 21px}.sd-stat{padding:12px 11px;border-radius:14px}.sd-stat strong{font-size:23px}.sd-stat-icon{width:31px;height:31px;border-radius:10px}.sd-stat-icon svg{width:16px;height:16px}.sd-stat-foot{font-size:9px}.sd-incident{padding:13px 13px 13px 14px}.sd-incident-meta{gap:6px}.sd-incident-meta>span:not(:first-child){padding-inline-start:6px}.sd-incident-link{justify-self:start}.sd-filters{grid-template-columns:1fr}.sd-path{max-width:170px}}
@media(prefers-reduced-motion:reduce){.sd-stat,.sd-incident,.sd-check,.sd-incident-link,.sd-copy{transition:none}.sd-stat:hover,.sd-incident:hover,.sd-check:hover{transform:none}}

</style>
<div class="card">
  <header class="sd-hero">
    <div class="sd-hero-copy">
      <div class="sd-kicker"><span class="sd-kicker-dot"></span> پایش سامانه <span class="sd-kicker-separator">/</span> مرکز عملیات فنی</div>
      <h2>مرکز خطا و عیب‌یابی</h2>
      <p>وضعیت سرویس‌ها، رخدادهای مهم و گزارش خطاها را در یک نمای متمرکز بررسی کن. جزئیات فنی برای پیگیری سریع آماده‌اند.</p>
    </div>
    <div class="sd-hero-side">
      <div class="sd-overall-state {{ $badCount > 0 ? 'is-warning' : 'is-healthy' }}">
        <span class="sd-overall-indicator"></span>
        <span>{{ $badCount > 0 ? 'نیازمند بررسی' : 'وضعیت بررسی‌ها پایدار است' }}</span>
        <strong>{{ $F::num($okCount) }} / {{ $F::num(count($health)) }}</strong>
      </div>
      <div class="sd-toolbar">
        <button type="button" class="btn sec sd-action" onclick="window.location.reload()"><span aria-hidden="true">↻</span> تازه‌سازی</button>
        <button type="button" class="btn sd-action" id="sd-copy-health"><span aria-hidden="true">▤</span> کپی گزارش سلامت</button>
      </div>
    </div>
  </header>
  <div class="sd-stats">
    <article class="sd-stat sd-stat-today">
      <div class="sd-stat-top"><small>خطاهای امروز</small><span class="sd-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 22 20H2L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg></span></div>
      <strong>{{ $F::num($stats['today']) }}</strong><span class="sd-stat-foot">رخدادهای ثبت‌شده از ابتدای روز</span>
    </article>
    <article class="sd-stat sd-stat-week">
      <div class="sd-stat-top"><small>خطاهای ۷ روز اخیر</small><span class="sd-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3m-3 3h7"/></svg></span></div>
      <strong>{{ $F::num($stats['week']) }}</strong><span class="sd-stat-foot">روند کوتاه‌مدت رخدادها</span>
    </article>
    <article class="sd-stat sd-stat-total">
      <div class="sd-stat-top"><small>کل تاریخچه خطاها</small><span class="sd-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 3h10l4 4v14H5z"/><path d="M14 3v5h5M8 13h8m-8 4h8"/></svg></span></div>
      <strong>{{ $F::num($stats['total']) }}</strong><span class="sd-stat-foot">تمام گزارش‌های ذخیره‌شده</span>
    </article>
    <article class="sd-stat sd-stat-sms">
      <div class="sd-stat-top"><small>پیامک ناموفق در ۲۴ ساعت</small><span class="sd-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v12H9l-5 4z"/><path d="m9 9 6 6m0-6-6 6"/></svg></span></div>
      <strong>{{ $F::num($stats['failed_sms']) }}</strong><span class="sd-stat-foot">ارسال‌هایی که نیاز به پیگیری دارند</span>
    </article>
  </div>

  <section class="sd-incidents" style="margin-top:8px">
    <div class="sd-health-head">
      <div><h3 style="margin:0;font-size:16px">صف رسیدگی به رخدادها</h3><p class="sd-muted" style="margin:6px 0 0">خطا، بکاپ، Cron، پیامک و پرداخت ناموفق از تاریخچه‌های فعلی در یک نمای اولویت‌بندی‌شده جمع شده‌اند.</p></div>
      <span class="sd-muted">{{ $F::num(count($incidents)) }} مورد اخیر</span>
    </div>
    @if(count($incidents))
      <div class="sd-incident-list">
        @foreach($incidents as $incident)
          <article class="sd-incident">
            <div>
              <div class="sd-incident-title">{{ $incident['title'] }}</div>
              <div class="sd-incident-detail">{{ $incident['detail'] }}</div>
              <div class="sd-incident-meta">
                <span class="sd-priority {{ $incident['priority'] === 'متوسط' ? 'medium' : '' }}">{{ $incident['priority'] === 'بالا' ? 'اولویت بالا' : 'اولویت متوسط' }}</span>
                <span>{{ $incident['source'] }}</span>
                <span>{{ $F::date($incident['occurred_at'], 'j F Y · H:i') }}</span>
              </div>
            </div>
            <a class="sd-incident-link" href="{{ $incident['url'] }}">بررسی رخداد ←</a>
          </article>
        @endforeach
      </div>
    @else
      <div class="sd-incident-empty">در تاریخچه‌های قابل‌دسترسی، رخداد ناموفق اخیر پیدا نشد. این به معنی اجرای آزمایش واقعی تمام سرویس‌ها نیست.</div>
    @endif
  </section>

  <section style="margin-top:22px">
    <div class="sd-health-head"><h3 style="margin:0;font-size:16px">سلامت اجزای سامانه</h3><span class="sd-muted">سالم: {{ $F::num($okCount) }} · نیازمند بررسی: {{ $F::num($badCount) }}</span></div>
    <div class="sd-health" id="sd-health-report">
      @foreach($health as $check)
        <article class="sd-check" data-check-name="{{ $check['name'] }}" data-check-status="{{ $check['status'] }}" data-check-detail="{{ $check['detail'] }}" data-check-message="{{ $check['message'] }}">
          <h4>{{ $check['name'] }}</h4><p>{{ $check['detail'] }}</p>
          <span class="sd-state {{ $check['status'] }}">{{ $check['status'] === 'ok' ? '● سالم' : '● نیازمند بررسی' }}</span>
          <p style="margin-top:7px">{{ $check['message'] }}</p>
        </article>
      @endforeach
    </div>
    <p class="sd-muted">این بررسی‌ها وضعیت اتصال و دسترسی اجزای اصلی را می‌سنجند؛ جایگزین تست یکپارچه همه قابلیت‌ها یا بررسی سرویس‌های بیرونی نیستند.</p>
  </section>

  <section style="margin-top:26px">
    <div class="page-h" style="margin-bottom:12px"><div><h3 style="margin:0;font-size:16px">تاریخچه خطاها</h3><p class="sd-muted" style="margin:6px 0 0">خطاها از زمان فعال‌شدن این قابلیت ثبت می‌شوند.</p></div><span class="sd-muted">{{ $F::num($errors->total()) }} نتیجه</span></div>
    <form method="GET" class="card" style="padding:14px;margin-bottom:16px">
      <div class="sd-filters">
        <div><label for="sd-q">جست‌وجو</label><input id="sd-q" name="q" value="{{ $filters['q'] }}" placeholder="نوع خطا، پیام، مسیر یا شناسه کاربر"></div>
        <div><label for="sd-status">کد پاسخ</label><select id="sd-status" name="status"><option value="">همه خطاها</option><option value="500" @selected($filters['status']==='500')>۵۰۰ — خطای سرور</option><option value="503" @selected($filters['status']==='503')>۵۰۳ — سرویس در دسترس نیست</option><option value="404" @selected($filters['status']==='404')>۴۰۴ — پیدا نشد</option><option value="403" @selected($filters['status']==='403')>۴۰۳ — عدم دسترسی</option><option value="422" @selected($filters['status']==='422')>۴۲۲ — خطای قابل‌مدیریت برنامه</option><option value="other" @selected($filters['status']==='other')>سایر کدها</option></select></div>
        <div><label for="sd-from">از تاریخ میلادی</label><input id="sd-from" type="date" name="from" value="{{ $filters['from'] }}"></div>
        <div><label for="sd-to">تا تاریخ میلادی</label><input id="sd-to" type="date" name="to" value="{{ $filters['to'] }}"></div>
      </div>
      <div class="fbar" style="margin-top:12px"><button class="btn" type="submit">اعمال فیلتر</button><a class="btn sec" href="{{ route('admin.system-diagnostics.index') }}">پاک‌کردن</a></div>
    </form>

    @if($errors->isEmpty())
      <div class="empty">خطایی با این فیلترها پیدا نشد. برای دیدن خطاهای واقعی، برنامه را پس از اجرای migration استفاده کنید.</div>
    @else
      <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>زمان</th><th>نوع خطا</th><th>پیام</th><th>کاربر ایجادکننده</th><th>مسیر</th><th>کد</th><th>جزئیات</th></tr></thead>
        <tbody>
        @foreach($errors as $error)
          @php
            $actor = $error->actor_type === 'admin'
              ? ($error->admin?->name ?: ($error->admin?->username ?: 'مدیر حذف‌شده'))
              : ($error->actor_type === 'user' ? ($error->user?->display_name ?: 'کاربر حذف‌شده') : 'مهمان / بدون ورود');
            $details = "شناسه لاگ: {$error->id}\\nشدت: ".($error->severity ?? 'error')."\\nدسته: ".($error->category ?? 'عمومی')."\\nنوع: {$error->exception_class}\\nپیام: {$error->message}\\nجزئیات فنی:\\n".($error->details ?: 'جزئیات فنی اضافی ثبت نشده است.')."\\nزمینه: ".json_encode($error->context ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\\nکد پاسخ: {$error->status_code}\\nکاربر: {$actor}\\nشناسه کاربر: ".($error->user_id ?: '—')."\\nشناسه مدیر: ".($error->admin_user_id ?: '—')."\\nروش: ".($error->method ?: '—')."\\nمسیر: ".($error->path ?: '—')."\\nنام مسیر: ".($error->route_name ?: '—')."\\nفایل: ".($error->file ?: '—')."\\nخط: ".($error->line ?: '—')."\\nزمان: ".($error->created_at ? $F::date($error->created_at, 'Y/m/d H:i:s') : '—');
          @endphp
          <tr>
            <td class="sd-date">{{ $error->created_at ? $F::date($error->created_at, 'j F Y H:i') : '—' }}</td>
            <td><div style="font-weight:750;max-width:210px;overflow-wrap:anywhere">{{ class_basename($error->exception_class) }}</div><div class="sd-muted">{{ $error->exception_class }}</div><div class="sd-muted">{{ $error->category ?: 'عمومی' }} · {{ ($error->severity ?? 'error') === 'warning' ? 'هشدار' : 'خطا' }}</div></td>
            <td class="sd-message">{{ \Illuminate\Support\Str::limit($error->message, 180) }}</td>
            <td><div style="font-weight:750">{{ $actor }}</div><div class="sd-muted">{{ $error->actor_type === 'admin' ? 'مدیر پنل' : ($error->actor_type === 'user' ? 'کاربر سامانه' : 'مهمان') }}</div><div class="sd-muted">شناسه: {{ $F::num($error->admin_user_id ?: ($error->user_id ?: 0)) }}</div></td>
            <td class="sd-path">{{ $error->path ?: '—' }}<div class="sd-muted">{{ $error->route_name ?: '' }}</div><div class="sd-muted">{{ $error->file ? basename($error->file).':'.$error->line : '' }}</div></td>
            <td>{{ $F::num($error->status_code) }}</td>
            <td>
              <details>
                <summary style="cursor:pointer;font-size:12px;margin-bottom:8px">نمایش جزئیات کامل</summary>
                <div style="min-width:260px;max-width:520px">
                  <div class="sd-muted">شناسه لاگ: {{ $F::num($error->id) }} · {{ $error->category ?: 'عمومی' }}</div>
                  <pre style="white-space:pre-wrap;overflow-wrap:anywhere;direction:ltr;text-align:left;max-height:280px;overflow:auto;font-size:11px;padding:10px;border:1px solid var(--line);border-radius:8px">{{ $details }}</pre>
                </div>
              </details>
              <button class="sd-copy" type="button" data-copy-details="{{ base64_encode($details) }}">کپی همین لاگ</button>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table></div>
      <div style="margin-top:16px">{{ $errors->links() }}</div>
    @endif
  </section>
</div>
<div id="sd-toast" role="status" aria-live="polite" style="display:none;margin-top:10px;color:var(--muted)"></div>
@push('scripts')
<script>
(function () {
  const toast = document.getElementById('sd-toast');
  async function copyText(value) {
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(value);
      } else {
        const area = document.createElement('textarea');
        area.value = value; area.style.position = 'fixed'; area.style.opacity = '0';
        document.body.appendChild(area); area.select();
        const copied = document.execCommand('copy'); area.remove();
        if (!copied) throw new Error('copy failed');
      }
      toast.textContent = 'گزارش در کلیپ‌بورد کپی شد.'; toast.style.display = 'block';
    } catch (error) {
      toast.textContent = 'کپی خودکار ممکن نشد؛ مرورگر اجازه دسترسی به کلیپ‌بورد را نداد.'; toast.style.display = 'block';
    }
  }
  document.getElementById('sd-copy-health')?.addEventListener('click', function () {
    const lines = Array.from(document.querySelectorAll('[data-check-name]')).map((item) =>
      item.dataset.checkName + ' — ' + (item.dataset.checkStatus === 'ok' ? 'سالم' : 'نیازمند بررسی') + '\n' + item.dataset.checkDetail + '\n' + item.dataset.checkMessage
    );
    copyText('گزارش سلامت صابخونه\n' + lines.join('\n\n'));
  });
  document.querySelectorAll('[data-copy-details]').forEach(function (button) {
    button.addEventListener('click', function () {
      try { copyText(decodeURIComponent(escape(atob(button.dataset.copyDetails)))); }
      catch (error) { copyText(atob(button.dataset.copyDetails)); }
    });
  });
})();
</script>
@endpush
@endsection
