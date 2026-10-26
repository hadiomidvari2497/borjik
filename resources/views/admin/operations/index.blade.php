@extends('admin.layout')
@section('title', 'مرکز عملیات و پشتیبان‌گیری')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $bytes = fn ($n) => $n === null ? '—' : ($n >= 1073741824 ? number_format($n / 1073741824, 2).' GB' : number_format($n / 1048576, 1).' MB');
@endphp
<style>
.oc-page{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:22px;padding:clamp(16px,2.4vw,30px);background:linear-gradient(145deg,rgba(16,185,129,.045),transparent 34%),var(--surface)}
.oc-page:before{content:"";position:absolute;top:-115px;left:-70px;width:260px;height:260px;border-radius:50%;background:rgba(16,185,129,.075);filter:blur(2px);pointer-events:none}
.oc-page>*{position:relative}
.oc-head{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}
.oc-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;padding:clamp(18px,3vw,30px);border-radius:18px;border:1px solid rgba(16,185,129,.2);background:linear-gradient(125deg,rgba(16,185,129,.12),rgba(20,184,166,.045) 58%,transparent);margin-bottom:20px}
.oc-hero-copy{min-width:0;max-width:720px}
.oc-eyebrow{display:inline-flex;align-items:center;gap:7px;font-size:11px;font-weight:850;letter-spacing:.02em;color:var(--primary,#0f9f78);padding:5px 10px;border:1px solid rgba(16,185,129,.24);border-radius:999px;background:rgba(16,185,129,.07);margin-bottom:10px}
.oc-title{font-size:clamp(21px,2.4vw,29px);font-weight:900;letter-spacing:-.035em;line-height:1.5;margin:0}
.oc-subtitle{font-size:13px;color:var(--muted);line-height:2;margin:6px 0 0;max-width:650px}
.oc-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 11px;font-size:11px;font-weight:850;white-space:nowrap;border:1px solid transparent}
.oc-good{background:#e8f7ee;color:#17683a;border-color:rgba(23,104,58,.08)}
.oc-bad{background:#fff0ef;color:#a52b24;border-color:rgba(165,43,36,.08)}
.oc-warn{background:#fff5df;color:#8a5a08;border-color:rgba(138,90,8,.08)}
.oc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:18px 0 28px}
.oc-card{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:17px;padding:19px;min-width:0;background:var(--surface);box-shadow:0 5px 18px rgba(15,23,42,.025);transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease}
.oc-card:after{content:"";position:absolute;inset-inline-start:0;top:18px;bottom:18px;width:3px;border-radius:4px;background:rgba(16,185,129,.55)}
.oc-card:hover{transform:translateY(-2px);border-color:rgba(16,185,129,.35);box-shadow:0 10px 25px rgba(15,23,42,.06)}
.oc-card h3{font-size:20px;font-weight:900;letter-spacing:-.02em;line-height:1.6;overflow-wrap:anywhere}
.oc-muted{color:var(--muted);font-size:12px;line-height:1.95}
.oc-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}
.oc-form>div{min-width:0;padding:13px;border:1px solid var(--line);border-radius:13px;background:color-mix(in srgb,var(--surface) 94%,var(--muted) 6%)}
.oc-form label{display:block;font-size:12px;font-weight:750;color:var(--muted);margin-bottom:7px}
.oc-form input:not([type=checkbox]),.oc-form select{width:100%;min-width:0;border-radius:10px}
.oc-page input:not([type=checkbox]):not([type=hidden]):not([type=submit]),.oc-page select,.oc-page textarea{display:block;box-sizing:border-box;min-height:46px;width:100%;max-width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:12px;background:var(--surface)!important;color:var(--text)!important;font:inherit;font-size:13px;line-height:1.6;box-shadow:inset 0 1px 2px rgba(15,23,42,.025),0 1px 0 rgba(255,255,255,.12);outline:none;transition:border-color .18s ease,box-shadow .18s ease,background-color .18s ease}
.oc-page input::placeholder,.oc-page textarea::placeholder{color:var(--muted);opacity:.78}
.oc-page input:hover:not([type=checkbox]):not([type=hidden]),.oc-page select:hover,.oc-page textarea:hover{border-color:rgba(16,185,129,.55)}
.oc-page input:focus-visible:not([type=checkbox]):not([type=hidden]),.oc-page select:focus-visible,.oc-page textarea:focus-visible{border-color:#10b981!important;box-shadow:0 0 0 3px rgba(16,185,129,.16),0 3px 10px rgba(16,185,129,.06);background:var(--surface)!important}
.oc-page input:disabled,.oc-page select:disabled,.oc-page textarea:disabled{opacity:.58;cursor:not-allowed}
.oc-page input:-webkit-autofill,.oc-page input:-webkit-autofill:hover,.oc-page input:-webkit-autofill:focus{-webkit-text-fill-color:var(--text)!important;box-shadow:0 0 0 1000px var(--surface) inset,0 0 0 3px rgba(16,185,129,.08);caret-color:var(--text);border-color:rgba(16,185,129,.5)}
.oc-page select{appearance:none;padding-inline-end:42px;cursor:pointer;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%238896a3' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:left 13px center!important}
.oc-page input[type=number]{font-variant-numeric:tabular-nums}
.oc-page input[type=number]::-webkit-inner-spin-button,.oc-page input[type=number]::-webkit-outer-spin-button{opacity:.55}
.oc-page input[type=checkbox]{appearance:none;-webkit-appearance:none;display:inline-grid;place-content:center;flex:0 0 19px;width:19px;height:19px;min-width:19px;margin:0;border:1px solid var(--line);border-radius:6px;background:var(--surface);cursor:pointer;transition:background .16s ease,border-color .16s ease,box-shadow .16s ease}
.oc-page input[type=checkbox]::before{content:"";width:9px;height:5px;border:2px solid #fff;border-top:0;border-right:0;transform:rotate(-45deg) scale(0);transform-origin:center;transition:transform .12s ease}
.oc-page input[type=checkbox]:checked{background:#0f9f78;border-color:#0f9f78;box-shadow:0 2px 6px rgba(15,159,120,.2)}
.oc-page input[type=checkbox]:checked::before{transform:rotate(-45deg) scale(1)}
.oc-page input[type=checkbox]:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(16,185,129,.2)}
.oc-page input[type=checkbox]:disabled{opacity:.55;cursor:not-allowed}
.oc-form>div:focus-within{border-color:rgba(16,185,129,.28);background:color-mix(in srgb,var(--surface) 97%,#10b981 3%)}
.oc-actions input:not([type=checkbox]):not([type=hidden]){min-height:40px;width:auto;max-width:100%;padding:8px 12px;border-radius:10px}
.oc-page input[type=password]{letter-spacing:.04em}
@media(max-width:640px){.oc-page input:not([type=checkbox]):not([type=hidden]),.oc-page select,.oc-page textarea{min-height:45px;font-size:16px}.oc-actions input:not([type=checkbox]):not([type=hidden]){width:100%;min-width:0}}
.oc-check{display:flex!important;align-items:center;gap:9px;font-size:13px!important;color:var(--text)!important;min-height:25px}
.oc-check input{width:auto;accent-color:#0f9f78}
.oc-section{margin-top:27px;padding-top:24px;border-top:1px solid var(--line)}
.oc-section h3{font-size:17px;font-weight:900;letter-spacing:-.02em}
.oc-section h4{font-size:14px;font-weight:850;margin:0 0 8px}
.oc-section> .oc-head{margin-bottom:14px}
.oc-table-wrap{overflow:auto;border:1px solid var(--line);border-radius:14px;margin-top:13px}
.oc-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--surface)}
.oc-table th,.oc-table td{padding:13px 12px;border-bottom:1px solid var(--line);text-align:right;vertical-align:top}
.oc-table th{font-size:11px;font-weight:850;color:var(--muted);white-space:nowrap;background:color-mix(in srgb,var(--surface) 90%,var(--muted) 10%)}
.oc-table tbody tr:last-child td{border-bottom:0}
.oc-table tbody tr:hover td{background:rgba(16,185,129,.035)}
.oc-table td{font-size:12px;line-height:1.9}
.oc-secret{background:var(--surface);border:1px dashed var(--line);padding:12px 14px;border-radius:11px;font-size:12px;color:var(--muted)}
.oc-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:16px;padding:14px;border:1px solid var(--line);border-radius:15px;background:color-mix(in srgb,var(--surface) 93%,var(--muted) 7%)}
.oc-actions .btn{border-radius:10px;min-height:40px;font-weight:800;transition:transform .15s ease,box-shadow .15s ease}
.oc-actions .btn:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(15,23,42,.08)}
.oc-actions form{margin:0}
.oc-section form[data-confirm] .btn{white-space:nowrap}
.oc-footnote{margin-top:22px;padding:15px 17px;border-radius:13px;border:1px solid rgba(16,185,129,.18);background:rgba(16,185,129,.045)}
@media(max-width:900px){.oc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.oc-grid,.oc-form{grid-template-columns:1fr}.oc-hero{align-items:flex-start}.oc-page{padding:12px;border-radius:16px}.oc-card{padding:16px}.oc-actions{padding:10px}.oc-actions>.btn,.oc-actions>form{width:100%}.oc-actions>form .btn{width:100%}.oc-actions form>div{width:100%}.oc-table{min-width:760px}}
@media(prefers-color-scheme:dark){:root:not([data-theme="light"]) .oc-good{background:rgba(47,160,96,.18);color:#91e0ae}:root:not([data-theme="light"]) .oc-bad{background:rgba(224,69,58,.18);color:#ffaaa3}:root:not([data-theme="light"]) .oc-warn{background:rgba(232,163,61,.18);color:#f4c775}}
:root[data-theme="dark"] .oc-good{background:rgba(47,160,96,.18);color:#91e0ae}
:root[data-theme="dark"] .oc-bad{background:rgba(224,69,58,.18);color:#ffaaa3}
:root[data-theme="dark"] .oc-warn{background:rgba(232,163,61,.18);color:#f4c775}
@media(prefers-reduced-motion:reduce){.oc-card,.oc-actions .btn{transition:none}.oc-card:hover,.oc-actions .btn:hover{transform:none}}
</style>
<div class="card oc-page">
  <header class="oc-hero">
    <div class="oc-hero-copy">
      <span class="oc-eyebrow">مدیریت زیرساخت · صابخونه</span>
      <h2 class="oc-title">مرکز عملیات و پشتیبان‌گیری</h2>
      <p class="oc-subtitle">وضعیت سرویس‌ها را بررسی کن، نسخه‌های پشتیبان را مدیریت کن و از آماده‌بودن مسیر بازیابی اطلاعات مطمئن شو.</p>
    </div>
    <span class="oc-pill {{ $smsReady ? 'oc-good' : 'oc-warn' }}"><span aria-hidden="true">●</span>{{ $smsReady ? 'اتصال پیامکی آماده است' : 'اتصال پیامکی تنظیم نشده' }}</span>
  </header>
  <div class="oc-grid">
    <div class="oc-card"><span class="oc-muted">آخرین بررسی سلامت</span><h3 style="margin:8px 0">{{ $lastHealth ? ($lastHealth->status === 'ok' ? 'سالم' : 'نیازمند رسیدگی') : 'هنوز بررسی نشده' }}</h3><span class="oc-muted">{{ $lastHealth ? $F::date($lastHealth->created_at, 'j F Y - H:i:s') : 'برای شروع «بررسی سلامت» را اجرا کن.' }}</span></div>
    <div class="oc-card"><span class="oc-muted">آخرین بکاپ</span><h3 style="margin:8px 0">{{ $lastBackup ? (['success'=>'موفق','failed'=>'ناموفق','running'=>'در حال اجرا'][$lastBackup->status] ?? $lastBackup->status) : 'هنوز بکاپی ثبت نشده' }}</h3><span class="oc-muted">{{ $lastBackup?->size_bytes ? $bytes($lastBackup->size_bytes) : 'حجم نامشخص' }} @if($lastBackup?->finished_at) · {{ $F::date($lastBackup->finished_at, 'j F Y - H:i:s') }} @endif</span></div>
    <div class="oc-card"><span class="oc-muted">مقصد ابری</span><h3 style="margin:8px 0">{{ $settings['cloud_enabled'] ? 'OneDrive فعال' : 'غیرفعال' }}</h3><span class="oc-muted">برای بکاپ خارج از سرور، حساب OneDrive شخصی را متصل کن و سپس یک بکاپ آزمایشی اجرا کن.</span></div>
  </div>

  <section class="oc-section">
    <div class="oc-head"><h3 style="margin:0">تنظیمات بکاپ و هشدار</h3><span class="oc-muted">کلیدهای OAuth به‌صورت رمزنگاری‌شده در دیتابیس ذخیره می‌شوند.</span></div>
    <form method="POST" action="{{ route('admin.operations.update') }}" style="margin-top:14px">@csrf @method('PUT')
      <div class="oc-form">
        <div><label for="local_path">مسیر کامل ذخیرهٔ محلی</label><input id="local_path" name="local_path" value="{{ old('local_path', $settings['local_path']) }}" required placeholder="/var/backups/borjik"><p class="oc-muted">مثال اوبونتو: /var/backups/borjik — بکاپ داخل پوشهٔ عمومی سایت پذیرفته نمی‌شود.</p></div>
        <div><label for="backup_frequency">تناوب بکاپ خودکار</label><select id="backup_frequency" name="backup_frequency"><option value="twice_daily" @selected(old('backup_frequency',$settings['backup_frequency'])==='twice_daily')>هر ۱۲ ساعت</option><option value="daily" @selected(old('backup_frequency',$settings['backup_frequency'])==='daily')>روزانه</option><option value="weekly" @selected(old('backup_frequency',$settings['backup_frequency'])==='weekly')>هفتگی</option></select></div>
        <div><label for="keep_local">تعداد نسخه‌های محلی برای نگهداری</label><input id="keep_local" type="number" min="1" max="365" name="keep_local" value="{{ old('keep_local',$settings['keep_local']) }}" required></div>
        <div><label for="disk_warning_percent">آستانه هشدار مصرف دیسک (درصد)</label><input id="disk_warning_percent" type="number" min="50" max="98" name="disk_warning_percent" value="{{ old('disk_warning_percent',$settings['disk_warning_percent']) }}" required></div>
        <div><label class="oc-check"><input type="checkbox" name="backup_enabled" value="1" @checked(old('backup_enabled',$settings['backup_enabled']))> بکاپ خودکار فعال باشد</label><p class="oc-muted">نیازمند اجرای Cron لاراول روی سرور است.</p></div>
        <div><input type="hidden" name="cloud_provider" value="onedrive"><label class="oc-check"><input type="checkbox" name="cloud_enabled" value="1" @checked(old('cloud_enabled',$settings['cloud_enabled']))> ارسال نسخه به OneDrive فعال باشد</label><p class="oc-muted">در صورت فعال‌بودن، شکست ارسال ابری به‌عنوان شکست کل بکاپ ثبت می‌شود.</p></div>
      </div>
      <div class="oc-section"><h4>اتصال OneDrive شخصی</h4>
        <p class="oc-muted">در Microsoft Entra یک برنامه با پشتیبانی از حساب‌های شخصی Microsoft ثبت کن. Redirect URI این صفحه را عیناً در برنامه وارد کن. Client Secret محرمانه است و در دیتابیس رمزنگاری‌شده نگهداری می‌شود. پس از ذخیرهٔ شناسه و رمز، دکمهٔ اتصال را بزن تا ورود به حساب Microsoft و اجازهٔ Files.ReadWrite انجام شود؛ Refresh Token به‌صورت خودکار و رمزنگاری‌شده ذخیره می‌شود.</p>
        <div class="oc-form">
          <div><label for="onedrive_client_id">Microsoft Application (Client) ID</label><input id="onedrive_client_id" name="onedrive_client_id" value="{{ old('onedrive_client_id',$settings['onedrive_client_id']) }}" autocomplete="off"></div>
          <div><label for="onedrive_client_secret">Client Secret Value</label><input id="onedrive_client_secret" name="onedrive_client_secret" type="password" autocomplete="new-password" placeholder="{{ $settings['onedrive_client_secret_set'] ? 'ذخیره شده؛ برای تغییر مقدار جدید وارد کن' : 'وارد کردن مقدار' }}"></div>
        </div>
        <div class="oc-secret" style="margin-top:10px;direction:ltr;overflow-wrap:anywhere">Redirect URI: {{ route('admin.operations.onedrive.callback') }}</div>
      </div>
      <div class="oc-section"><h4>هشدار پیامکی</h4><p class="oc-muted">پیامک از اتصال فعال و پیش‌فرض بخش «اتصال پیامک» ارسال می‌شود. هشدارها هنگام تغییر وضعیت سلامت (خرابی یا بازگشت به حالت سالم) فرستاده می‌شوند تا هر بار بررسی، پیامک تکراری ایجاد نکند.</p>
        <div class="oc-form">
          <div><label for="alert_mobile">شماره موبایل دریافت هشدار</label><input id="alert_mobile" name="alert_mobile" value="{{ old('alert_mobile',$settings['alert_mobile']) }}" placeholder="0912xxxxxxx"></div>
          <div><label class="oc-check"><input type="checkbox" name="alert_enabled" value="1" @checked(old('alert_enabled',$settings['alert_enabled']))> هشدار پیامکی فعال باشد</label></div>
        </div>
      </div>
      <div class="oc-actions"><button class="btn" type="submit">ذخیره تنظیمات</button></div>
    </form>
    <div class="oc-actions">
      <a class="btn sec" href="{{ route('admin.operations.onedrive.connect') }}">اتصال / تأیید حساب OneDrive</a>
      <form method="POST" action="{{ route('admin.operations.check') }}">@csrf<button class="btn sec" type="submit">بررسی سلامت همین حالا</button></form>
      <form method="POST" action="{{ route('admin.operations.backup') }}">@csrf<button class="btn sec" type="submit">اجرای بکاپ همین حالا</button></form>
      <form method="POST" action="{{ route('admin.operations.test-sms') }}">@csrf<div style="display:flex;gap:6px;flex-wrap:wrap"><input name="mobile" value="{{ $settings['alert_mobile'] }}" placeholder="شماره تست" required style="max-width:190px"><button class="btn sec" type="submit">تست پیامک</button></div></form>
    </div>
  </section>

  <section class="oc-section"><div class="oc-head"><h3 style="margin:0">تاریخچه بکاپ‌ها</h3><span class="oc-muted">آخرین اجراها</span></div>
    <div class="oc-table-wrap"><table class="oc-table"><thead><tr><th>زمان</th><th>وضعیت</th><th>فایل</th><th>حجم</th><th>مسیر محلی</th><th>OneDrive</th><th>جزئیات</th><th>آزمایش بازیابی</th></tr></thead><tbody>
    @forelse($backups as $run)<tr><td>{{ $run->started_at ? $F::date($run->started_at, 'j F Y H:i:s') : '—' }}</td><td><span class="oc-pill {{ $run->status==='success'?'oc-good':($run->status==='failed'?'oc-bad':'oc-warn') }}">{{ ['success'=>'موفق','failed'=>'ناموفق','running'=>'در حال اجرا'][$run->status] ?? $run->status }}</span></td><td>{{ $run->filename ?: '—' }}</td><td>{{ $bytes($run->size_bytes) }}</td><td style="direction:ltr;text-align:left;overflow-wrap:anywhere">{{ $run->local_path ?: '—' }}</td><td>{{ $run->cloud_file_id ? 'ارسال شد · '.$run->cloud_file_id : ($settings['cloud_enabled'] ? 'ارسال نشده' : 'غیرفعال') }}</td><td>{{ $run->message ?: '—' }}</td><td>
      @if($run->status === 'success' && $run->local_path)
        <form method="POST" action="{{ route('admin.operations.backups.verify', $run->id) }}" data-confirm="آرشیو بدون استخراج و بدون تغییر دیتابیس بررسی می‌شود. ادامه می‌دهی؟" data-confirm-title="آزمایش غیرمخرب بکاپ" data-confirm-icon="question">@csrf<button class="btn sec" type="submit" style="white-space:nowrap">بررسی سلامت فایل</button></form>
        <div class="oc-muted" style="margin-top:5px">بدون بازنویسی دیتابیس</div>
      @else<span class="oc-muted">فایل محلی قابل آزمایش نیست</span>@endif
    </td></tr>@empty<tr><td colspan="8">هنوز بکاپی ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div><div style="margin-top:12px">{{ $backups->links() }}</div>
  </section>
  <section class="oc-section"><div class="oc-head"><h3 style="margin:0">تاریخچه سلامت</h3><span class="oc-muted">آخرین بررسی‌های زمان‌بندی‌شده</span></div>
    <div class="oc-table-wrap"><table class="oc-table"><thead><tr><th>زمان</th><th>وضعیت کلی</th><th>جزئیات بررسی‌ها</th></tr></thead><tbody>
    @forelse($health as $record)<tr><td>{{ $F::date($record->created_at, 'j F Y H:i:s') }}</td><td><span class="oc-pill {{ $record->status==='ok'?'oc-good':'oc-bad' }}">{{ $record->status==='ok'?'سالم':'هشدار' }}</span></td><td>@foreach($record->checks as $check)<div><strong>{{ $check['name'] ?? '' }}:</strong> {{ $check['detail'] ?? '' }} <span class="oc-muted">({{ $check['status'] ?? 'unknown' }})</span></div>@endforeach</td></tr>@empty<tr><td colspan="3">هنوز بررسی‌ای ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div><div style="margin-top:12px">{{ $health->links() }}</div>
  </section>
  <p class="oc-muted oc-footnote">نکته امنیتی: بکاپ شامل داده‌های خصوصی و فایل‌های بارگذاری‌شده است. مسیر محلی را خارج از public انتخاب کن و دسترسی حساب OneDrive را فقط به افراد مورداعتماد بده و دکمهٔ «بررسی سلامت فایل» یک تمرین غیرمخرب است: ساختار ZIP، manifest و خواندن فایل‌های آرشیو را می‌سنجد و دیتابیس فعال را تغییر نمی‌دهد. برای اثبات بازیابی کامل، فایل را در محیط ایزوله جداگانه بازیابی و برنامه را روی نسخهٔ آزمایشی بررسی کن.</p>
</div>
@endsection
