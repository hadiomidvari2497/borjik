@php
  $permissionLabels = [
    'ticket.view.assigned' => 'مشاهده تیکت‌های ارجاع‌شده',
    'ticket.respond' => 'پاسخ‌گویی به تیکت‌ها',
    'request.view.domain' => 'مشاهده درخواست‌های مرتبط',
    'event.view' => 'مشاهده رویدادها و اطلاعیه‌ها',
    'document.view' => 'مشاهده اسناد ساختمان',
    'phonebook.personal' => 'دسترسی به دفترچه تلفن',
    'report.view.own' => 'مشاهده گزارش‌های کاری خودم',
    'notification.view' => 'مشاهده اعلان‌ها',
    'building.view' => 'مشاهده اطلاعات ساختمان',
    'shift.view' => 'مشاهده برنامه شیفت‌ها',
    'supply.manage' => 'مدیریت تجهیزات و ملزومات',
  ];
  $permissionGroups = [
    'امور درخواست‌ها و تیکت‌ها' => ['ticket.view.assigned', 'ticket.respond', 'request.view.domain'],
    'اطلاعات و ارتباطات' => ['event.view', 'document.view', 'phonebook.personal', 'notification.view', 'building.view'],
    'امور داخلی پرسنل' => ['report.view.own', 'shift.view', 'supply.manage'],
  ];
@endphp
@extends('layouts.app')
@section('title', 'مدیریت نقش‌های پرسنلی')
@section('content')
<style>
.role-page{--role-ink:#17233b;--role-muted:#69758b;--role-line:#e5eaf2;color:var(--role-ink)}
.role-hero{position:relative;overflow:hidden;border-radius:24px;padding:clamp(22px,4vw,38px);margin-bottom:20px;background:radial-gradient(circle at 8% 10%,rgba(255,255,255,.22),transparent 28%),linear-gradient(125deg,#182b50 0%,#344f83 52%,#6c64b5 100%);color:#fff;box-shadow:0 18px 45px rgba(35,55,100,.18)}
.role-hero:after{content:"";position:absolute;width:250px;height:250px;border:1px solid rgba(255,255,255,.16);border-radius:50%;inset-inline-end:-70px;top:-120px;box-shadow:0 0 0 28px rgba(255,255,255,.035),0 0 0 58px rgba(255,255,255,.025);pointer-events:none}
.role-hero-inner{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
.role-eyebrow{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,.24);background:rgba(255,255,255,.1);border-radius:999px;padding:6px 11px;font-size:12px;color:#f0f3ff;margin-bottom:14px}
.role-hero h2{font-size:clamp(23px,3vw,32px);font-weight:850;letter-spacing:-.5px;color:#fff;margin:0 0 10px}
.role-hero p{max-width:650px;line-height:1.95;color:#e3e9fa;margin:0;font-size:14px}
.role-stat{min-width:120px;padding:18px 22px;border-radius:18px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(8px);text-align:center}
.role-stat strong{display:block;font-size:32px;line-height:1.25;color:#fff}.role-stat span{font-size:12px;color:#e3e9fa}
.role-alert{border-radius:14px;padding:12px 15px;margin:0 0 14px;font-size:13px;line-height:1.8}
.role-alert.success{background:#eaf8f0;color:#17633b;border:1px solid #c8eed8}.role-alert.error{background:#fff0f0;color:#9b2828;border:1px solid #ffd6d6}
.role-layout{display:grid;grid-template-columns:minmax(0,1fr);gap:20px}
.role-panel{background:var(--card,#fff);border:1px solid var(--role-line);border-radius:22px;overflow:hidden;box-shadow:0 8px 24px rgba(30,47,78,.045)}
.role-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:22px 24px;border-bottom:1px solid var(--role-line);background:linear-gradient(180deg,#fff,#fbfcff)}
.role-panel-head h3{font-size:17px;font-weight:800;margin:0 0 5px;color:var(--role-ink)}.role-panel-head p{font-size:12px;line-height:1.8;color:var(--role-muted);margin:0}
.role-number{display:grid;place-items:center;min-width:40px;height:40px;border-radius:13px;background:#eef2ff;color:#4957a8;font-weight:800}
.role-panel-body{padding:22px 24px}
.role-name-field{display:block;margin-bottom:22px}.role-name-field span{display:block;font-size:12px;font-weight:750;color:#45516a;margin-bottom:8px}
.role-name-field input{width:100%;max-width:560px;border:1px solid #dce3ef;border-radius:12px;padding:12px 14px;background:#fff;color:var(--role-ink);outline:none;transition:border-color .18s,box-shadow .18s}
.role-name-field input:focus{border-color:#7378d7;box-shadow:0 0 0 4px rgba(105,111,214,.12)}
.role-permission-title{font-size:12px;font-weight:800;color:#45516a;margin:0 0 12px}
.role-permission-group{margin-bottom:18px}.role-group-label{display:flex;align-items:center;gap:8px;font-size:12px;color:#778198;font-weight:750;margin-bottom:9px}.role-group-label:before{content:"";width:5px;height:5px;border-radius:50%;background:#8585df}
.role-permissions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
.role-permission{position:relative;display:flex;align-items:center;gap:10px;min-height:48px;padding:10px 12px;border:1px solid #e5eaf3;border-radius:13px;background:#fff;cursor:pointer;font-size:12px;font-weight:600;color:#39465e;transition:border-color .16s,background .16s,transform .16s}
.role-permission:hover{border-color:#b9c0f0;background:#fafaff;transform:translateY(-1px)}
.role-permission input{width:17px;height:17px;flex:0 0 auto;margin:0;accent-color:#625fca}
.role-permission:has(input:checked){border-color:#bfc5f6;background:#f5f5ff;color:#343b85}
.role-form-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding-top:18px;border-top:1px solid #edf0f6}
.role-save{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:12px;padding:11px 17px;background:linear-gradient(120deg,#4e5fbc,#7167d4);color:#fff;font-weight:750;font-size:12px;cursor:pointer;box-shadow:0 7px 15px rgba(85,91,190,.18);transition:transform .16s,box-shadow .16s}
.role-save:hover{transform:translateY(-1px);box-shadow:0 10px 20px rgba(85,91,190,.24)}
.role-delete{display:inline-flex;align-items:center;justify-content:center;border:1px solid #f0d6d6;border-radius:11px;padding:9px 13px;background:#fff7f7;color:#a83d49;font-weight:700;font-size:12px;cursor:pointer}
.role-default{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;background:#edf8f2;color:#24784c;font-size:11px;font-weight:750}
.role-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:20px}
.role-card{min-width:0;border:1px solid var(--role-line);border-radius:18px;overflow:hidden;background:#fff;transition:box-shadow .2s,border-color .2s}
.role-card:hover{border-color:#d1d5f5;box-shadow:0 12px 30px rgba(42,53,99,.08)}
.role-card-top{display:flex;align-items:center;gap:12px;padding:17px 18px;background:linear-gradient(180deg,#fff,#fafbff);border-bottom:1px solid #edf0f6}
.role-card-icon{display:grid;place-items:center;width:42px;height:42px;flex:0 0 auto;border-radius:14px;background:linear-gradient(145deg,#eef0ff,#e8f3ff);color:#535fc0}
.role-card-icon svg{width:21px;height:21px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.role-card-title{min-width:0;flex:1}.role-card-title h4{font-size:14px;font-weight:800;color:var(--role-ink);margin:0 0 5px;overflow-wrap:anywhere}.role-card-title p{font-size:11px;color:var(--role-muted);margin:0}
.role-card-content{padding:18px}
.role-card .role-name-field{margin-bottom:20px}.role-card .role-name-field input{max-width:none}
.role-card .role-permissions{grid-template-columns:1fr}
.role-card .role-permission{min-height:42px;padding:9px 11px}
.role-card .role-permission-group{margin-bottom:14px}
.role-card .role-form-actions{gap:8px}
.role-card .role-save{flex:1}
.role-card .role-delete-form{padding:0 18px 18px}
.role-empty{margin:20px;padding:35px 20px;border:1px dashed #d8deeb;border-radius:17px;text-align:center;color:var(--role-muted);background:#fafbfe}
.role-empty strong{display:block;color:#34415c;margin:10px 0 5px;font-size:14px}.role-empty p{font-size:12px;line-height:1.9;margin:0}
@media(min-width:1120px){.role-layout{grid-template-columns:minmax(0,1fr)}.role-create{align-self:stretch;position:static}.role-list{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.role-list{grid-template-columns:1fr;padding:12px}.role-panel-head,.role-panel-body{padding:18px}.role-permissions{grid-template-columns:1fr}.role-hero{border-radius:19px}.role-stat{min-width:100px;padding:12px 16px}.role-stat strong{font-size:26px}.role-card-content{padding:15px}}

/* Dark-theme correction: role editor cards, permissions and alerts follow shared tokens. */
:root[data-theme="dark"] .role-alert.success{background:rgba(47,160,96,.16);color:#91e0ae;border-color:rgba(47,160,96,.3)}
:root[data-theme="dark"] .role-alert.error{background:rgba(224,69,58,.15);color:#ffaaa3;border-color:rgba(255,130,120,.3)}
:root[data-theme="dark"] .role-number,
:root[data-theme="dark"] .role-card-icon{background:var(--brand-soft);color:var(--brand)}
:root[data-theme="dark"] .role-name-field span,
:root[data-theme="dark"] .role-permission-title{color:var(--text)}
:root[data-theme="dark"] .role-group-label{color:var(--muted)}
:root[data-theme="dark"] .role-name-field input,
:root[data-theme="dark"] .role-permission,
:root[data-theme="dark"] .role-card{background:var(--card);color:var(--text);border-color:var(--line)}
:root[data-theme="dark"] .role-permission:hover,
:root[data-theme="dark"] .role-permission:has(input:checked){background:var(--brand-soft);border-color:var(--brand);color:var(--brand)}
:root[data-theme="dark"] .role-delete{background:rgba(224,69,58,.14);border-color:rgba(255,130,120,.3);color:#ffaaa3}
:root[data-theme="dark"] .role-default{background:rgba(47,160,96,.16);color:#91e0ae}
:root[data-theme="dark"] .role-empty{background:var(--panel);border-color:var(--line);color:var(--muted)}
:root[data-theme="dark"] .role-empty strong{color:var(--text)}
:root[data-theme="dark"] .role-name-field input:focus{border-color:var(--brand);box-shadow:0 0 0 4px var(--brand-soft)}
@media(prefers-color-scheme:dark){
 :root:not([data-theme="light"]) .role-alert.success{background:rgba(47,160,96,.16);color:#91e0ae;border-color:rgba(47,160,96,.3)}
 :root:not([data-theme="light"]) .role-alert.error{background:rgba(224,69,58,.15);color:#ffaaa3;border-color:rgba(255,130,120,.3)}
 :root:not([data-theme="light"]) .role-number,
 :root:not([data-theme="light"]) .role-card-icon{background:var(--brand-soft);color:var(--brand)}
 :root:not([data-theme="light"]) .role-name-field span,
 :root:not([data-theme="light"]) .role-permission-title{color:var(--text)}
 :root:not([data-theme="light"]) .role-group-label{color:var(--muted)}
 :root:not([data-theme="light"]) .role-name-field input,
 :root:not([data-theme="light"]) .role-permission,
 :root:not([data-theme="light"]) .role-card{background:var(--card);color:var(--text);border-color:var(--line)}
 :root:not([data-theme="light"]) .role-permission:hover,
 :root:not([data-theme="light"]) .role-permission:has(input:checked){background:var(--brand-soft);border-color:var(--brand);color:var(--brand)}
 :root:not([data-theme="light"]) .role-delete{background:rgba(224,69,58,.14);border-color:rgba(255,130,120,.3);color:#ffaaa3}
 :root:not([data-theme="light"]) .role-default{background:rgba(47,160,96,.16);color:#91e0ae}
 :root:not([data-theme="light"]) .role-empty{background:var(--panel);border-color:var(--line);color:var(--muted)}
 :root:not([data-theme="light"]) .role-empty strong{color:var(--text)}
 :root:not([data-theme="light"]) .role-name-field input:focus{border-color:var(--brand);box-shadow:0 0 0 4px var(--brand-soft)}
}

</style>
<div class="role-page">
  <section class="role-hero">
    <div class="role-hero-inner">
      <div>
        <div class="role-eyebrow"><span>✦</span> مرکز مدیریت دسترسی‌ها</div>
        <h2>نقش‌های پرسنلی، دقیقاً مطابق ساختمان شما</h2>
        <p>برای هر مسئولیت یک نقش بسازید، دسترسی‌های لازم را انتخاب کنید و تیم ساختمان را منظم‌تر مدیریت کنید. تغییرات این صفحه فقط روی همین ساختمان اثر می‌گذارد.</p>
      </div>
      <div class="role-stat"><strong>{{ $roles->count() }}</strong><span>نقش تعریف‌شده</span></div>
    </div>
  </section>

  @if(session('status'))<div class="role-alert success" role="status">✓ {{ session('status') }}</div>@endif
  @if($errors->any())<div class="role-alert error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

  <div class="role-layout">
    <section class="role-panel role-create">
      <div class="role-panel-head">
        <div><h3>ساخت نقش تازه</h3><p>عنوانی روشن انتخاب کنید و دسترسی‌های موردنیاز را مشخص کنید.</p></div>
        <span class="role-number">＋</span>
      </div>
      <div class="role-panel-body">
        <form method="POST" action="{{ route('staff-roles.store') }}">@csrf
          <label class="role-name-field" for="new-name"><span>نام نقش جدید</span><input id="new-name" name="name_fa" maxlength="60" required value="{{ old('name_fa') }}" placeholder="مثلاً مسئول تأسیسات"></label>
          <p class="role-permission-title">دسترسی‌های قابل واگذاری</p>
          @foreach($permissionGroups as $group => $keys)
            <div class="role-permission-group">
              <div class="role-group-label">{{ $group }}</div>
              <div class="role-permissions">
                @foreach($keys as $key)
                  @if($permissions->contains('key', $key))
                    <label class="role-permission"><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, old('permissions', []), true))><span>{{ $permissionLabels[$key] ?? $key }}</span></label>
                  @endif
                @endforeach
              </div>
            </div>
          @endforeach
          <div class="role-form-actions"><button class="role-save" type="submit">＋ ایجاد نقش</button></div>
        </form>
      </div>
    </section>

    <section class="role-panel">
      <div class="role-panel-head">
        <div><h3>نقش‌های این ساختمان</h3><p>هر نقش را باز کنید تا نام و دسترسی‌هایش را ویرایش کنید.</p></div>
        <span class="role-number">{{ $roles->count() }}</span>
      </div>
      @if($roles->isEmpty())
        <div class="role-empty"><span style="font-size:28px">✧</span><strong>هنوز نقشی ساخته نشده است</strong><p>از فرم ساخت نقش تازه استفاده کنید تا اولین نقش اختصاصی ساختمان را بسازید.</p></div>
      @else
        <div class="role-list">
          @foreach($roles as $role)
            <article class="role-card">
              <div class="role-card-top">
                <span class="role-card-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 19 6v5c0 4.5-2.8 7.7-7 10-4.2-2.3-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                <div class="role-card-title"><h4>{{ $role->name_fa }}</h4><p>{{ $role->permissions->count() }} دسترسی فعال</p></div>
                @if($role->is_default)<span class="role-default">پیش‌فرض</span>@endif
              </div>
              <div class="role-card-content">
                <form method="POST" action="{{ route('staff-roles.update', $role->id) }}">@csrf @method('PUT')
                  <label class="role-name-field" for="role-{{ $role->id }}"><span>نام نقش</span><input id="role-{{ $role->id }}" name="name_fa" maxlength="60" required value="{{ $role->name_fa }}"></label>
                  <p class="role-permission-title">دسترسی‌ها</p>
                  @foreach($permissionGroups as $group => $keys)
                    <div class="role-permission-group">
                      <div class="role-group-label">{{ $group }}</div>
                      <div class="role-permissions">
                        @foreach($keys as $key)
                          @if($permissions->contains('key', $key))
                            <label class="role-permission"><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked($role->permissions->contains('key', $key))><span>{{ $permissionLabels[$key] ?? $key }}</span></label>
                          @endif
                        @endforeach
                      </div>
                    </div>
                  @endforeach
                  <div class="role-form-actions"><button class="role-save" type="submit">ذخیره تغییرات</button></div>
                </form>
              </div>
              <form class="role-delete-form" method="POST" action="{{ route('staff-roles.destroy', $role->id) }}" data-confirm="از حذف این نقش مطمئن هستید؟" data-confirm-title="حذف نقش" data-confirm-icon="warning">@csrf @method('DELETE')<button class="role-delete" type="submit">حذف نقش</button></form>
            </article>
          @endforeach
        </div>
      @endif
    </section>
  </div>
</div>
@endsection
