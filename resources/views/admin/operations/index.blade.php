@extends('admin.layout')
@section('title', 'مرکز عملیات و پشتیبان‌گیری')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $bytes = fn ($n) => $n === null ? '—' : ($n >= 1073741824 ? number_format($n / 1073741824, 2).' GB' : number_format($n / 1048576, 1).' MB');
@endphp
<style>
.oc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:16px 0}.oc-card{border:1px solid var(--line);border-radius:14px;padding:16px;min-width:0}.oc-muted{color:var(--muted);font-size:12px;line-height:1.9}.oc-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.oc-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.oc-form label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}.oc-form input,.oc-form select{width:100%;min-width:0}.oc-check{display:flex;align-items:center;gap:8px;font-size:13px}.oc-check input{width:auto}.oc-table{width:100%;border-collapse:collapse}.oc-table th,.oc-table td{padding:10px;border-bottom:1px solid var(--line);text-align:right;vertical-align:top}.oc-table th{font-size:12px;color:var(--muted)}.oc-pill{display:inline-flex;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:800}.oc-good{background:#e8f7ee;color:#17683a}.oc-bad{background:#fff0ef;color:#a52b24}.oc-warn{background:#fff5df;color:#8a5a08}.oc-secret{background:var(--surface);border:1px solid var(--line);padding:10px;border-radius:9px;font-size:12px;color:var(--muted)}.oc-section{margin-top:22px}.oc-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
@media(max-width:800px){.oc-grid{grid-template-columns:1fr}.oc-form{grid-template-columns:1fr}.oc-table-wrap{overflow:auto}.oc-table{min-width:680px}}
</style>
<div class="card">
  <div class="oc-head"><div><h2 style="margin:0">مرکز عملیات برجیک</h2><p class="oc-muted">سلامت سرویس، هشدار پیامکی و نسخه‌های پشتیبان را از یکجا مدیریت کن.</p></div><span class="oc-pill {{ $smsReady ? 'oc-good' : 'oc-warn' }}">{{ $smsReady ? 'اتصال پیامکی آماده است' : 'اتصال پیامکی تنظیم نشده' }}</span></div>
  <div class="oc-grid">
    <div class="oc-card"><span class="oc-muted">آخرین بررسی سلامت</span><h3 style="margin:8px 0">{{ $lastHealth ? ($lastHealth->status === 'ok' ? 'سالم' : 'نیازمند رسیدگی') : 'هنوز بررسی نشده' }}</h3><span class="oc-muted">{{ $lastHealth ? $F::date($lastHealth->created_at, 'j F Y - H:i:s') : 'برای شروع «بررسی سلامت» را اجرا کن.' }}</span></div>
    <div class="oc-card"><span class="oc-muted">آخرین بکاپ</span><h3 style="margin:8px 0">{{ $lastBackup ? (['success'=>'موفق','failed'=>'ناموفق','running'=>'در حال اجرا'][$lastBackup->status] ?? $lastBackup->status) : 'هنوز بکاپی ثبت نشده' }}</h3><span class="oc-muted">{{ $lastBackup?->size_bytes ? $bytes($lastBackup->size_bytes) : 'حجم نامشخص' }} @if($lastBackup?->finished_at) · {{ $F::date($lastBackup->finished_at, 'j F Y - H:i:s') }} @endif</span></div>
    <div class="oc-card"><span class="oc-muted">مقصد ابری</span><h3 style="margin:8px 0">{{ $settings['cloud_enabled'] ? 'Google Drive فعال' : 'غیرفعال' }}</h3><span class="oc-muted">برای بکاپ خارج از سرور، اتصال Google Drive را کامل و سپس یک بکاپ آزمایشی اجرا کن.</span></div>
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
        <div><label class="oc-check"><input type="checkbox" name="cloud_enabled" value="1" @checked(old('cloud_enabled',$settings['cloud_enabled']))> ارسال نسخه به Google Drive فعال باشد</label><p class="oc-muted">در صورت فعال‌بودن، شکست ارسال ابری به‌عنوان شکست کل بکاپ ثبت می‌شود.</p></div>
      </div>
      <div class="oc-section"><h4>اتصال Google Drive</h4><p class="oc-muted">از OAuth Client در Google Cloud و Refresh Token با مجوز Drive استفاده کن. مقادیر محرمانه در فرم دوباره نمایش داده نمی‌شوند؛ برای جایگزینی، مقدار جدید وارد کن.</p>
        <div class="oc-form">
          <div><label for="drive_client_id">OAuth Client ID</label><input id="drive_client_id" name="drive_client_id" value="{{ old('drive_client_id',$settings['drive_client_id']) }}" autocomplete="off"></div>
          <div><label for="drive_client_secret">OAuth Client Secret</label><input id="drive_client_secret" name="drive_client_secret" type="password" autocomplete="new-password" placeholder="{{ $settings['drive_client_secret_set'] ? 'ذخیره شده؛ برای تغییر مقدار جدید وارد کن' : 'وارد کردن مقدار' }}"></div>
          <div><label for="drive_refresh_token">OAuth Refresh Token</label><input id="drive_refresh_token" name="drive_refresh_token" type="password" autocomplete="new-password" placeholder="{{ $settings['drive_refresh_token_set'] ? 'ذخیره شده؛ برای تغییر مقدار جدید وارد کن' : 'وارد کردن مقدار' }}"></div>
          <div><label for="drive_folder_id">شناسه پوشه مقصد (اختیاری)</label><input id="drive_folder_id" name="drive_folder_id" value="{{ old('drive_folder_id',$settings['drive_folder_id']) }}" placeholder="Google Drive folder ID"></div>
        </div>
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
      <form method="POST" action="{{ route('admin.operations.check') }}">@csrf<button class="btn sec" type="submit">بررسی سلامت همین حالا</button></form>
      <form method="POST" action="{{ route('admin.operations.backup') }}" onsubmit="return confirm('بکاپ جدید ایجاد شود؟ در صورت فعال بودن Google Drive، فایل به مقصد ابری هم ارسال می‌شود.')">@csrf<button class="btn sec" type="submit">اجرای بکاپ همین حالا</button></form>
      <form method="POST" action="{{ route('admin.operations.test-sms') }}">@csrf<div style="display:flex;gap:6px;flex-wrap:wrap"><input name="mobile" value="{{ $settings['alert_mobile'] }}" placeholder="شماره تست" required style="max-width:190px"><button class="btn sec" type="submit">تست پیامک</button></div></form>
    </div>
  </section>

  <section class="oc-section"><div class="oc-head"><h3 style="margin:0">تاریخچه بکاپ‌ها</h3><span class="oc-muted">آخرین اجراها</span></div>
    <div class="oc-table-wrap"><table class="oc-table"><thead><tr><th>زمان</th><th>وضعیت</th><th>فایل</th><th>حجم</th><th>مسیر محلی</th><th>Google Drive</th><th>جزئیات</th></tr></thead><tbody>
    @forelse($backups as $run)<tr><td>{{ $run->started_at ? $F::date($run->started_at, 'j F Y H:i:s') : '—' }}</td><td><span class="oc-pill {{ $run->status==='success'?'oc-good':($run->status==='failed'?'oc-bad':'oc-warn') }}">{{ ['success'=>'موفق','failed'=>'ناموفق','running'=>'در حال اجرا'][$run->status] ?? $run->status }}</span></td><td>{{ $run->filename ?: '—' }}</td><td>{{ $bytes($run->size_bytes) }}</td><td style="direction:ltr;text-align:left;overflow-wrap:anywhere">{{ $run->local_path ?: '—' }}</td><td>{{ $run->cloud_file_id ? 'ارسال شد · '.$run->cloud_file_id : ($settings['cloud_enabled'] ? 'ارسال نشده' : 'غیرفعال') }}</td><td>{{ $run->message ?: '—' }}</td></tr>@empty<tr><td colspan="7">هنوز بکاپی ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div><div style="margin-top:12px">{{ $backups->links() }}</div>
  </section>
  <section class="oc-section"><div class="oc-head"><h3 style="margin:0">تاریخچه سلامت</h3><span class="oc-muted">آخرین بررسی‌های زمان‌بندی‌شده</span></div>
    <div class="oc-table-wrap"><table class="oc-table"><thead><tr><th>زمان</th><th>وضعیت کلی</th><th>جزئیات بررسی‌ها</th></tr></thead><tbody>
    @forelse($health as $record)<tr><td>{{ $F::date($record->created_at, 'j F Y H:i:s') }}</td><td><span class="oc-pill {{ $record->status==='ok'?'oc-good':'oc-bad' }}">{{ $record->status==='ok'?'سالم':'هشدار' }}</span></td><td>@foreach($record->checks as $check)<div><strong>{{ $check['name'] ?? '' }}:</strong> {{ $check['detail'] ?? '' }} <span class="oc-muted">({{ $check['status'] ?? 'unknown' }})</span></div>@endforeach</td></tr>@empty<tr><td colspan="3">هنوز بررسی‌ای ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div><div style="margin-top:12px">{{ $health->links() }}</div>
  </section>
  <p class="oc-muted" style="margin-top:18px">نکته امنیتی: بکاپ شامل داده‌های خصوصی و فایل‌های بارگذاری‌شده است. مسیر محلی را خارج از public انتخاب کن، پوشهٔ Drive را فقط با حساب‌های مورداعتماد به اشتراک بگذار و پیش از اتکا به بکاپ، بازیابی را در محیط جداگانه آزمایش کن. بکاپ ابری و محلی جایگزین آزمایش بازیابی نیستند.</p>
</div>
@endsection
