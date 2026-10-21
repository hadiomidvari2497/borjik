@extends('layouts.app')
@section('title','نگهداری و تعمیرات')
@section('content')
@php($jalaliToday = \Morilog\Jalali\Jalalian::now()->format('Y/m/d'))
<style>
.mt-hero{padding:20px;border-radius:16px;background:linear-gradient(120deg,#194f49,#287e6d);color:white;margin-bottom:16px}.mt-hero h2{margin:0 0 6px}.mt-hero p{margin:0;color:#d8f1e9;line-height:1.9}.mt-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.mt-card{border:1px solid var(--line);border-radius:14px;padding:16px;background:var(--surface);min-width:0}.mt-card h3{margin:0 0 14px;font-size:15px}.mt-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.mt-form label{display:block;font-size:12px;color:var(--muted);margin-bottom:5px}.mt-form input,.mt-form select,.mt-form textarea{width:100%;min-width:0}.mt-form .wide{grid-column:1/-1}.mt-btn{border:0;border-radius:9px;padding:9px 13px;background:#176c5d;color:#fff;font:inherit;font-size:12px;font-weight:800;cursor:pointer}.mt-tablewrap{overflow:auto;margin-top:10px}.mt-table{width:100%;border-collapse:collapse;min-width:600px}.mt-table th,.mt-table td{padding:10px;border-bottom:1px solid var(--line);text-align:right;vertical-align:top}.mt-table th{font-size:12px;color:var(--muted)}.mt-muted{color:var(--muted);font-size:12px;line-height:1.8}.mt-pill{display:inline-block;padding:4px 8px;border-radius:20px;background:#fff2db;color:#895600;font-size:11px}.mt-inline{display:flex;align-items:center;gap:6px;flex-wrap:wrap}.mt-inline input{max-width:125px}.mt-empty{padding:14px;color:var(--muted);font-size:13px}
@media(max-width:800px){.mt-grid{grid-template-columns:1fr}}@media(max-width:520px){.mt-form{grid-template-columns:1fr}.mt-form .wide{grid-column:auto}}
</style>
<div class="mt-hero"><h2>نگهداری پیشگیرانه ساختمان</h2><p>تجهیزات را ثبت کن، برنامه سرویس دوره‌ای بساز و پس از انجام هر سرویس، هزینه و سابقه را نگه دار. تاریخ‌ها با تقویم شمسی وارد می‌شوند.</p></div>
<div class="mt-grid">
 <section class="mt-card"><h3>ثبت تجهیز جدید</h3><form method="POST" action="{{ route('maintenance.assets.store') }}">@csrf
  <div class="mt-form">
   <div><label for="asset-name">نام تجهیز *</label><input id="asset-name" name="name" required maxlength="150" placeholder="مثلاً آسانسور بلوک A"></div>
   <div><label for="asset-category">دسته‌بندی</label><input id="asset-category" name="category" maxlength="80" placeholder="آسانسور، پمپ، موتورخانه..."></div>
   <div><label for="asset-location">محل نصب</label><input id="asset-location" name="location" maxlength="150"></div>
   <div><label for="asset-manufacturer">سازنده</label><input id="asset-manufacturer" name="manufacturer" maxlength="120"></div>
   <div><label for="asset-model">مدل</label><input id="asset-model" name="model" maxlength="120"></div>
   <div><label for="asset-serial">شماره سریال</label><input id="asset-serial" name="serial_number" maxlength="120"></div>
   <div><label for="asset-installed">تاریخ نصب (شمسی)</label><input id="asset-installed" name="installed_at" class="jdate" autocomplete="off" placeholder="۱۴۰۵/۰۷/۲۹"></div>
   <div><label for="asset-warranty">پایان ضمانت (شمسی)</label><input id="asset-warranty" name="warranty_until" class="jdate" autocomplete="off" placeholder="۱۴۰۶/۰۷/۲۹"></div>
   <div class="wide"><label for="asset-notes">یادداشت</label><textarea id="asset-notes" name="notes" rows="2" maxlength="5000"></textarea></div>
   <div class="wide"><button class="mt-btn" type="submit">ثبت تجهیز</button></div>
  </div>
 </form></section>
 <section class="mt-card"><h3>تعریف برنامه سرویس</h3>
  @if($assets->isEmpty())<div class="mt-empty">ابتدا یک تجهیز ثبت کن تا بتوانی برایش برنامه سرویس بسازی.</div>@else
  <form method="POST" action="{{ route('maintenance.plans.store') }}">@csrf
   <div class="mt-form">
    <div><label for="plan-asset">تجهیز *</label><select id="plan-asset" name="asset_id" required><option value="">انتخاب تجهیز</option>@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->name }}</option>@endforeach</select></div>
    <div><label for="plan-title">عنوان سرویس *</label><input id="plan-title" name="title" required maxlength="180" placeholder="بازدید فنی و ایمنی"></div>
    <div><label for="plan-frequency">تناوب</label><select id="plan-frequency" name="frequency"><option value="weekly">هفتگی</option><option value="monthly" selected>ماهانه</option><option value="quarterly">هر سه ماه</option><option value="semiannual">هر شش ماه</option><option value="annual">سالانه</option><option value="custom">بازه دلخواه (روز)</option></select></div>
    <div id="plan-interval-wrap" hidden><label for="plan-interval">فاصله به روز</label><input id="plan-interval" type="number" name="interval_days" min="1" max="3650"></div>
    <div><label for="plan-next">سررسید بعدی (شمسی) *</label><input id="plan-next" name="next_due_at" class="jdate" autocomplete="off" required value="{{ $jalaliToday }}"></div>
    <div><label for="plan-cost">هزینه تخمینی (ریال)</label><input id="plan-cost" type="number" name="estimated_cost" min="0" max="99999999999"></div>
    <div><label for="plan-assignee">مسئول (اختیاری)</label><select id="plan-assignee" name="assigned_to"><option value="">تعیین نشده</option>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->display_name ?: $person->mobile }}</option>@endforeach</select></div>
    <div class="wide"><label for="plan-description">شرح کار</label><textarea id="plan-description" name="description" rows="2" maxlength="5000"></textarea></div>
    <div class="wide"><button class="mt-btn" type="submit">ثبت برنامه سرویس</button></div>
   </div>
  </form>@endif
 </section>
</div>
<section class="mt-card" style="margin-top:14px"><h3>برنامه‌های فعال و سررسیدها</h3>
 @if($plans->isEmpty())<div class="mt-empty">هنوز برنامه‌ای تعریف نشده است.</div>@else
 <div class="mt-tablewrap"><table class="mt-table"><thead><tr><th>تجهیز</th><th>سرویس</th><th>تناوب</th><th>سررسید</th><th>مسئول</th><th>ثبت انجام سرویس</th></tr></thead><tbody>
 @foreach($plans as $plan)<tr><td><strong>{{ $plan->asset?->name }}</strong><div class="mt-muted">{{ $plan->asset?->location }}</div></td><td>{{ $plan->title }}<div class="mt-muted">{{ $plan->description }}</div></td><td>{{ ['weekly'=>'هفتگی','monthly'=>'ماهانه','quarterly'=>'سه‌ماهه','semiannual'=>'شش‌ماهه','annual'=>'سالانه','custom'=>'بازه دلخواه'][$plan->frequency] ?? $plan->frequency }}</td><td><span class="mt-pill">{{ $F::date($plan->next_due_at,'j F Y') }}</span>@if($plan->next_due_at->lt(today()) && !$plan->next_due_at->isToday())<div class="mt-muted">سررسید گذشته</div>@endif</td><td>{{ $plan->assignee?->display_name ?: $plan->assignee?->mobile ?: '—' }}</td><td><form method="POST" action="{{ route('maintenance.plans.complete',$plan) }}">@csrf<div class="mt-inline"><input name="performed_at" class="jdate" autocomplete="off" required value="{{ $jalaliToday }}" aria-label="تاریخ انجام شمسی"><input name="cost" type="number" min="0" placeholder="هزینه" aria-label="هزینه نهایی"><button class="mt-btn" type="submit">انجام شد</button></div><textarea name="notes" rows="1" placeholder="یادداشت انجام کار (اختیاری)" style="width:100%;margin-top:6px"></textarea></form></td></tr>@endforeach
 </tbody></table></div>@endif
</section>
<section class="mt-card" style="margin-top:14px"><h3>تاریخچه سرویس‌های انجام‌شده</h3>
 @if($records->isEmpty())<div class="mt-empty">هنوز سرویس انجام‌شده‌ای ثبت نشده است.</div>@else<div class="mt-tablewrap"><table class="mt-table"><thead><tr><th>تاریخ</th><th>تجهیز</th><th>سرویس</th><th>هزینه نهایی (ریال)</th><th>ثبت‌کننده</th><th>یادداشت</th></tr></thead><tbody>
 @foreach($records as $record)<tr><td>{{ $F::date($record->performed_at,'j F Y') }}</td><td>{{ $record->asset?->name }}</td><td>{{ $record->title }}</td><td>{{ $F::digits(number_format($record->cost)) }}</td><td>{{ $record->performer?->display_name ?: '—' }}</td><td>{{ $record->notes ?: '—' }}</td></tr>@endforeach
 </tbody></table></div>@endif
</section>
<script>
(() => { const f=document.getElementById('plan-frequency'), w=document.getElementById('plan-interval-wrap'), i=document.getElementById('plan-interval'); if(!f)return; const sync=()=>{w.hidden=f.value!=='custom';i.required=f.value==='custom';i.disabled=f.value!=='custom'};f.addEventListener('change',sync);sync(); })();
</script>
@endsection