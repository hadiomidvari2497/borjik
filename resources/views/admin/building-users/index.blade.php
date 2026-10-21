@extends('admin.layout')
@section('title', 'کاربران ساختمان‌ها')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $membershipLabels = ['active' => 'عضو فعال', 'invited' => 'دعوت‌شده', 'inactive' => 'غیرفعال', 'rejected' => 'دعوت رد شده'];
@endphp
<style>
.bu-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:18px 0}
.bu-stat{border:1px solid var(--line);border-radius:14px;padding:15px;min-width:0}
.bu-stat .k{font-size:12px;color:var(--muted);margin-bottom:8px}.bu-stat .v{font-size:24px;font-weight:800;line-height:1.2}.bu-stat .hint{font-size:11px;color:var(--muted);margin-top:6px}
.bu-filters{display:grid;grid-template-columns:minmax(180px,2fr) repeat(4,minmax(130px,1fr)) auto;gap:10px;align-items:end}
.bu-filters .fld{min-width:0}.bu-filters label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}.bu-filters input,.bu-filters select{width:100%;min-width:0}
.bu-user{display:flex;align-items:center;gap:10px;min-width:190px}.bu-avatar{width:38px;height:38px;flex:0 0 38px;border-radius:12px;display:grid;place-items:center;background:rgba(28,79,74,.1);color:var(--brand,#1c4f4a);font-weight:800}
.bu-user strong{display:block;font-size:13px}.bu-user small{display:block;color:var(--muted);font-size:11px;margin-top:4px;direction:ltr;text-align:right}
.bu-building{display:flex;flex-wrap:wrap;gap:5px;max-width:300px}.bu-building .pill{white-space:normal;line-height:1.6}.bu-muted{font-size:12px;color:var(--muted)}
.bu-status{display:inline-flex;align-items:center;gap:5px}.bu-status:before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}
.bu-status.active{color:#16805d}.bu-status.disabled{color:#b45309}
@media(max-width:1100px){.bu-filters{grid-template-columns:repeat(3,minmax(0,1fr))}.bu-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:620px){.bu-filters{grid-template-columns:1fr}.bu-stats{gap:8px}.bu-stat{padding:12px}.bu-stat .v{font-size:21px}}
</style>
<div class="card">
  <div class="page-h"><div><h2>مدیریت کاربران پنل ساختمان‌ها</h2><p style="color:var(--muted);font-size:13px;line-height:1.8;margin:7px 0 0">جست‌وجوی سراسری کاربران و بررسی نقش و وضعیت عضویت آن‌ها در هر ساختمان؛ جدا از حساب‌های ادمین سیستم.</p></div></div>
  <div class="bu-stats">
    <div class="bu-stat"><div class="k">کل کاربران سامانه</div><div class="v">{{ $F::num($stats['total']) }}</div><div class="hint">حساب‌های ثبت‌شده</div></div>
    <div class="bu-stat"><div class="k">حساب‌های فعال</div><div class="v">{{ $F::num($stats['active']) }}</div><div class="hint">وضعیت حساب کاربری</div></div>
    <div class="bu-stat"><div class="k">موبایل تأییدشده</div><div class="v">{{ $F::num($stats['verified']) }}</div><div class="hint">تأیید شماره همراه</div></div>
    <div class="bu-stat"><div class="k">دارای عضویت فعال</div><div class="v">{{ $F::num($stats['memberships']) }}</div><div class="hint">کاربران یکتای عضو ساختمان</div></div>
  </div>
  <form method="GET" class="card" style="padding:14px;margin-bottom:16px">
    <div class="bu-filters">
      <div class="fld"><label for="bu-q">جست‌وجو</label><input id="bu-q" name="q" value="{{ $filters['q'] }}" placeholder="نام، موبایل یا کد ملی"></div>
      <div class="fld"><label for="bu-building">ساختمان</label><select id="bu-building" name="building_id"><option value="">همه ساختمان‌ها</option>@foreach($buildings as $b)<option value="{{ $b->id }}" @selected((string)$filters['building_id'] === (string)$b->id)>{{ $b->name }}</option>@endforeach</select></div>
      <div class="fld"><label for="bu-role">نقش</label><select id="bu-role" name="role_key"><option value="">همه نقش‌ها</option>@foreach($roles as $r)<option value="{{ $r->key }}" @selected((string)$filters['role_key'] === $r->key)>{{ $r->name_fa }}</option>@endforeach</select></div>
      <div class="fld"><label for="bu-status">وضعیت حساب</label><select id="bu-status" name="status"><option value="">همه</option><option value="active" @selected($filters['status']==='active')>فعال</option><option value="disabled" @selected($filters['status']==='disabled')>غیرفعال</option></select></div>
      <div class="fld"><label for="bu-membership">وضعیت عضویت</label><select id="bu-membership" name="membership_status"><option value="">همه وضعیت‌ها</option>@foreach($membershipLabels as $key => $label)<option value="{{ $key }}" @selected($filters['membership_status']===$key)>{{ $label }}</option>@endforeach</select></div>
      <div class="fbar" style="margin:0;gap:6px"><button class="btn" type="submit">اعمال فیلتر</button><a class="btn sec" href="{{ route('admin.building-users.index') }}">پاک‌کردن</a></div>
    </div>
  </form>
  <div class="page-h" style="margin-bottom:10px"><h3 style="margin:0;font-size:15px">فهرست کاربران</h3><span class="bu-muted">{{ $F::num($users->total()) }} نتیجه</span></div>
  @if($users->isEmpty())
    <div class="empty">با این مشخصات کاربری پیدا نشد. فیلترها را تغییر بده و دوباره جست‌وجو کن.</div>
  @else
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>کاربر</th><th>وضعیت حساب</th><th>ساختمان‌ها و نقش‌ها</th>@if($canViewFinance)<th>اشتراک و پرداخت</th>@endif<th>تأیید موبایل</th><th>آخرین ورود</th></tr></thead>
      <tbody>
      @foreach($users as $u)
        @php $name = trim((string)$u->display_name) ?: 'کاربر بدون نام'; @endphp
        <tr>
          <td><div class="bu-user"><div class="bu-avatar">{{ mb_substr($name, 0, 1) }}</div><div><strong>{{ $name }}</strong><small>{{ $u->mobile }}</small><div class="bu-muted" style="margin-top:4px">شناسه: {{ $F::digits($u->id) }}</div></div></div></td>
          <td><span class="bu-status {{ $u->status === 'active' ? 'active' : 'disabled' }}">{{ $u->status === 'active' ? 'فعال' : 'غیرفعال' }}</span></td>
          <td>@if($u->buildingRoles->isEmpty())<span class="bu-muted">هنوز عضویتی ثبت نشده</span>@else<div class="bu-building">
            @foreach($u->buildingRoles as $m)
              <span class="pill" title="{{ $m->role?->name_fa ?: 'بدون نقش' }} — {{ $membershipLabels[$m->status?->value ?? $m->status] ?? 'نامشخص' }}">{{ $m->building?->name ?: 'ساختمان حذف‌شده' }} · {{ $m->role?->name_fa ?: 'بدون نقش' }} <span class="bu-muted">({{ $membershipLabels[$m->status?->value ?? $m->status] ?? 'نامشخص' }})</span></span>
            @endforeach
          </div>@endif</td>
          @if($canViewFinance)
            @php
              $paymentLabels = ['paid' => 'آخرین پرداخت موفق', 'pending' => 'پرداخت در انتظار', 'failed' => 'آخرین پرداخت ناموفق'];
              $subscriptionLabels = ['active' => 'اشتراک فعال', 'pending' => 'اشتراک در انتظار', 'expired' => 'اشتراک منقضی', 'failed' => 'اشتراک ناموفق'];
            @endphp
            <td style="min-width:190px">
              <div style="font-weight:800;font-size:12px">{{ $subscriptionLabels[$u->latest_subscription_status ?? ''] ?? 'بدون سابقه اشتراک' }}</div>
              @if($u->active_subscription_expires_at)
                <div class="bu-muted" style="margin-top:4px">اعتبار تا {{ $F::date(\\Illuminate\\Support\\Carbon::parse($u->active_subscription_expires_at), 'j F Y') }}</div>
              @endif
              <div class="bu-muted" style="margin-top:5px">پرداخت موفق: {{ $F::num(number_format((float)($u->paid_total ?? 0))) }} تومان</div>
              @if($u->latest_payment_status)
                <div class="bu-muted" style="margin-top:3px">{{ $paymentLabels[$u->latest_payment_status] ?? 'وضعیت پرداخت: '.$u->latest_payment_status }}</div>
              @endif
              <a href="{{ route('admin.subscription-payments.index', ['q' => $u->mobile]) }}" style="display:inline-block;margin-top:6px;font-size:11px;font-weight:800;text-decoration:none">تاریخچه مالی ←</a>
            </td>
          @endif
          <td>@if($u->mobile_verified_at)<span class="bu-status active">تأییدشده</span>@else<span class="bu-muted">تأیید نشده</span>@endif</td>
          <td>@if($u->last_login_at){{ $F::date(\Illuminate\Support\Carbon::parse($u->last_login_at), 'j F Y - H:i') }}@else<span class="bu-muted">هنوز وارد نشده</span>@endif</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    <div style="margin-top:16px">{{ $users->links() }}</div>
  @endif
</div>
@endsection
