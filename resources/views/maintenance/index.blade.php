@extends('layouts.app')
@section('title','نگهداری و تعمیرات')
@section('content')
@php
    $jalaliToday = \Morilog\Jalali\Jalalian::now()->format('Y/m/d');
    $overduePlans = $plans->filter(fn ($plan) => $plan->next_due_at && $plan->next_due_at->lt(today()));
    $dueSoonPlans = $plans->filter(fn ($plan) => $plan->next_due_at && !$plan->next_due_at->lt(today()) && $plan->next_due_at->lte(today()->copy()->addDays(7)));
    $maintenanceCost = (int) $records->sum('cost');
    $frequencyLabels = ['weekly'=>'هفتگی','monthly'=>'ماهانه','quarterly'=>'هر سه ماه','semiannual'=>'هر شش ماه','annual'=>'سالانه','custom'=>'بازه دلخواه'];
@endphp
<style>
.maintenance-page{display:grid;gap:20px;min-width:0;color:var(--text)}
.maintenance-page *{box-sizing:border-box}
.mt-hero{position:relative;isolation:isolate;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:22px;padding:clamp(22px,3vw,34px);border-radius:24px;color:#fff;background:radial-gradient(ellipse at 8% 0%,rgba(138,239,210,.22),transparent 35%),radial-gradient(ellipse at 100% 100%,rgba(94,205,177,.18),transparent 38%),linear-gradient(118deg,#102b3b 0%,#155b59 55%,#278778 100%);box-shadow:0 18px 40px rgba(14,72,68,.16)}
.mt-hero:before{content:"";position:absolute;z-index:-1;inset-inline-end:-70px;top:-155px;width:330px;height:330px;border:1px solid rgba(255,255,255,.16);border-radius:50%;box-shadow:0 0 0 24px rgba(255,255,255,.035),0 0 0 52px rgba(255,255,255,.025),0 0 0 82px rgba(255,255,255,.018)}
.mt-hero:after{content:"";position:absolute;z-index:-1;inset-block:24px;inset-inline-start:0;width:4px;border-radius:8px;background:linear-gradient(#baf5df,#4bc6ac)}
.mt-hero-copy{min-width:0}.mt-kicker{display:inline-flex;align-items:center;gap:8px;margin-bottom:12px;padding:6px 11px;border:1px solid rgba(255,255,255,.2);border-radius:999px;background:rgba(255,255,255,.08);color:#dcfff2;font-size:11px;font-weight:800}.mt-kicker:before{content:"";width:6px;height:6px;border-radius:50%;background:#a3f3d4;box-shadow:0 0 0 4px rgba(163,243,212,.12)}
.mt-hero h2{margin:0 0 8px;color:#fff;font-size:clamp(23px,3vw,32px);font-weight:900;line-height:1.5;letter-spacing:-.5px}.mt-hero p{max-width:680px;margin:0;color:#d6eee8;font-size:12px;line-height:2.1}
.mt-hero-art{flex:0 0 84px;width:84px;height:84px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.23);border-radius:25px;background:linear-gradient(145deg,rgba(255,255,255,.17),rgba(255,255,255,.045));box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 14px 30px rgba(3,35,37,.13)}
.mt-hero-art svg{width:39px;height:39px;stroke:#e3fff4;stroke-width:1.5;fill:none}
.mt-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.mt-stat{position:relative;overflow:hidden;min-width:0;padding:17px;border:1px solid var(--line);border-radius:17px;background:var(--card);box-shadow:0 5px 18px rgba(17,44,55,.035)}
.mt-stat:after{content:"";position:absolute;inset-inline-end:-17px;bottom:-27px;width:78px;height:78px;border-radius:24px;background:var(--mt-tint,rgba(30,135,116,.08));transform:rotate(25deg)}
.mt-stat-head{display:flex;align-items:center;justify-content:space-between;gap:8px;color:var(--muted);font-size:11px;font-weight:750}
.mt-stat-icon{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:11px;background:var(--mt-tint,rgba(30,135,116,.1));color:var(--mt-accent,#1b8071)}
.mt-stat-icon svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.8}
.mt-stat strong{display:block;margin-top:8px;font-size:clamp(21px,2vw,27px);font-weight:900;line-height:1.35;letter-spacing:-.4px;font-variant-numeric:tabular-nums}
.mt-stat small{display:block;margin-top:4px;color:var(--muted);font-size:10px;line-height:1.8}
.mt-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;align-items:start}
.mt-card{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:18px;background:var(--card);box-shadow:0 5px 20px rgba(17,44,55,.035)}
.mt-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:19px 20px 15px;border-bottom:1px solid var(--line)}
.mt-card-heading{display:flex;align-items:flex-start;gap:11px;min-width:0}
.mt-card-icon{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border-radius:12px;background:var(--brand-soft,rgba(30,135,116,.1));color:var(--brand-d,#176c5d)}
.mt-card-icon svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:1.7}
.mt-card h3{margin:0;font-size:14px;font-weight:900;line-height:1.8}
.mt-card-subtitle{margin:3px 0 0;color:var(--muted);font-size:10px;line-height:1.8}
.mt-card-body{padding:19px 20px}
.mt-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}
.mt-field{min-width:0}.mt-form label{display:block;margin-bottom:6px;color:var(--muted);font-size:11px;font-weight:750}
.mt-form input,.mt-form select,.mt-form textarea,.mt-inline input,.mt-record-form textarea{display:block;width:100%;min-width:0;max-width:100%;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--text);padding:10px 11px;font:inherit;font-size:12px;line-height:1.6;transition:border-color .18s ease,box-shadow .18s ease}
.mt-form input:focus,.mt-form select:focus,.mt-form textarea:focus,.mt-inline input:focus,.mt-record-form textarea:focus{outline:none;border-color:var(--brand,#238c7d);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand,#238c7d) 13%,transparent)}
.mt-form input::placeholder,.mt-form textarea::placeholder,.mt-inline input::placeholder,.mt-record-form textarea::placeholder{color:var(--muted);opacity:.78}
.mt-form .wide{grid-column:1/-1}
.mt-form textarea,.mt-record-form textarea{resize:vertical}
.mt-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:39px;border:1px solid transparent;border-radius:10px;padding:9px 14px;background:linear-gradient(120deg,#176b60,#238b78);color:#fff;font:inherit;font-size:11px;font-weight:850;text-decoration:none;cursor:pointer;box-shadow:0 5px 12px rgba(24,112,96,.14);transition:transform .18s ease,box-shadow .18s ease}
.mt-btn:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(24,112,96,.2)}.mt-btn:focus-visible{outline:3px solid rgba(42,166,143,.35);outline-offset:2px}
.mt-btn svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2}
.mt-btn-quiet{background:var(--surface);color:var(--text);border-color:var(--line);box-shadow:none}
.mt-form-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.mt-form-hint{color:var(--muted);font-size:10px;line-height:1.8}
.mt-section{display:grid;gap:0}
.mt-section .mt-card-head{align-items:center}
.mt-section-count{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;min-width:29px;height:27px;padding:0 8px;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--muted);font-size:11px;font-weight:850}
.mt-tablewrap{overflow:auto;width:100%}.mt-table{width:100%;border-collapse:separate;border-spacing:0;min-width:820px}
.mt-table th,.mt-table td{padding:13px 15px;border-bottom:1px solid var(--line);text-align:right;vertical-align:middle;font-size:11px}
.mt-table th{position:sticky;top:0;background:var(--surface);color:var(--muted);font-size:10px;font-weight:850;white-space:nowrap}
.mt-table tbody tr{transition:background .15s ease}.mt-table tbody tr:hover{background:color-mix(in srgb,var(--brand-soft,rgba(30,135,116,.1)) 48%,transparent)}.mt-table tbody tr:last-child td{border-bottom:0}
.mt-primary-text{font-weight:850;color:var(--text);line-height:1.8}.mt-muted{display:block;margin-top:3px;color:var(--muted);font-size:10px;line-height:1.8;overflow-wrap:anywhere}
.mt-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:8px;background:rgba(32,132,113,.1);color:#22836f;font-size:10px;font-weight:850;white-space:nowrap}
.mt-pill:before{content:"";width:5px;height:5px;border-radius:50%;background:currentColor}
.mt-pill-overdue{background:rgba(220,70,60,.1);color:#d34e45}.mt-pill-soon{background:rgba(204,139,36,.12);color:#a16b0c}.mt-pill-neutral{background:var(--surface);color:var(--muted)}
.mt-inline{display:flex;align-items:center;gap:6px;min-width:290px}.mt-inline input{flex:1;width:100px;padding:8px 9px;font-size:11px}.mt-inline .jdate{flex:1.2}.mt-record-form{min-width:300px}.mt-record-form textarea{min-height:36px;margin-top:7px;padding:7px 9px;font-size:10px}
.mt-empty{display:grid;justify-items:center;gap:8px;padding:30px 18px;text-align:center;color:var(--muted);font-size:11px;line-height:2}
.mt-empty-icon{display:grid;place-items:center;width:43px;height:43px;border-radius:14px;background:var(--surface);color:var(--brand-d,#176c5d)}
.mt-empty-icon svg{width:22px;height:22px;stroke:currentColor;fill:none;stroke-width:1.6}
.mt-empty strong{color:var(--text);font-size:12px}
.mt-note{display:flex;align-items:flex-start;gap:9px;margin-top:14px;padding:12px 14px;border:1px solid color-mix(in srgb,#22836f 22%,var(--line));border-radius:12px;background:color-mix(in srgb,#22836f 6%,var(--card));color:var(--muted);font-size:10px;line-height:1.9}
.mt-note svg{flex:0 0 16px;width:16px;height:16px;margin-top:2px;stroke:#22836f;fill:none;stroke-width:1.8}
.mt-history-total{display:flex;align-items:center;gap:6px;color:var(--muted);font-size:10px;white-space:nowrap}.mt-history-total strong{color:var(--text);font-size:12px}
.mt-feedback{padding:10px 13px;border:1px solid var(--line);border-radius:11px;background:var(--surface);color:var(--text);font-size:11px;line-height:1.9}
.mt-feedback-error{border-color:rgba(211,78,69,.3);background:rgba(211,78,69,.06);color:#d34e45}
@media(prefers-reduced-motion:no-preference){.mt-stat,.mt-card{transition:box-shadow .2s ease,border-color .2s ease}.mt-stat:hover{box-shadow:0 10px 25px rgba(17,44,55,.07);border-color:color-mix(in srgb,var(--brand,#238c7d) 28%,var(--line))}}
@media(max-width:1050px){.mt-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.mt-layout{grid-template-columns:1fr}}
@media(max-width:650px){.maintenance-page{gap:14px}.mt-hero{padding:21px 18px;border-radius:19px;gap:12px}.mt-hero-art{flex-basis:54px;width:54px;height:54px;border-radius:16px}.mt-hero-art svg{width:28px;height:28px}.mt-hero h2{font-size:22px}.mt-hero p{font-size:11px}.mt-stats{gap:8px}.mt-stat{padding:12px;border-radius:14px}.mt-stat-head{align-items:flex-start;font-size:10px}.mt-stat-icon{width:29px;height:29px;flex-basis:29px;border-radius:9px}.mt-stat-icon svg{width:16px;height:16px}.mt-stat strong{font-size:21px}.mt-stat small{font-size:9px}.mt-card{border-radius:15px}.mt-card-head{padding:15px 14px 12px}.mt-card-body{padding:14px}.mt-card-icon{width:33px;height:33px;flex-basis:33px;border-radius:10px}.mt-form{grid-template-columns:1fr;gap:11px}.mt-form .wide{grid-column:auto}.mt-form-footer{align-items:stretch;flex-direction:column}.mt-form-footer .mt-btn{width:100%}.mt-table th,.mt-table td{padding:11px 12px}.mt-history-total{white-space:normal}}
</style>
<div class="maintenance-page">
    <header class="mt-hero">
        <div class="mt-hero-copy">
            <span class="mt-kicker">عملیات ساختمان · نگهداری هوشمند</span>
            <h2>مرکز نگهداری و تعمیرات</h2>
            <p>از وضعیت تجهیزات تا برنامه سرویس‌های دوره‌ای و هزینه‌های انجام‌شده؛ همه‌چیز را یکجا مدیریت کن، سررسیدهای عقب‌افتاده را زودتر ببین و سوابق تعمیرات را مرتب نگه دار.</p>
        </div>
        <div class="mt-hero-art" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3 18l3 3 5.3-5.3a5 5 0 0 0 6.4-6.4l-3 3-3-3 3-3Z"/><path d="m5 5 2-2 4 4-2 2z"/></svg>
        </div>
    </header>

    @if(session('status'))
        <div class="mt-feedback" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mt-feedback mt-feedback-error" role="alert">لطفاً موارد مشخص‌شده را بررسی کن. اطلاعات واردشده کامل یا معتبر نیست.</div>
    @endif

    <section class="mt-stats" aria-label="خلاصه وضعیت نگهداری">
        <article class="mt-stat" style="--mt-accent:#228575;--mt-tint:rgba(34,133,117,.11)">
            <div class="mt-stat-head"><span>تجهیزات ثبت‌شده</span><span class="mt-stat-icon"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 8h8M8 12h8M8 16h4"/></svg></span></div>
            <strong>{{ \App\Support\Fa::num($assets->count()) }}</strong>
            <small>دارایی‌های ثبت‌شده ساختمان</small>
        </article>
        <article class="mt-stat" style="--mt-accent:#2779bd;--mt-tint:rgba(39,121,189,.11)">
            <div class="mt-stat-head"><span>برنامه‌های فعال</span><span class="mt-stat-icon"><svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M7.5 3v4M16.5 3v4M4 9.5h16M8 13h3M8 16.5h6"/></svg></span></div>
            <strong>{{ \App\Support\Fa::num($plans->count()) }}</strong>
            <small>سرویس‌های برنامه‌ریزی‌شده</small>
        </article>
        <article class="mt-stat" style="--mt-accent:{{ $overduePlans->isNotEmpty() ? '#d34e45' : '#b07a1a' }};--mt-tint:{{ $overduePlans->isNotEmpty() ? 'rgba(211,78,69,.11)' : 'rgba(176,122,26,.11)' }}">
            <div class="mt-stat-head"><span>سررسیدهای گذشته</span><span class="mt-stat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span></div>
            <strong>{{ \App\Support\Fa::num($overduePlans->count()) }}</strong>
            <small>{{ $overduePlans->isNotEmpty() ? 'نیازمند پیگیری فوری' : 'مورد عقب‌افتاده‌ای ثبت نشده' }}</small>
        </article>
        <article class="mt-stat" style="--mt-accent:#8a65b7;--mt-tint:rgba(138,101,183,.11)">
            <div class="mt-stat-head"><span>هزینه سوابق اخیر</span><span class="mt-stat-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 10h18M7 15h4"/></svg></span></div>
            <strong style="font-size:clamp(17px,1.8vw,23px)">{{ \App\Support\Fa::digits(number_format($maintenanceCost)) }}</strong>
            <small>ریال · مجموع {{ \App\Support\Fa::num($records->count()) }} سرویس آخر</small>
        </article>
    </section>

    <div class="mt-layout">
        <section class="mt-card">
            <div class="mt-card-head">
                <div class="mt-card-heading">
                    <span class="mt-card-icon"><svg viewBox="0 0 24 24"><path d="M12 3 4 7v5c0 5 3.5 7.5 8 9 4.5-1.5 8-4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                    <div><h3>ثبت تجهیز جدید</h3><p class="mt-card-subtitle">مشخصات دارایی‌ها و تجهیزات مشترک ساختمان</p></div>
                </div>
            </div>
            <div class="mt-card-body">
                <form method="POST" action="{{ route('maintenance.assets.store') }}">
                    @csrf
                    <div class="mt-form">
                        <div class="mt-field"><label for="asset-name">نام تجهیز <span aria-hidden="true">*</span></label><input id="asset-name" name="name" required maxlength="150" value="{{ old('name') }}" placeholder="مثلاً آسانسور بلوک A"></div>
                        <div class="mt-field"><label for="asset-category">دسته‌بندی</label><input id="asset-category" name="category" maxlength="80" value="{{ old('category') }}" placeholder="آسانسور، پمپ، موتورخانه..."></div>
                        <div class="mt-field"><label for="asset-location">محل نصب</label><input id="asset-location" name="location" maxlength="150" value="{{ old('location') }}" placeholder="مثلاً موتورخانه طبقه منفی یک"></div>
                        <div class="mt-field"><label for="asset-manufacturer">سازنده</label><input id="asset-manufacturer" name="manufacturer" maxlength="120" value="{{ old('manufacturer') }}" placeholder="نام شرکت سازنده"></div>
                        <div class="mt-field"><label for="asset-model">مدل</label><input id="asset-model" name="model" maxlength="120" value="{{ old('model') }}" placeholder="مدل تجهیز"></div>
                        <div class="mt-field"><label for="asset-serial">شماره سریال</label><input id="asset-serial" name="serial_number" maxlength="120" value="{{ old('serial_number') }}" placeholder="شماره شناسایی"></div>
                        <div class="mt-field"><label for="asset-installed">تاریخ نصب (شمسی)</label><input id="asset-installed" name="installed_at" class="jdate" autocomplete="off" value="{{ old('installed_at') }}" placeholder="۱۴۰۵/۰۷/۲۹"></div>
                        <div class="mt-field"><label for="asset-warranty">پایان ضمانت (شمسی)</label><input id="asset-warranty" name="warranty_until" class="jdate" autocomplete="off" value="{{ old('warranty_until') }}" placeholder="۱۴۰۶/۰۷/۲۹"></div>
                        <div class="mt-field wide"><label for="asset-notes">توضیحات و نکات مهم</label><textarea id="asset-notes" name="notes" rows="2" maxlength="5000" placeholder="نکات فنی، شرایط نگهداری یا اطلاعات تکمیلی...">{{ old('notes') }}</textarea></div>
                        <div class="wide mt-form-footer"><span class="mt-form-hint">تاریخ‌ها با تقویم شمسی ثبت می‌شوند.</span><button class="mt-btn" type="submit"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> ثبت تجهیز</button></div>
                    </div>
                </form>
            </div>
        </section>

        <section class="mt-card">
            <div class="mt-card-head">
                <div class="mt-card-heading">
                    <span class="mt-card-icon"><svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M7.5 3v4M16.5 3v4M4 9.5h16M8 13h3M8 16.5h7"/></svg></span>
                    <div><h3>برنامه‌ریزی سرویس دوره‌ای</h3><p class="mt-card-subtitle">زمان‌بندی، هزینه تخمینی و تعیین مسئول اجرا</p></div>
                </div>
            </div>
            <div class="mt-card-body">
                @if($assets->isEmpty())
                    <div class="mt-empty"><span class="mt-empty-icon"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 8h8M8 12h8M8 16h4"/></svg></span><strong>هنوز تجهیزی ثبت نشده است</strong><span>ابتدا یک تجهیز از فرم سمت راست ثبت کن؛ سپس می‌توانی برای آن برنامه سرویس بسازی.</span></div>
                @else
                    <form method="POST" action="{{ route('maintenance.plans.store') }}">
                        @csrf
                        <div class="mt-form">
                            <div class="mt-field"><label for="plan-asset">انتخاب تجهیز <span aria-hidden="true">*</span></label><select id="plan-asset" name="asset_id" required><option value="">انتخاب از فهرست تجهیزات</option>@foreach($assets as $asset)<option value="{{ $asset->id }}" @selected((string)old('asset_id')===(string)$asset->id)>{{ $asset->name }}</option>@endforeach</select></div>
                            <div class="mt-field"><label for="plan-title">عنوان سرویس <span aria-hidden="true">*</span></label><input id="plan-title" name="title" required maxlength="180" value="{{ old('title') }}" placeholder="بازدید فنی و ایمنی"></div>
                            <div class="mt-field"><label for="plan-frequency">تناوب تکرار</label><select id="plan-frequency" name="frequency">@foreach($frequencyLabels as $key=>$label)<option value="{{ $key }}" @selected(old('frequency',$key==='monthly'?'monthly':'')===$key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="mt-field" id="plan-interval-wrap" hidden><label for="plan-interval">فاصله بین سرویس‌ها (روز)</label><input id="plan-interval" type="number" name="interval_days" min="1" max="3650" value="{{ old('interval_days') }}" placeholder="مثلاً ۴۵"></div>
                            <div class="mt-field"><label for="plan-next">سررسید بعدی (شمسی) <span aria-hidden="true">*</span></label><input id="plan-next" name="next_due_at" class="jdate" autocomplete="off" required value="{{ old('next_due_at',$jalaliToday) }}"></div>
                            <div class="mt-field"><label for="plan-cost">هزینه تخمینی (ریال)</label><input id="plan-cost" type="number" name="estimated_cost" min="0" max="99999999999" value="{{ old('estimated_cost') }}" placeholder="مثلاً ۱۵۰۰۰۰۰۰"></div>
                            <div class="mt-field wide"><label for="plan-assignee">مسئول اجرا (اختیاری)</label><select id="plan-assignee" name="assigned_to"><option value="">هنوز تعیین نشده</option>@foreach($staff as $person)<option value="{{ $person->id }}" @selected((string)old('assigned_to')===(string)$person->id)>{{ $person->display_name ?: $person->mobile }}</option>@endforeach</select></div>
                            <div class="mt-field wide"><label for="plan-description">شرح کار و چک‌لیست</label><textarea id="plan-description" name="description" rows="2" maxlength="5000" placeholder="مواردی که در هر نوبت باید بررسی یا سرویس شوند...">{{ old('description') }}</textarea></div>
                            <div class="wide mt-form-footer"><span class="mt-form-hint">پس از ثبت انجام سرویس، سررسید بعدی خودکار به‌روزرسانی می‌شود.</span><button class="mt-btn" type="submit"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> ثبت برنامه سرویس</button></div>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>

    <section class="mt-card mt-section">
        <div class="mt-card-head">
            <div class="mt-card-heading">
                <span class="mt-card-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <div><h3>برنامه‌های فعال و سررسیدها</h3><p class="mt-card-subtitle">اولویت با سرویس‌های عقب‌افتاده و نزدیک به سررسید است.</p></div>
            </div>
            <span class="mt-section-count">{{ \App\Support\Fa::num($plans->count()) }} برنامه</span>
        </div>
        @if($plans->isEmpty())
            <div class="mt-empty"><span class="mt-empty-icon"><svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M7.5 3v4M16.5 3v4M4 9.5h16"/></svg></span><strong>برنامه‌ای برای سرویس ثبت نشده</strong><span>از فرم برنامه‌ریزی بالا، اولین سرویس دوره‌ای ساختمان را تعریف کن.</span></div>
        @else
            <div class="mt-tablewrap"><table class="mt-table"><thead><tr><th>تجهیز</th><th>شرح سرویس</th><th>تناوب</th><th>سررسید</th><th>مسئول</th><th>ثبت انجام سرویس</th></tr></thead><tbody>
                @foreach($plans->sortBy(fn ($plan) => $plan->next_due_at?->timestamp ?? PHP_INT_MAX) as $plan)
                    @php
                        $isOverdue = $plan->next_due_at && $plan->next_due_at->lt(today());
                        $isDueSoon = !$isOverdue && $plan->next_due_at && $plan->next_due_at->lte(today()->copy()->addDays(7));
                    @endphp
                    <tr>
                        <td><span class="mt-primary-text">{{ $plan->asset?->name ?: 'تجهیز حذف‌شده' }}</span><span class="mt-muted">{{ $plan->asset?->location ?: 'محل نصب ثبت نشده' }}</span></td>
                        <td><span class="mt-primary-text">{{ $plan->title }}</span>@if($plan->description)<span class="mt-muted">{{ \Illuminate\Support\Str::limit($plan->description,100) }}</span>@endif</td>
                        <td><span class="mt-pill mt-pill-neutral">{{ $frequencyLabels[$plan->frequency] ?? $plan->frequency }}</span></td>
                        <td>
                            <span class="mt-pill {{ $isOverdue ? 'mt-pill-overdue' : ($isDueSoon ? 'mt-pill-soon' : '') }}">{{ $F::date($plan->next_due_at,'j F Y') }}</span>
                            @if($isOverdue)<span class="mt-muted">عقب‌افتاده · نیازمند پیگیری</span>@elseif($isDueSoon)<span class="mt-muted">سررسید در ۷ روز آینده</span>@endif
                        </td>
                        <td>{{ $plan->assignee?->display_name ?: $plan->assignee?->mobile ?: 'تعیین نشده' }}</td>
                        <td>
                            <form class="mt-record-form" method="POST" action="{{ route('maintenance.plans.complete',$plan) }}">
                                @csrf
                                <div class="mt-inline"><input name="performed_at" class="jdate" autocomplete="off" required value="{{ $jalaliToday }}" aria-label="تاریخ انجام سرویس به شمسی"><input name="cost" type="number" min="0" max="99999999999" placeholder="هزینه نهایی (ریال)" aria-label="هزینه نهایی سرویس"><button class="mt-btn" type="submit">ثبت انجام</button></div>
                                <textarea name="notes" rows="1" maxlength="5000" placeholder="یادداشت انجام کار (اختیاری)" aria-label="یادداشت انجام کار"></textarea>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody></table></div>
        @endif
        <div class="mt-note"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7h.01"/></svg><span>با ثبت انجام سرویس، تاریخ و هزینه در تاریخچه نگهداری ذخیره می‌شود و سررسید بعدی با توجه به تناوب انتخاب‌شده محاسبه خواهد شد.</span></div>
    </section>

    <section class="mt-card mt-section">
        <div class="mt-card-head">
            <div class="mt-card-heading">
                <span class="mt-card-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span>
                <div><h3>تاریخچه سرویس‌ها و تعمیرات</h3><p class="mt-card-subtitle">آخرین عملیات ثبت‌شده برای پیگیری هزینه‌ها و سوابق فنی</p></div>
            </div>
            <div class="mt-history-total"><span>مجموع هزینه ثبت‌شده</span><strong>{{ \App\Support\Fa::digits(number_format($maintenanceCost)) }} ریال</strong></div>
        </div>
        @if($records->isEmpty())
            <div class="mt-empty"><span class="mt-empty-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8"/></svg></span><strong>هنوز سابقه‌ای ثبت نشده</strong><span>بعد از ثبت اولین سرویس، تاریخچه عملیات در این بخش نمایش داده می‌شود.</span></div>
        @else
            <div class="mt-tablewrap"><table class="mt-table"><thead><tr><th>تاریخ انجام</th><th>تجهیز</th><th>سرویس / عملیات</th><th>هزینه نهایی (ریال)</th><th>ثبت‌کننده</th><th>یادداشت</th></tr></thead><tbody>
                @foreach($records as $record)
                    <tr>
                        <td><span class="mt-primary-text">{{ $F::date($record->performed_at,'j F Y') }}</span></td>
                        <td><span class="mt-primary-text">{{ $record->asset?->name ?: 'تجهیز حذف‌شده' }}</span></td>
                        <td><span class="mt-primary-text">{{ $record->title }}</span></td>
                        <td><span class="mt-primary-text">{{ $F::digits(number_format($record->cost)) }}</span></td>
                        <td>{{ $record->performer?->display_name ?: '—' }}</td>
                        <td>{{ $record->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody></table></div>
        @endif
    </section>
</div>
<script>
(() => {
    const frequency = document.getElementById('plan-frequency');
    const wrapper = document.getElementById('plan-interval-wrap');
    const interval = document.getElementById('plan-interval');
    if (!frequency || !wrapper || !interval) return;
    const syncInterval = () => {
        const custom = frequency.value === 'custom';
        wrapper.hidden = !custom;
        interval.required = custom;
        interval.disabled = !custom;
    };
    frequency.addEventListener('change', syncInterval);
    syncInterval();
})();
</script>
@endsection
