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
</style>
<div class="card">
  <div class="page-h">
    <div><h2>مرکز خطا و عیب‌یابی</h2><p style="color:var(--muted);font-size:13px;line-height:1.8;margin:7px 0 0">نمایش خطاهای ثبت‌شده و بررسی سلامت اجزای اصلی سامانه. اطلاعات حساس و محتوای فرم‌ها در گزارش ذخیره نمی‌شود.</p></div>
    <div class="sd-toolbar"><button type="button" class="btn sec" onclick="window.location.reload()">↻ تازه‌سازی</button><button type="button" class="btn" id="sd-copy-health">کپی گزارش سلامت</button></div>
  </div>
  <div class="sd-stats">
    <div class="sd-stat"><small>خطاهای امروز</small><strong>{{ $F::num($stats['today']) }}</strong></div>
    <div class="sd-stat"><small>خطاهای ۷ روز اخیر</small><strong>{{ $F::num($stats['week']) }}</strong></div>
    <div class="sd-stat"><small>کل تاریخچه خطاها</small><strong>{{ $F::num($stats['total']) }}</strong></div>
    <div class="sd-stat"><small>پیامک ناموفق در ۲۴ ساعت</small><strong>{{ $F::num($stats['failed_sms']) }}</strong></div>
  </div>

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
    copyText('گزارش سلامت برجیک\n' + lines.join('\n\n'));
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
