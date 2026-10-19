@extends('admin.layout')
@section('title', 'سلامت هاست')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $ok = collect($checks)->where('status', 'ok')->count();
  $bad = collect($checks)->where('status', 'fail')->count();
  $bytes = fn ($n) => $n === null ? 'نامشخص' : ($n >= 1073741824 ? number_format($n / 1073741824, 2).' GB' : number_format($n / 1048576, 1).' MB');
@endphp
<style>
.hm-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}.hm-card{border:1px solid var(--line);border-radius:14px;padding:15px;min-width:0}.hm-label{font-size:12px;color:var(--muted);display:block;margin-bottom:8px}.hm-value{font-size:22px;font-weight:850;overflow-wrap:anywhere}.hm-sub{font-size:12px;color:var(--muted);margin-top:7px}.hm-bar{height:7px;background:var(--line);border-radius:20px;overflow:hidden;margin-top:12px}.hm-bar span{display:block;height:100%;background:#16804a;border-radius:20px}.hm-health{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.hm-check{border:1px solid var(--line);border-radius:12px;padding:13px}.hm-check h4{margin:0 0 7px;font-size:14px}.hm-check p{font-size:12px;color:var(--muted);overflow-wrap:anywhere}.hm-ok{color:#16804a;font-weight:800}.hm-fail{color:#c0392b;font-weight:800}.hm-table{width:100%;border-collapse:collapse}.hm-table td{padding:10px;border-bottom:1px solid var(--line);overflow-wrap:anywhere}.hm-table td:first-child{color:var(--muted);width:38%}.hm-copy{border:1px solid var(--line);border-radius:8px;padding:7px 10px;background:transparent;color:inherit;cursor:pointer}.hm-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}.hm-section{margin-top:24px}.hm-note{font-size:12px;line-height:1.9;color:var(--muted)}
@media(max-width:900px){.hm-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.hm-health{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.hm-grid,.hm-health{grid-template-columns:1fr}.hm-value{font-size:20px}}

@media(prefers-color-scheme:dark){
 :root:not([data-theme="light"]) .hm-ok{color:#91e0ae}
 :root:not([data-theme="light"]) .hm-fail{color:#ffaaa3}
}
:root[data-theme="dark"] .hm-ok{color:#91e0ae}
:root[data-theme="dark"] .hm-fail{color:#ffaaa3}

</style>
<div class="card">
  <div class="hm-head"><div><h2 style="margin:0">مانیتورینگ هاست</h2><p class="hm-note">نمای لحظه‌ای از منابع قابل‌مشاهدهٔ سرور و بررسی سرویس‌های ضروری برنامه.</p><p class="hm-note">آخرین بررسی: {{ $F::date($checkedAt, 'j F Y - H:i:s') }}</p></div><div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn sec" type="button" onclick="location.reload()">↻ به‌روزرسانی</button><button class="hm-copy" id="hm-copy" type="button">کپی تمام گزارش</button></div></div>
  <div class="hm-grid" id="hm-summary">
    <div class="hm-card"><span class="hm-label">فضای دیسک مصرف‌شده</span><div class="hm-value">{{ $disk['used_percent'] === null ? 'نامشخص' : $F::num($disk['used_percent']).'٪' }}</div><div class="hm-sub">{{ $bytes($disk['free']) }} آزاد از {{ $bytes($disk['total']) }}</div>@if($disk['used_percent'] !== null)<div class="hm-bar"><span style="width:{{ min(100, $disk['used_percent']) }}%;{{ $disk['used_percent'] >= 90 ? 'background:#c0392b' : '' }}"></span></div>@endif</div>
    <div class="hm-card"><span class="hm-label">مصرف حافظهٔ سیستم</span><div class="hm-value">{{ $memory ? $F::num($memory['used_percent']).'٪' : 'نامشخص' }}</div><div class="hm-sub">{{ $memory ? $bytes($memory['available']).' در دسترس از '.$bytes($memory['total']) : 'اطلاعات حافظه در این سیستم‌عامل در دسترس نیست' }}</div>@if($memory)<div class="hm-bar"><span style="width:{{ min(100, $memory['used_percent']) }}%;{{ $memory['used_percent'] >= 90 ? 'background:#c0392b' : '' }}"></span></div>@endif</div>
    <div class="hm-card"><span class="hm-label">بار پردازنده (Load Average)</span><div class="hm-value">{{ $load ? implode(' / ', array_map(fn($n) => number_format($n, 2), $load)) : 'نامشخص' }}</div><div class="hm-sub">میانگین ۱، ۵ و ۱۵ دقیقه؛ ممکن است در هاست اشتراکی مخفی باشد.</div></div>
    <div class="hm-card"><span class="hm-label">مدت روشن‌بودن سیستم</span><div class="hm-value" style="font-size:18px">{{ $uptime ?: 'نامشخص' }}</div><div class="hm-sub">این مقدار در صورت دسترسی سیستم‌عامل نمایش داده می‌شود.</div></div>
  </div>
  <section class="hm-section"><div class="hm-head"><h3 style="margin:0">سلامت سرویس‌ها</h3><span class="hm-note">سالم: {{ $F::num($ok) }} · نیازمند بررسی: {{ $F::num($bad) }}</span></div><div class="hm-health" style="margin-top:12px" id="hm-checks">@foreach($checks as $check)<article class="hm-check" data-name="{{ $check['name'] }}" data-status="{{ $check['status'] }}" data-detail="{{ $check['detail'] }}" data-message="{{ $check['message'] }}"><h4>{{ $check['name'] }}</h4><p>{{ $check['detail'] }}</p><span class="{{ $check['status'] === 'ok' ? 'hm-ok' : 'hm-fail' }}">{{ $check['status'] === 'ok' ? '● سالم' : '● نیازمند بررسی' }}</span><p>{{ $check['message'] }}</p></article>@endforeach</div></section>
  <section class="hm-section"><div class="hm-head"><h3 style="margin:0">مشخصات محیط اجرا</h3><button class="hm-copy" type="button" id="hm-copy-details">کپی مشخصات</button></div><div class="hm-card" style="margin-top:12px"><table class="hm-table" id="hm-details"><tbody>
    <tr><td>سیستم‌عامل</td><td>{{ $server['os'] }}</td></tr><tr><td>وب‌سرور</td><td>{{ $server['server'] }}</td></tr><tr><td>PHP SAPI</td><td>{{ $server['sapi'] }}</td></tr><tr><td>محیط برنامه</td><td>{{ $server['environment'] }}</td></tr><tr><td>منطقه زمانی</td><td>{{ $server['timezone'] }}</td></tr><tr><td>محدودیت حافظه PHP</td><td>{{ $server['php_memory_limit'] }}</td></tr><tr><td>حداکثر زمان اجرا</td><td>{{ $server['max_execution_time'] }} ثانیه</td></tr>
    @foreach($server['extensions'] as $extension => $enabled)<tr><td>افزونه {{ $extension }}</td><td>{{ $enabled ? 'فعال' : 'غیرفعال' }}</td></tr>@endforeach
  </tbody></table></div></section>
  <p class="hm-note" style="margin-top:18px">محدودیت مهم: روی هاست اشتراکی ممکن است اطلاعات RAM، Load و uptime سیستم‌عامل قابل‌دسترسی نباشد؛ در این صورت «نامشخص» نشان داده می‌شود، نه اینکه وضعیت سالم فرض شود. این صفحه بررسی‌های برنامه را هنگام بازشدن صفحه انجام می‌دهد و جایگزین ابزار مانیتورینگ سطح سیستم یا هشدار خارج از سایت نیست.</p>
</div>
<div id="hm-toast" class="hm-note" role="status" aria-live="polite" style="display:none"></div>
@push('scripts')
<script>
(function(){
 const toast=document.getElementById('hm-toast');
 async function copy(text){try{if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(text)}else{const el=document.createElement('textarea');el.value=text;el.style.position='fixed';el.style.opacity='0';document.body.appendChild(el);el.select();if(!document.execCommand('copy'))throw new Error('copy failed');el.remove()}toast.textContent='گزارش در کلیپ‌بورد کپی شد.';toast.style.display='block'}catch(e){toast.textContent='کپی انجام نشد؛ مجوز کلیپ‌بورد مرورگر را بررسی کن.';toast.style.display='block'}}
 const checks=()=>Array.from(document.querySelectorAll('#hm-checks [data-name]')).map(e=>e.dataset.name+' — '+(e.dataset.status==='ok'?'سالم':'نیازمند بررسی')+'\n'+e.dataset.detail+'\n'+e.dataset.message).join('\n\n');
 const details=()=>Array.from(document.querySelectorAll('#hm-details tr')).map(r=>Array.from(r.cells).map(c=>c.innerText.trim()).join(': ')).join('\n');
 document.getElementById('hm-copy').addEventListener('click',()=>copy('گزارش مانیتورینگ هاست برجیک\nزمان بررسی: {{ $checkedAt->format('Y-m-d H:i:s') }}\nدیسک: {{ $disk['used_percent'] === null ? 'نامشخص' : $disk['used_percent'].'%' }}\nRAM: {{ $memory ? $memory['used_percent'].'%' : 'نامشخص' }}\nLoad: {{ $load ? implode(' / ', $load) : 'نامشخص' }}\nUptime: {{ $uptime ?: 'نامشخص' }}\n\n'+checks()+'\n\n'+details()));
 document.getElementById('hm-copy-details').addEventListener('click',()=>copy(details()));
})();
</script>
@endpush
@endsection
