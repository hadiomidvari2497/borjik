@php($F = \App\Support\Fa::class)
@extends('layouts.app')
@section('title', 'دفترچه تلفن')
@section('content')
<style>
.phonebook-page{display:grid;gap:18px;color:var(--text,var(--ink,#203342))}
.phonebook-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:clamp(22px,4vw,34px);margin-bottom:20px;border-radius:24px;background:linear-gradient(125deg,#123d42,#176b63 55%,#398d83);color:#fff;box-shadow:0 18px 40px #175c551f}
.phonebook-hero h2{margin:0 0 8px;font-size:clamp(23px,3vw,31px);line-height:1.6;font-weight:900;color:#fff}
.phonebook-hero p{margin:0;max-width:660px;color:#d9f0e9;font-size:12px;line-height:2}
.phonebook-hero-mark{display:grid;place-items:center;flex:0 0 70px;width:70px;height:70px;border:1px solid #ffffff45;border-radius:22px;background:#ffffff16;font-size:28px}
.phonebook-stats{display:flex;gap:8px;flex-wrap:wrap;margin-top:17px}
.phonebook-stat{min-width:95px;padding:9px 13px;border:1px solid #ffffff35;border-radius:12px;background:#ffffff12}
.phonebook-stat strong{display:block;font-size:20px;line-height:1.5;color:#fff}.phonebook-stat span{font-size:10px;color:#d9f0e9}
.phonebook-panel{overflow:hidden;border:1px solid var(--line,#e2e9ed);border-radius:19px;background:var(--card,#fff)}
.phonebook-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid var(--line,#e2e9ed)}
.phonebook-panel-head h3{margin:0 0 4px;font-size:15px;font-weight:850}.phonebook-panel-head p{margin:0;color:var(--muted,#718096);font-size:11px;line-height:1.8}
.phonebook-body{padding:18px 20px}
.phonebook-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.phonebook-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:1px solid var(--line,#e2e9ed);border-radius:12px;background:var(--card,#fff);color:var(--muted,#718096);text-decoration:none;font-size:12px;font-weight:800}
.phonebook-tab.active{border-color:#b6ddd0;background:#eaf7f2;color:#176b63}
.phonebook-search{display:flex;gap:9px;margin-bottom:16px}
.phonebook-search input{flex:1;min-width:0;border:1px solid var(--line,#dce5eb);border-radius:12px;padding:10px 12px;background:var(--card,#fff);color:inherit;font:inherit;font-size:12px}
.phonebook-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:12px}
.phonebook-contact{min-width:0;padding:15px;border:1px solid var(--line,#e2e9ed);border-radius:16px;background:var(--card,#fff)}
.phonebook-contact-top{display:flex;align-items:flex-start;gap:11px}
.phonebook-avatar{display:grid;place-items:center;flex:0 0 43px;width:43px;height:43px;border-radius:14px;background:#e8f6f1;color:#176b63;font-size:15px;font-weight:900}
.phonebook-contact-main{flex:1;min-width:0}.phonebook-contact h4{margin:0 0 5px;font-size:13px;line-height:1.8;overflow-wrap:anywhere}
.phonebook-chip{display:inline-flex;padding:4px 8px;border-radius:8px;background:var(--surface-2,#f1f5f6);color:var(--muted,#617381);font-size:10px}
.phonebook-phone{display:block;margin-top:12px;color:#176b63;font-size:14px;font-weight:850;direction:ltr;text-align:right;text-decoration:none;overflow-wrap:anywhere}
.phonebook-roles{display:flex;gap:5px;flex-wrap:wrap;margin-top:9px}
.phonebook-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:13px;padding-top:12px;border-top:1px solid var(--line,#edf1f4)}
.phonebook-actions form{margin:0}
.phonebook-edit{margin-top:13px;padding-top:13px;border-top:1px dashed var(--line,#dce5eb)}
.phonebook-form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.phonebook-form-grid .fld{min-width:0}.phonebook-form-grid .fld label{display:block;margin-bottom:6px;font-size:11px;font-weight:800;color:var(--muted,#526575)}
.phonebook-form-grid input,.phonebook-form-grid select{width:100%;min-width:0;border:1px solid var(--line,#dce5eb);border-radius:11px;padding:10px 11px;background:var(--card,#fff);color:inherit;font:inherit;font-size:12px}
.phonebook-empty{padding:28px 16px;text-align:center;color:var(--muted,#718096);font-size:12px;line-height:2;border:1px dashed var(--line,#cfddd9);border-radius:15px}
.phonebook-empty strong{display:block;color:inherit;font-size:14px;margin-bottom:4px}
.phonebook-form-errors{padding:11px 13px;margin-bottom:13px;border-radius:12px;background:#fff4f2;border:1px solid #f0d4cf;color:#a3433c;font-size:11px;line-height:1.9}
@media(max-width:680px){.phonebook-hero{align-items:flex-start}.phonebook-hero-mark{flex-basis:52px;width:52px;height:52px;border-radius:16px}.phonebook-form-grid{grid-template-columns:1fr}.phonebook-panel-head,.phonebook-body{padding:15px}.phonebook-search{flex-direction:column}.phonebook-search .btn{width:100%}}

/* Phonebook refresh — richer hierarchy, tactile cards, and polished responsive controls. */
.phonebook-page{gap:20px}
.phonebook-hero{position:relative;isolation:isolate;overflow:hidden;min-height:220px;align-items:center;padding:clamp(25px,4.2vw,42px);margin:0;border:1px solid rgba(255,255,255,.16);background:radial-gradient(ellipse at 12% 5%,rgba(153,246,219,.24),transparent 35%),radial-gradient(ellipse at 95% 110%,rgba(112,224,200,.2),transparent 40%),linear-gradient(118deg,#102b3b 0%,#155c5c 52%,#278778 100%);box-shadow:0 20px 46px rgba(16,65,65,.16),inset 0 1px 0 rgba(255,255,255,.12)}
.phonebook-hero:before{content:"";position:absolute;z-index:-1;width:260px;height:260px;inset-inline-end:7%;top:-195px;border:1px solid rgba(255,255,255,.2);border-radius:50%;box-shadow:0 0 0 22px rgba(255,255,255,.035),0 0 0 48px rgba(255,255,255,.025),0 0 0 75px rgba(255,255,255,.018);pointer-events:none}
.phonebook-hero:after{content:"";position:absolute;z-index:-1;width:5px;inset-block:27px;inset-inline-start:0;border-radius:8px;background:linear-gradient(180deg,#baf5df,#4bc6ac);box-shadow:0 0 24px rgba(129,242,207,.45);pointer-events:none}
.phonebook-hero h2{font-size:clamp(25px,3.2vw,35px);letter-spacing:-.7px;line-height:1.45;text-wrap:balance}
.phonebook-hero p{max-width:590px;font-size:12px;line-height:2.15}
.phonebook-hero-mark{position:relative;flex-basis:82px;width:82px;height:82px;border:1px solid rgba(255,255,255,.27);border-radius:25px;background:linear-gradient(145deg,rgba(255,255,255,.19),rgba(255,255,255,.055));box-shadow:inset 0 1px 0 rgba(255,255,255,.17),0 14px 30px rgba(4,34,39,.14);font-size:34px;backdrop-filter:blur(10px)}
.phonebook-hero-mark:after{content:"";position:absolute;inset:7px;border:1px solid rgba(255,255,255,.13);border-radius:18px;pointer-events:none}
.phonebook-stats{gap:9px;margin-top:22px}
.phonebook-stat{min-width:112px;padding:10px 15px;border-radius:14px;background:rgba(255,255,255,.1);box-shadow:inset 0 1px 0 rgba(255,255,255,.09);backdrop-filter:blur(8px)}
.phonebook-stat strong{font-size:22px;letter-spacing:-.4px}
.phonebook-stat span{font-size:10px;letter-spacing:.1px}
.phonebook-tabs{gap:9px;margin:0;padding:6px;width:max-content;max-width:100%;border:1px solid var(--line,#e2e9ed);border-radius:15px;background:var(--surface-2,#f5f8f8)}
.phonebook-tab{padding:11px 16px;border-color:transparent;border-radius:11px;transition:background .18s ease,border-color .18s ease,transform .18s ease}
.phonebook-tab:hover{transform:translateY(-1px);border-color:var(--line,#dce5eb)}
.phonebook-tab.active{border-color:#c4e7db;background:var(--card,#fff);color:#176b63;box-shadow:0 3px 10px rgba(23,107,99,.08)}
.phonebook-panel{border-radius:22px;box-shadow:0 8px 28px rgba(25,55,67,.045)}
.phonebook-panel-head{padding:20px 22px;background:linear-gradient(180deg,rgba(120,170,165,.055),transparent)}
.phonebook-panel-head h3{font-size:15px;letter-spacing:-.2px}
.phonebook-panel-head p{max-width:650px}
.phonebook-body{padding:20px 22px}
.phonebook-search{gap:10px;margin-bottom:19px}
.phonebook-search input{min-height:44px;padding:11px 14px;border-radius:13px;transition:border-color .18s ease,box-shadow .18s ease}
.phonebook-search input:focus,.phonebook-form-grid input:focus,.phonebook-form-grid select:focus{outline:none;border-color:#55a995;box-shadow:0 0 0 4px rgba(45,153,128,.12)}
.phonebook-search .btn{min-width:96px}
.phonebook-grid{grid-template-columns:repeat(auto-fit,minmax(min(100%,265px),1fr));gap:14px}
.phonebook-contact{position:relative;overflow:hidden;padding:17px;border-radius:18px;box-shadow:0 2px 8px rgba(24,54,65,.025);transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
.phonebook-contact:before{content:"";position:absolute;inset-inline:0;top:0;height:3px;background:linear-gradient(90deg,#1b776c,#8ddfc5);opacity:.55}
.phonebook-contact:hover{transform:translateY(-3px);border-color:#b7d9d0;box-shadow:0 12px 26px rgba(24,83,76,.09)}
.phonebook-avatar{flex-basis:46px;width:46px;height:46px;border:1px solid #d4eee5;border-radius:15px;background:linear-gradient(145deg,#eaf9f2,#d9f0e8);color:#176b63;font-size:16px;box-shadow:inset 0 1px 0 rgba(255,255,255,.85)}
.phonebook-contact h4{font-size:13px;letter-spacing:-.15px}
.phonebook-chip{border:1px solid var(--line,#e2e9ed);background:var(--surface-2,#f5f7f8);font-weight:700}
.phonebook-phone{display:flex;align-items:center;gap:8px;margin-top:14px;padding:11px 12px;border:1px solid #dcefe7;border-radius:12px;background:linear-gradient(100deg,#f0faf5,#f8fcfa);color:#176b63;font-size:14px;letter-spacing:.2px;transition:background .18s ease,border-color .18s ease}
.phonebook-phone:before{content:"☎";display:grid;place-items:center;width:27px;height:27px;border-radius:9px;background:#d9f2e7;color:#176b63;font-size:13px}
.phonebook-phone:hover{border-color:#9bd5c2;background:#e8f8f0;text-decoration:none}
.phonebook-roles{gap:6px}
.phonebook-actions{align-items:center;gap:8px;margin-top:14px;padding-top:13px}
.phonebook-actions .btn,.phonebook-search .btn,.phonebook-body>form .btn{border-radius:10px}
.phonebook-actions details{position:relative}
.phonebook-actions summary{cursor:pointer;list-style:none}
.phonebook-actions summary::-webkit-details-marker{display:none}
.phonebook-edit{padding:14px;border:1px solid var(--line,#e2e9ed);border-radius:13px;background:var(--surface-2,#f7f9f9)}
.phonebook-form-grid{gap:14px}
.phonebook-form-grid input,.phonebook-form-grid select{min-height:42px;border-radius:11px;transition:border-color .18s ease,box-shadow .18s ease}
.phonebook-empty{padding:36px 18px;border-color:#c9e0d8;background:radial-gradient(ellipse at top,rgba(220,245,235,.42),transparent 65%);border-radius:17px}
.phonebook-empty:before{content:"☎";display:grid;place-items:center;width:48px;height:48px;margin:0 auto 12px;border:1px solid #d1e9df;border-radius:16px;background:#eaf8f1;color:#287b69;font-size:21px}
.phonebook-empty strong{font-size:14px}
.phonebook-form-errors{border-radius:13px}
.phonebook-page .phonebook-panel .phonebook-panel{border-radius:17px;background:var(--surface-2,#fafcfc);box-shadow:none}
.phonebook-page .phonebook-panel .phonebook-panel .phonebook-panel-head{background:transparent}
@media(max-width:680px){
 .phonebook-page{gap:15px}
 .phonebook-hero{min-height:0;gap:13px;padding:23px 19px}
 .phonebook-hero h2{font-size:22px;letter-spacing:-.35px}
 .phonebook-hero p{font-size:11px}
 .phonebook-hero-mark{flex-basis:55px;width:55px;height:55px;border-radius:17px;font-size:25px}
 .phonebook-hero-mark:after{inset:5px;border-radius:12px}
 .phonebook-stat{min-width:98px;padding:8px 11px}
 .phonebook-stat strong{font-size:19px}
 .phonebook-tabs{width:100%;display:grid;grid-template-columns:1fr 1fr}
 .phonebook-tab{justify-content:center;padding:10px 8px}
 .phonebook-panel{border-radius:18px}
 .phonebook-panel-head,.phonebook-body{padding:15px}
 .phonebook-grid{grid-template-columns:1fr;gap:11px}
 .phonebook-contact{padding:15px}
 .phonebook-form-grid{grid-template-columns:1fr}
 .phonebook-search{flex-direction:row;flex-wrap:wrap}
 .phonebook-search input{flex-basis:100%}
 .phonebook-search .btn{flex:1}
}
@media(prefers-reduced-motion:reduce){.phonebook-contact,.phonebook-tab,.phonebook-phone,.phonebook-search input{transition:none}}


/* Theme audit: preserve the shared dark palette on phone-book controls and contact details. */
:root[data-theme="dark"] .phonebook-tabs,
:root[data-theme="dark"] .phonebook-edit,
:root[data-theme="dark"] .phonebook-page .phonebook-panel .phonebook-panel{background:var(--track,#1b2f2d);border-color:var(--line)}
:root[data-theme="dark"] .phonebook-tab.active{background:var(--card);border-color:var(--line);color:var(--brand)}
:root[data-theme="dark"] .phonebook-avatar{background:var(--brand-soft);border-color:var(--line);color:var(--brand)}
:root[data-theme="dark"] .phonebook-phone{background:var(--panel);border-color:var(--line);color:var(--brand)}
:root[data-theme="dark"] .phonebook-phone:before,
:root[data-theme="dark"] .phonebook-empty:before{background:var(--brand-soft);border-color:var(--line);color:var(--brand)}
:root[data-theme="dark"] .phonebook-empty{background:var(--panel);border-color:var(--line)}
:root[data-theme="dark"] .phonebook-form-errors{background:rgba(224,69,58,.14);border-color:rgba(255,130,120,.35);color:#ffaaa3}
:root[data-theme="dark"] .phonebook-contact:hover{border-color:var(--brand)}
:root[data-theme="dark"] .phonebook-search input:focus,
:root[data-theme="dark"] .phonebook-form-grid input:focus,
:root[data-theme="dark"] .phonebook-form-grid select:focus{border-color:var(--brand);box-shadow:0 0 0 4px var(--brand-soft)}
@media(prefers-color-scheme:dark){
 :root:not([data-theme="light"]) .phonebook-tabs,
 :root:not([data-theme="light"]) .phonebook-edit,
 :root:not([data-theme="light"]) .phonebook-page .phonebook-panel .phonebook-panel{background:var(--track,#1b2f2d);border-color:var(--line)}
 :root:not([data-theme="light"]) .phonebook-tab.active{background:var(--card);border-color:var(--line);color:var(--brand)}
 :root:not([data-theme="light"]) .phonebook-avatar{background:var(--brand-soft);border-color:var(--line);color:var(--brand)}
 :root:not([data-theme="light"]) .phonebook-phone{background:var(--panel);border-color:var(--line);color:var(--brand)}
 :root:not([data-theme="light"]) .phonebook-phone:before,
 :root:not([data-theme="light"]) .phonebook-empty:before{background:var(--brand-soft);border-color:var(--line);color:var(--brand)}
 :root:not([data-theme="light"]) .phonebook-empty{background:var(--panel);border-color:var(--line)}
 :root:not([data-theme="light"]) .phonebook-form-errors{background:rgba(224,69,58,.14);border-color:rgba(255,130,120,.35);color:#ffaaa3}
 :root:not([data-theme="light"]) .phonebook-contact:hover{border-color:var(--brand)}
 :root:not([data-theme="light"]) .phonebook-search input:focus,
 :root:not([data-theme="light"]) .phonebook-form-grid input:focus,
 :root:not([data-theme="light"]) .phonebook-form-grid select:focus{border-color:var(--brand);box-shadow:0 0 0 4px var(--brand-soft)}
}
</style>
<div class="phonebook-page">
  <section class="phonebook-hero">
    <div>
      <h2>ارتباطات مهم، همیشه در دسترس</h2>
      <p>شماره‌های ضروری ساختمان را در یک دفترچهٔ مشترک نگه دارید و مخاطبان دلخواهتان را در دفترچهٔ خصوصی خودتان مدیریت کنید.</p>
      <div class="phonebook-stats">
        @if($canBuilding)<div class="phonebook-stat"><strong>{{ $F::num($buildingCount) }}</strong><span>تماس‌های ساختمان</span></div>@endif
        @if($canPersonal)<div class="phonebook-stat"><strong>{{ $F::num($personalEntries->total()) }}</strong><span>مخاطبان شخصی</span></div>@endif
      </div>
    </div>
    <div class="phonebook-hero-mark" aria-hidden="true">☎</div>
  </section>

  @if($canBuilding && $canPersonal)
    <nav class="phonebook-tabs" aria-label="نوع دفترچه">
      <a class="phonebook-tab {{ $activeTab === 'building' ? 'active' : '' }}" href="{{ route('phonebook.index', ['tab' => 'building']) }}">دفترچهٔ ساختمان</a>
      <a class="phonebook-tab {{ $activeTab === 'personal' ? 'active' : '' }}" href="{{ route('phonebook.index', ['tab' => 'personal']) }}">دفترچهٔ شخصی</a>
    </nav>
  @endif

  <section class="phonebook-panel">
    <div class="phonebook-panel-head">
      <div><h3>{{ $activeTab === 'building' && $canBuilding ? 'دفترچه تلفن ساختمان' : 'دفترچه تلفن شخصی' }}</h3>
      <p>{{ $activeTab === 'building' && $canBuilding ? 'برای مالک و مدیر ساختمان؛ شامل مخاطبان ثبت‌شده و شماره تماس اعضای فعال.' : 'این فهرست خصوصی است و فقط خودت به مخاطبان ثبت‌شده در آن دسترسی داری.' }}</p></div>
      <span class="phonebook-chip">{{ $activeTab === 'building' && $canBuilding ? 'مشترک و مدیریت‌شده' : 'خصوصی' }}</span>
    </div>
    <div class="phonebook-body">
      <form class="phonebook-search" method="GET" action="{{ route('phonebook.index') }}">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <input type="search" name="q" value="{{ $search }}" placeholder="جست‌وجوی نام، شماره یا دسته‌بندی..." aria-label="جست‌وجوی مخاطب">
        <button class="btn" type="submit">جست‌وجو</button>
        @if($search !== '')<a class="btn sec" href="{{ route('phonebook.index', ['tab' => $activeTab]) }}">پاک‌کردن</a>@endif
      </form>

      @if($activeTab === 'building' && $canBuilding)
        @if($buildingEntries->isEmpty() && $buildingPeople->isEmpty())<div class="phonebook-empty"><strong>دفترچهٔ ساختمان هنوز مخاطبی ندارد</strong>شماره‌های ضروری را ثبت کن؛ اعضای فعال ساختمان نیز در این فهرست نمایش داده می‌شوند.</div>@endif
        <div class="phonebook-grid">
          @foreach($buildingEntries as $contact)
            <article class="phonebook-contact">
              <div class="phonebook-contact-top"><div class="phonebook-avatar">{{ mb_substr($contact->name, 0, 1) }}</div><div class="phonebook-contact-main"><h4>{{ $contact->name }}</h4><span class="phonebook-chip">{{ $categories[$contact->category] ?? 'سایر شماره‌ها' }}</span></div></div>
              <a class="phonebook-phone" href="tel:{{ $contact->phone_href }}">{{ $F::digits($contact->phone) }}</a>
              <div class="phonebook-actions">
                <details><summary class="btn sec sm">ویرایش</summary><form class="phonebook-edit" method="POST" action="{{ route('phonebook.update', $contact->id) }}">@csrf @method('PUT')
                  <div class="phonebook-form-grid" style="grid-template-columns:1fr">
                    <div class="fld"><label>نام مخاطب</label><input name="name" value="{{ $contact->name }}" maxlength="150" required></div>
                    <div class="fld"><label>شماره تماس</label><input name="phone" value="{{ $contact->phone }}" maxlength="20" inputmode="tel" required></div>
                    <div class="fld"><label>دسته‌بندی</label><select name="category" required>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected($contact->category === $key)>{{ $label }}</option>@endforeach</select></div>
                  </div><button class="btn sm" style="margin-top:10px" type="submit">ذخیره تغییرات</button>
                </form></details>
                <form method="POST" action="{{ route('phonebook.destroy', $contact->id) }}" data-confirm="این مخاطب از دفترچهٔ ساختمان حذف شود؟" data-confirm-title="حذف مخاطب" data-confirm-icon="warning">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button></form>
              </div>
            </article>
          @endforeach
          @foreach($buildingPeople as $person)
            <article class="phonebook-contact"><div class="phonebook-contact-top"><div class="phonebook-avatar">{{ mb_substr($person['name'] ?: 'ک', 0, 1) }}</div><div class="phonebook-contact-main"><h4>{{ $person['name'] ?: 'بدون نام' }}</h4><span class="phonebook-chip">عضو ساختمان</span></div></div>
              <a class="phonebook-phone" href="tel:{{ $person['phone_href'] }}">{{ $F::digits($person['phone']) }}</a><div class="phonebook-roles">@foreach($person['roles'] as $role)<span class="phonebook-chip">{{ $role }}</span>@endforeach</div>
            </article>
          @endforeach
        </div>
        <section class="phonebook-panel" style="margin-top:18px"><div class="phonebook-panel-head"><div><h3>افزودن شمارهٔ ضروری</h3><p>برای اورژانس، آتش‌نشانی، خدمات، حراست و سایر تماس‌های مهم.</p></div></div><div class="phonebook-body">
          @if($errors->any())<div class="phonebook-form-errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
          <form method="POST" action="{{ route('phonebook.store') }}">@csrf<input type="hidden" name="scope" value="building">
            <div class="phonebook-form-grid">
              <div class="fld"><label for="building-name">نام مخاطب *</label><input id="building-name" name="name" value="{{ old('scope') === 'building' ? old('name') : '' }}" maxlength="150" required placeholder="مثلاً آتش‌نشانی"></div>
              <div class="fld"><label for="building-phone">شماره تماس *</label><input id="building-phone" name="phone" value="{{ old('scope') === 'building' ? old('phone') : '' }}" maxlength="20" inputmode="tel" required placeholder="شماره تلفن"></div>
              <div class="fld"><label for="building-category">دسته‌بندی *</label><select id="building-category" name="category" required><option value="">انتخاب دسته</option>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('scope') === 'building' && old('category') === $key)>{{ $label }}</option>@endforeach</select></div>
            </div><div style="display:flex;justify-content:flex-start;margin-top:14px"><button class="btn" type="submit">ثبت شماره در دفترچهٔ ساختمان</button></div>
          </form>
        </div></section>
      @else
        @if($personalEntries->isEmpty())<div class="phonebook-empty"><strong>هنوز مخاطبی ذخیره نکرده‌ای</strong>شماره‌های موردنیازت را اضافه کن تا همیشه در دفترچهٔ شخصی‌ات در دسترس باشند.</div>
        @else
          <div class="phonebook-grid">
            @foreach($personalEntries as $contact)
              <article class="phonebook-contact">
                <div class="phonebook-contact-top"><div class="phonebook-avatar">{{ mb_substr($contact->name, 0, 1) }}</div><div class="phonebook-contact-main"><h4>{{ $contact->name }}</h4><span class="phonebook-chip">{{ $categories[$contact->category] ?? 'سایر شماره‌ها' }}</span></div></div>
                <a class="phonebook-phone" href="tel:{{ $contact->phone_href }}">{{ $F::digits($contact->phone) }}</a>
                <div class="phonebook-actions">
                  <details><summary class="btn sec sm">ویرایش</summary><form class="phonebook-edit" method="POST" action="{{ route('phonebook.update', $contact->id) }}">@csrf @method('PUT')
                    <div class="phonebook-form-grid" style="grid-template-columns:1fr">
                      <div class="fld"><label>نام مخاطب</label><input name="name" value="{{ $contact->name }}" maxlength="150" required></div>
                      <div class="fld"><label>شماره تماس</label><input name="phone" value="{{ $contact->phone }}" maxlength="20" inputmode="tel" required></div>
                      <div class="fld"><label>دسته‌بندی</label><select name="category" required>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected($contact->category === $key)>{{ $label }}</option>@endforeach</select></div>
                    </div><button class="btn sm" style="margin-top:10px" type="submit">ذخیره تغییرات</button>
                  </form></details>
                  <form method="POST" action="{{ route('phonebook.destroy', $contact->id) }}" data-confirm="این مخاطب از دفترچهٔ شخصی حذف شود؟" data-confirm-title="حذف مخاطب" data-confirm-icon="warning">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button></form>
                </div>
              </article>
            @endforeach
          </div>
          <div style="margin-top:16px">{{ $personalEntries->links() }}</div>
        @endif
        <section class="phonebook-panel" style="margin-top:18px"><div class="phonebook-panel-head"><div><h3>افزودن مخاطب شخصی</h3><p>این شماره فقط در دفترچهٔ خصوصی حساب خودت ذخیره می‌شود.</p></div></div><div class="phonebook-body">
          @if($errors->any())<div class="phonebook-form-errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
          <form method="POST" action="{{ route('phonebook.store') }}">@csrf<input type="hidden" name="scope" value="personal">
            <div class="phonebook-form-grid">
              <div class="fld"><label for="personal-name">نام مخاطب *</label><input id="personal-name" name="name" value="{{ old('scope', 'personal') === 'personal' ? old('name') : '' }}" maxlength="150" required placeholder="نام و نام خانوادگی"></div>
              <div class="fld"><label for="personal-phone">شماره تماس *</label><input id="personal-phone" name="phone" value="{{ old('scope', 'personal') === 'personal' ? old('phone') : '' }}" maxlength="20" inputmode="tel" required placeholder="شماره تلفن"></div>
              <div class="fld"><label for="personal-category">دسته‌بندی *</label><select id="personal-category" name="category" required><option value="">انتخاب دسته</option>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('scope', 'personal') === 'personal' && old('category') === $key)>{{ $label }}</option>@endforeach</select></div>
            </div><div style="display:flex;justify-content:flex-start;margin-top:14px"><button class="btn" type="submit">ذخیره مخاطب شخصی</button></div>
          </form>
        </div></section>
      @endif
    </div>
  </section>
</div>
@endsection
