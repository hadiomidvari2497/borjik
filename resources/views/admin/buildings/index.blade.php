@extends('admin.layout')
@section('title', 'ساختمان‌های سامانه')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $subscriptionLabel = function ($building) {
      $subscription = $building->subscription;
      if (! $subscription) return 'بدون اشتراک';
      if ($subscription->status === 'active' && $subscription->expires_at && $subscription->expires_at->isFuture()) return 'فعال';
      return 'منقضی / غیرفعال';
  };
@endphp
<style>
.bd-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:18px 0}
.bd-stat{border:1px solid var(--line);border-radius:14px;padding:15px;min-width:0}
.bd-stat .k{font-size:12px;color:var(--muted);margin-bottom:8px}.bd-stat .v{font-size:24px;font-weight:800;line-height:1.2}.bd-stat .hint{font-size:11px;color:var(--muted);margin-top:6px}
.bd-filters{display:grid;grid-template-columns:minmax(180px,2fr) repeat(3,minmax(140px,1fr)) auto;gap:10px;align-items:end}
.bd-filters .fld{min-width:0}.bd-filters label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}.bd-filters input,.bd-filters select{width:100%;min-width:0}
.bd-owner{display:flex;align-items:center;gap:9px;min-width:180px}.bd-avatar{width:36px;height:36px;flex:0 0 36px;border-radius:11px;display:grid;place-items:center;background:rgba(28,79,74,.1);color:var(--brand,#1c4f4a);font-weight:800}
.bd-owner strong{display:block;font-size:13px}.bd-owner small{display:block;color:var(--muted);font-size:11px;margin-top:4px;direction:ltr;text-align:right}
.bd-muted{font-size:12px;color:var(--muted)}.bd-status{display:inline-flex;align-items:center;gap:5px;white-space:nowrap}.bd-status:before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}
.bd-status.active{color:#16805d}.bd-status.inactive{color:#b45309}.bd-status.expired{color:#b45309}.bd-status.none{color:var(--muted)}
.bd-name{font-weight:750}.bd-code{display:inline-block;direction:ltr;font-size:11px;color:var(--muted);margin-top:4px}
@media(max-width:1100px){.bd-filters{grid-template-columns:repeat(3,minmax(0,1fr))}.bd-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:620px){.bd-filters{grid-template-columns:1fr}.bd-stats{gap:8px}.bd-stat{padding:12px}.bd-stat .v{font-size:21px}}
</style>
<div class="card">
  <div class="page-h">
    <div>
      <h2>مدیریت ساختمان‌های سامانه</h2>
      <p style="color:var(--muted);font-size:13px;line-height:1.8;margin:7px 0 0">فهرست ساختمان‌هایی که کاربران در پنل ساخته‌اند؛ همراه با سازنده، تعداد واحدها و وضعیت اشتراک.</p>
    </div>
  </div>
  <div class="bd-stats">
    <div class="bd-stat"><div class="k">کل ساختمان‌ها</div><div class="v">{{ $F::num($stats['total']) }}</div><div class="hint">ساختمان‌های ثبت‌شده</div></div>
    <div class="bd-stat"><div class="k">ساختمان‌های فعال</div><div class="v">{{ $F::num($stats['active']) }}</div><div class="hint">بر اساس وضعیت ساختمان</div></div>
    <div class="bd-stat"><div class="k">کل واحدها</div><div class="v">{{ $F::num($stats['units']) }}</div><div class="hint">واحدهای ثبت‌شده در ساختمان‌ها</div></div>
    <div class="bd-stat"><div class="k">اشتراک فعال</div><div class="v">{{ $F::num($stats['subscribed']) }}</div><div class="hint">اشتراک معتبر در حال حاضر</div></div>
  </div>
  <form method="GET" class="card" style="padding:14px;margin-bottom:16px">
    <div class="bd-filters">
      <div class="fld"><label for="bd-q">جست‌وجو</label><input id="bd-q" name="q" value="{{ $filters['q'] }}" placeholder="نام، کد، آدرس یا مشخصات سازنده"></div>
      <div class="fld"><label for="bd-type">نوع ساختمان</label><select id="bd-type" name="type"><option value="">همه انواع</option>@foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>@endforeach</select></div>
      <div class="fld"><label for="bd-status">وضعیت ساختمان</label><select id="bd-status" name="status"><option value="">همه وضعیت‌ها</option><option value="active" @selected($filters['status']==='active')>فعال</option><option value="inactive" @selected($filters['status']==='inactive')>غیرفعال</option></select></div>
      <div class="fld"><label for="bd-subscription">وضعیت اشتراک</label><select id="bd-subscription" name="subscription"><option value="">همه اشتراک‌ها</option><option value="active" @selected($filters['subscription']==='active')>اشتراک فعال</option><option value="expired" @selected($filters['subscription']==='expired')>منقضی / غیرفعال</option><option value="none" @selected($filters['subscription']==='none')>بدون اشتراک</option></select></div>
      <div class="fbar" style="margin:0;gap:6px"><button class="btn" type="submit">اعمال فیلتر</button><a class="btn sec" href="{{ route('admin.buildings.index') }}">پاک‌کردن</a></div>
    </div>
  </form>
  <div class="page-h" style="margin-bottom:10px"><h3 style="margin:0;font-size:15px">فهرست ساختمان‌ها</h3><span class="bd-muted">{{ $F::num($buildings->total()) }} نتیجه</span></div>
  @if($buildings->isEmpty())
    <div class="empty">ساختمانی با این مشخصات پیدا نشد. فیلترها را تغییر بده و دوباره جست‌وجو کن.</div>
  @else
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>ساختمان</th><th>سازنده / مدیر ثبت‌کننده</th><th>موقعیت</th><th>واحدها</th><th>اشتراک</th><th>وضعیت</th><th>تاریخ ثبت</th></tr></thead>
      <tbody>
      @foreach($buildings as $building)
        @php
          $owner = $building->createdBy;
          $ownerName = trim((string)($owner?->display_name ?? '')) ?: 'کاربر حذف‌شده';
          $subLabel = $subscriptionLabel($building);
          $subClass = $subLabel === 'فعال' ? 'active' : ($subLabel === 'بدون اشتراک' ? 'none' : 'expired');
          $typeValue = $building->type instanceof \BackedEnum ? $building->type->value : (string)$building->type;
        @endphp
        <tr>
          <td><div class="bd-name">{{ $building->name }}</div><span class="bd-code">کد: {{ $building->code }}</span><div class="bd-muted" style="margin-top:4px">{{ $typeLabels[$typeValue] ?? 'سایر' }}</div></td>
          <td><div class="bd-owner"><div class="bd-avatar">{{ mb_substr($ownerName, 0, 1) }}</div><div><strong>{{ $ownerName }}</strong><small>{{ $owner?->mobile ?? '—' }}</small></div></div></td>
          <td><div>{{ $building->city?->name ?? '—' }}{{ $building->province?->name ? '، '.$building->province->name : '' }}</div><div class="bd-muted" style="max-width:220px;margin-top:4px">{{ $building->address ?: 'آدرس ثبت نشده' }}</div></td>
          <td><strong>{{ $F::num($building->units_count) }}</strong><div class="bd-muted">واحد</div></td>
          <td><span class="bd-status {{ $subClass }}">{{ $subLabel }}</span><div class="bd-muted" style="margin-top:5px">{{ $building->subscription?->plan?->name ?? '—' }}</div>@if($building->subscription?->expires_at)<div class="bd-muted">تا {{ $F::date($building->subscription->expires_at, 'j F Y') }}</div>@endif</td>
          <td><span class="bd-status {{ $building->status === 'active' ? 'active' : 'inactive' }}">{{ $building->status === 'active' ? 'فعال' : 'غیرفعال' }}</span></td>
          <td>{{ $building->created_at ? $F::date($building->created_at, 'j F Y') : '—' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    <div style="margin-top:16px">{{ $buildings->links() }}</div>
  @endif
</div>
@endsection
