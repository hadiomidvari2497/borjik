@extends('admin.layout')
@section('title', 'پایش عملکرد')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $names = ['database' => 'پایگاه داده', 'cache' => 'کش', 'storage' => 'خواندن و نوشتن دیسک'];
  $fmt = fn ($value) => is_numeric($value) ? number_format((float) $value, 2).' ms' : '—';
  $bytes = fn ($value) => is_numeric($value) ? ($value >= 1073741824 ? number_format($value / 1073741824, 2).' GB' : number_format($value / 1048576, 1).' MB') : '—';
@endphp
<style>
.pm-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}.pm-card{border:1px solid var(--line);border-radius:14px;padding:16px;min-width:0}.pm-muted{font-size:12px;color:var(--muted);line-height:1.9}.pm-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.pm-table{width:100%;border-collapse:collapse}.pm-table th,.pm-table td{padding:10px;border-bottom:1px solid var(--line);text-align:right;vertical-align:top}.pm-table th{font-size:12px;color:var(--muted)}.pm-pill{display:inline-flex;padding:4px 9px;border-radius:99px;font-size:11px;font-weight:800}.pm-ok{background:#e8f7ee;color:#17683a}.pm-fail{background:#fff0ef;color:#a52b24}.pm-unknown{background:var(--surface);color:var(--muted)}.pm-wrap{overflow:auto}
@media(max-width:900px){.pm-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.pm-grid{grid-template-columns:1fr}}

@media(prefers-color-scheme:dark){
 :root:not([data-theme="light"]) .pm-ok{background:rgba(47,160,96,.18);color:#91e0ae}
 :root:not([data-theme="light"]) .pm-fail{background:rgba(224,69,58,.18);color:#ffaaa3}
}
:root[data-theme="dark"] .pm-ok{background:rgba(47,160,96,.18);color:#91e0ae}
:root[data-theme="dark"] .pm-fail{background:rgba(224,69,58,.18);color:#ffaaa3}
</style>
<div class="card">
  <div class="pm-head"><div><h2 style="margin:0">پایش عملکرد برجیک</h2><p class="pm-muted">خط مبنای دیتابیس، کش، خواندن/نوشتن فایل و منابع قابل‌مشاهده از PHP؛ این صفحه فعلاً ابزار اندازه‌گیری است، نه ادعای بهینه‌سازی.</p></div>
    <form method="POST" action="{{ route('admin.monitoring.performance.sample') }}">@csrf<button class="btn btn-primary" type="submit">اندازه‌گیری همین حالا</button></form>
  </div>
  <p class="pm-muted">آخرین نمونه: {{ $latest ? $F::date($latest->sampled_at, 'j F Y - H:i:s') : 'هنوز نمونه‌ای ثبت نشده' }} · نمونه‌های ۲۴ ساعت اخیر: {{ $F::num($sampleCount) }} · میانگین فقط از نمونه‌های دارای عدد محاسبه می‌شود.</p>
  <div class="pm-grid">
    @foreach($names as $key => $name)
      <div class="pm-card"><span class="pm-muted">{{ $name }} · میانگین ۲۴ ساعت</span><h2 style="margin:8px 0">{{ $fmt($average[$key] ?? null) }}</h2><span class="pm-muted">آخرین مقدار: {{ $fmt(data_get($latest?->metrics, $key.'.latency_ms')) }}</span><div style="margin-top:10px"><span class="pm-pill {{ data_get($latest?->metrics, $key.'.status') === 'ok' ? 'pm-ok' : (data_get($latest?->metrics, $key.'.status') === 'fail' ? 'pm-fail' : 'pm-unknown') }}">{{ data_get($latest?->metrics, $key.'.status', 'نامشخص') === 'ok' ? 'عادی' : (data_get($latest?->metrics, $key.'.status') === 'fail' ? 'خطا' : 'نامشخص') }}</span></div></div>
    @endforeach
    <div class="pm-card"><span class="pm-muted">فضای دیسک</span><h2 style="margin:8px 0">{{ is_numeric(data_get($latest?->metrics,'disk.used_percent')) ? $F::num(data_get($latest?->metrics,'disk.used_percent')).'٪' : '—' }}</h2><span class="pm-muted">آزاد: {{ $bytes(data_get($latest?->metrics,'disk.free_bytes')) }} از {{ $bytes(data_get($latest?->metrics,'disk.total_bytes')) }}</span></div>
  </div>
  <div class="pm-grid">
    <div class="pm-card"><span class="pm-muted">مصرف حافظهٔ PHP در نمونه</span><h3>{{ $bytes(data_get($latest?->metrics,'runtime.memory_used_bytes')) }}</h3><span class="pm-muted">اوج مصرف: {{ $bytes(data_get($latest?->metrics,'runtime.memory_peak_bytes')) }} · محدودیت PHP: {{ data_get($latest?->metrics,'runtime.memory_limit','—') }}</span></div>
    <div class="pm-card"><span class="pm-muted">نسخه PHP</span><h3>{{ data_get($latest?->metrics,'runtime.php_version','—') }}</h3><span class="pm-muted">SAPI: {{ data_get($latest?->metrics,'runtime.php_sapi','—') }}</span></div>
    <div class="pm-card"><span class="pm-muted">Load average سرور</span><h3>{{ is_array(data_get($latest?->metrics,'runtime.load_average')) ? implode(' / ', array_map(fn($v)=>number_format((float)$v,2),data_get($latest->metrics,'runtime.load_average'))) : 'در دسترس نیست' }}</h3><span class="pm-muted">روی بعضی هاست‌های اشتراکی قابل‌خواندن نیست.</span></div>
    <div class="pm-card"><span class="pm-muted">مدت نگهداری داده‌ها</span><h3>۳۰ روز</h3><span class="pm-muted">نمونه‌های قدیمی‌تر به‌صورت خودکار حذف می‌شوند.</span></div>
  </div>
  <h3 style="margin-top:24px">تاریخچهٔ اندازه‌گیری</h3>
  <div class="pm-wrap"><table class="pm-table"><thead><tr><th>زمان</th><th>دیتابیس</th><th>کش</th><th>فایل</th><th>مصرف دیسک</th><th>حافظه PHP</th><th>وضعیت</th></tr></thead><tbody>
  @forelse($samples as $sample)
    <tr><td>{{ $F::date($sample->sampled_at,'j F Y - H:i:s') }}</td><td>{{ $fmt(data_get($sample->metrics,'database.latency_ms')) }}</td><td>{{ $fmt(data_get($sample->metrics,'cache.latency_ms')) }}</td><td>{{ $fmt(data_get($sample->metrics,'storage.latency_ms')) }}</td><td>{{ is_numeric(data_get($sample->metrics,'disk.used_percent')) ? $F::num(data_get($sample->metrics,'disk.used_percent')).'٪' : '—' }}</td><td>{{ $bytes(data_get($sample->metrics,'runtime.memory_used_bytes')) }}</td><td><span class="pm-pill {{ $sample->status === 'ok' ? 'pm-ok' : 'pm-fail' }}">{{ $sample->status === 'ok' ? 'عادی' : 'نیازمند بررسی' }}</span></td></tr>
  @empty<tr><td colspan="7">هنوز داده‌ای ثبت نشده؛ یک اندازه‌گیری دستی اجرا کن یا زمان‌بندی را فعال کن.</td></tr>@endforelse
  </tbody></table></div>
  <div style="margin-top:14px">{{ $samples->links() }}</div>
  <p class="pm-muted" style="margin-top:18px">محدودیت فعلی: این نمونه‌ها زمان پاسخ درخواست‌های واقعی، پرس‌وجوهای کند و CPU دقیق VPS را اندازه نمی‌گیرند. برای این موارد باید مرحلهٔ بعدی ابزارسازی و ثبت کنترل‌شدهٔ درخواست‌های کند اضافه شود. روی سرورهای چندکاربره، load average و دیسک ممکن است نمای کل سیستم باشند، نه سهم اختصاصی برنامه.</p>
</div>
@endsection
