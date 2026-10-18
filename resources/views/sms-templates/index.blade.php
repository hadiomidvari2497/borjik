@php
  $F = \App\Support\Fa::class;
  $labels = [
    'otp' => 'کد ورود یک‌بارمصرف',
    'expense_reminder' => 'یادآوری ثبت هزینه',
    'receipt_approved' => 'تأیید پرداخت',
    'receipt_rejected' => 'رد پرداخت',
    'rent_invoice_issued' => 'صدور صورت‌حساب اجاره',
    'invitation' => 'دعوت به ساختمان',
    'task_created' => 'ایجاد تسک',
    'task_assigned' => 'ارجاع تسک',
    'task_status_changed' => 'تغییر وضعیت تسک',
  ];
  $variableHints = [
    'code' => 'کد یک‌بارمصرف', 'name' => 'نام مخاطب', 'building' => 'نام ساختمان',
    'month' => 'ماه', 'date' => 'تاریخ', 'amount' => 'مبلغ', 'unit' => 'شماره واحد',
    'reason' => 'دلیل رد', 'inviter' => 'دعوت‌کننده', 'role' => 'نقش دعوت‌شده',
    'link' => 'لینک دعوت', 'title' => 'عنوان تسک', 'number' => 'شماره تسک', 'status' => 'وضعیت تسک',
  ];
  $preview = fn ($body) => strtr($body, array_combine(
    array_map(fn ($key) => '{'.$key.'}', array_keys($samples)),
    array_values($samples)
  ));
@endphp
@extends('layouts.app')
@section('title', 'قالب‌های پیامک')
@section('content')
<style>
.sms-page{display:grid;gap:16px}.sms-hero{padding:23px 25px;border-radius:20px;color:#fff;background:linear-gradient(115deg,#173f50,#287d83);display:flex;gap:20px;align-items:center;justify-content:space-between}.sms-hero h2{font-size:21px;margin:0 0 7px}.sms-hero p{font-size:12px;line-height:1.9;opacity:.88;margin:0;max-width:700px}.sms-counts{display:flex;gap:10px;flex-wrap:wrap}.sms-count{min-width:110px;border:1px solid rgba(255,255,255,.22);border-radius:14px;padding:12px;background:rgba(255,255,255,.08)}.sms-count strong{display:block;font-size:21px}.sms-count span{font-size:10px;opacity:.85}.sms-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.sms-card{padding:18px;min-width:0}.sms-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px}.sms-card h3{font-size:14px;margin:0 0 5px}.sms-key{font-size:10px;color:var(--muted);direction:ltr;display:inline-block}.sms-state{font-size:10px;font-weight:800;padding:5px 9px;border-radius:9px;white-space:nowrap;background:var(--brand-soft);color:var(--brand-d)}.sms-state.off{background:rgba(200,70,70,.12);color:#b33}.sms-textarea{min-height:105px;line-height:1.9;resize:vertical}.sms-preview{background:var(--surface-2,var(--bg));border:1px dashed var(--line);border-radius:13px;padding:12px;margin-top:10px}.sms-preview small{display:block;color:var(--muted);font-size:10px;margin-bottom:5px}.sms-preview p{margin:0;font-size:12px;line-height:2;overflow-wrap:anywhere;white-space:pre-wrap}.sms-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:12px}.sms-active{display:flex;align-items:center;gap:7px;font-size:11px;color:var(--muted);margin-inline-end:auto}.sms-active input{width:auto}.sms-vars{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.sms-var{font-size:10px;direction:ltr;border-radius:7px;padding:4px 7px;background:var(--brand-soft);color:var(--brand-d)}.sms-default{border-style:dashed}.sms-empty{padding:24px;text-align:center;color:var(--muted);font-size:12px}.sms-section-title{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px}.sms-section-title h2{font-size:16px;margin:0}.sms-form-grid{display:grid;grid-template-columns:minmax(180px,1fr) minmax(240px,2fr);gap:12px}.sms-help{font-size:11px;color:var(--muted);line-height:1.9;margin-top:10px}
@media(max-width:760px){.sms-grid{grid-template-columns:1fr}.sms-hero{align-items:flex-start;flex-direction:column}.sms-form-grid{grid-template-columns:1fr}.sms-count{min-width:95px}}
</style>
<div class="sms-page">
  <section class="sms-hero">
    <div><h2>قالب‌های پیامک</h2><p>متن پیامک‌های خودکار ساختمان را شخصی‌سازی کن. متغیرهای داخل آکولاد مثل <code>{building}</code> هنگام ارسال با اطلاعات واقعی جایگزین می‌شوند. این تغییرات فقط روی ساختمان فعال اثر دارند.</p></div>
    <div class="sms-counts"><div class="sms-count"><strong>{{ $F::num($templates->count()) }}</strong><span>قالب اختصاصی</span></div><div class="sms-count"><strong>{{ $F::num($templates->where('is_active', true)->count()) }}</strong><span>قالب فعال</span></div></div>
  </section>

  <section class="card">
    <div class="sms-section-title"><h2>قالب‌های اختصاصی ساختمان</h2><span class="muted" style="font-size:11px">قالب فعال جایگزین متن پیش‌فرض می‌شود.</span></div>
    @if($templates->isEmpty())
      <div class="sms-empty">هنوز قالب اختصاصی نساخته‌ای. می‌توانی از فرم پایین یک نوع پیامک را انتخاب و متن آن را شخصی‌سازی کنی.</div>
    @else
      <div class="sms-grid">
        @foreach($templates as $template)
          <article class="card sms-card">
            <div class="sms-card-head"><div><h3>{{ $labels[$template->key] ?? $template->key }}</h3><span class="sms-key">{{ $template->key }}</span></div><span class="sms-state {{ $template->is_active ? '' : 'off' }}">{{ $template->is_active ? 'فعال' : 'غیرفعال' }}</span></div>
            <form method="POST" action="{{ route('sms-templates.update', $template->id) }}">@csrf @method('PUT')
              <label for="body-{{ $template->id }}">متن قالب</label>
              <textarea class="sms-textarea" id="body-{{ $template->id }}" name="body" maxlength="5000" required>{{ old('body', $template->body) }}</textarea>
              @error('body')<div class="e">{{ $message }}</div>@enderror
              <div class="sms-vars">@foreach($variableHints as $key => $hint)<span class="sms-var" title="{{ $hint }}">{{ '{'.$key.'}' }}</span>@endforeach</div>
              <div class="sms-preview"><small>پیش‌نمایش نمونه</small><p>{{ $preview($template->body) }}</p></div>
              <div class="sms-actions"><label class="sms-active"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))> قالب فعال باشد</label><button class="btn sm" type="submit">ذخیره تغییرات</button></div>
            </form>
            <form method="POST" action="{{ route('sms-templates.destroy', $template->id) }}" data-confirm="قالب اختصاصی حذف شود؟ متن پیش‌فرض دوباره استفاده خواهد شد." data-confirm-title="حذف قالب پیامک" data-confirm-icon="warning" style="margin-top:8px">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف قالب اختصاصی</button></form>
          </article>
        @endforeach
      </div>
    @endif
  </section>

  <section class="card">
    <div class="sms-section-title"><h2>افزودن یا شخصی‌سازی قالب</h2></div>
    <form method="POST" action="{{ route('sms-templates.store') }}">@csrf
      <div class="sms-form-grid">
        <div class="fld"><label for="sms-key">نوع پیامک *</label><select id="sms-key" name="key" required><option value="">انتخاب نوع پیامک</option>@foreach($defaults as $default)@unless($default['is_overridden'])<option value="{{ $default['key'] }}" @selected(old('key') === $default['key'])>{{ $labels[$default['key']] ?? $default['key'] }} ({{ $default['key'] }})</option>@endunless @endforeach</select>@error('key')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="sms-body">متن پیامک *</label><textarea class="sms-textarea" id="sms-body" name="body" maxlength="5000" required placeholder="متن قالب را بنویس؛ متغیرها مانند {building} حفظ شوند.">{{ old('body') }}</textarea>@error('body')<div class="e">{{ $message }}</div>@enderror</div>
      </div>
      <div class="sms-vars">@foreach($variableHints as $key => $hint)<span class="sms-var" title="{{ $hint }}">{{ '{'.$key.'}' }} · {{ $hint }}</span>@endforeach</div>
      <p class="sms-help">متغیرها باید دقیقاً با نام انگلیسی داخل آکولاد نوشته شوند. اگر قالب غیرفعال شود یا حذف شود، سامانه به متن پیش‌فرض پیامک برمی‌گردد. برای حفظ امنیت، قالب کد ورود را با دقت تغییر بده.</p>
      <div class="sms-actions"><label class="sms-active"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> بلافاصله فعال شود</label><button class="btn" type="submit">ذخیره قالب</button></div>
    </form>
  </section>

  <section class="card">
    <div class="sms-section-title"><h2>متن‌های پیش‌فرض سیستم</h2><span class="muted" style="font-size:11px">فقط برای مشاهده</span></div>
    <div class="sms-grid">
      @foreach($defaults as $default)
        <article class="card sms-card sms-default"><div class="sms-card-head"><div><h3>{{ $labels[$default['key']] ?? $default['key'] }}</h3><span class="sms-key">{{ $default['key'] }}</span></div><span class="sms-state">{{ $default['is_overridden'] ? 'شخصی‌سازی شده' : 'پیش‌فرض' }}</span></div><div class="sms-preview"><p>{{ $preview($default['body']) }}</p></div></article>
      @endforeach
    </div>
  </section>
</div>
@endsection
