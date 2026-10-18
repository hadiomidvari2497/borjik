@extends('admin.layout')
@section('title', 'تاریخچه فعالیت مدیران')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $methodLabels = ['POST' => 'ایجاد / اجرا', 'PUT' => 'ویرایش', 'PATCH' => 'به‌روزرسانی', 'DELETE' => 'حذف'];
@endphp
<style>
.al-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:18px 0}.al-stat{border:1px solid var(--line);border-radius:14px;padding:14px}.al-stat small{display:block;color:var(--muted);font-size:12px;margin-bottom:7px}.al-stat strong{font-size:24px}
.al-filters{display:grid;grid-template-columns:minmax(180px,2fr) repeat(4,minmax(120px,1fr));gap:10px;align-items:end}.al-filters label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}.al-filters input,.al-filters select{width:100%;min-width:0}
.al-muted{font-size:12px;color:var(--muted)}.al-method{display:inline-flex;padding:4px 8px;border-radius:7px;background:rgba(28,79,74,.1);font-size:11px;white-space:nowrap}.al-path{direction:ltr;text-align:right;word-break:break-all;font-size:12px}.al-admin{font-weight:750}.al-date{white-space:nowrap;font-size:12px}
@media(max-width:1000px){.al-filters{grid-template-columns:repeat(3,minmax(0,1fr))}.al-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:620px){.al-filters{grid-template-columns:1fr}.al-stats{gap:7px}.al-stat{padding:10px}.al-stat strong{font-size:20px}}
</style>
<div class="card">
  <div class="page-h"><div><h2>تاریخچه فعالیت مدیران</h2><p style="color:var(--muted);font-size:13px;line-height:1.8;margin:7px 0 0">ثبت عملیات موفق ایجاد، ویرایش و حذف در پنل مدیریت؛ بدون ذخیره رمز عبور، کلیدها یا محتوای فرم‌ها.</p></div></div>
  <div class="al-stats">
    <div class="al-stat"><small>عملیات امروز</small><strong>{{ $F::num($stats['today']) }}</strong></div>
    <div class="al-stat"><small>عملیات ۷ روز اخیر</small><strong>{{ $F::num($stats['week']) }}</strong></div>
    <div class="al-stat"><small>مدیران ثبت‌شده در لاگ</small><strong>{{ $F::num($stats['admins']) }}</strong></div>
  </div>
  <form method="GET" class="card" style="padding:14px;margin-bottom:16px">
    <div class="al-filters">
      <div><label for="al-q">جست‌وجو</label><input id="al-q" name="q" value="{{ $filters['q'] }}" placeholder="نام مدیر، مسیر، عملیات یا IP"></div>
      <div><label for="al-admin">مدیر</label><select id="al-admin" name="admin_id"><option value="">همه مدیران</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected((string)$filters['admin_id']===(string)$admin->id)>{{ $admin->name ?: $admin->username }}</option>@endforeach</select></div>
      <div><label for="al-method">نوع عملیات</label><select id="al-method" name="method"><option value="">همه عملیات‌ها</option>@foreach($methodLabels as $method => $label)<option value="{{ $method }}" @selected($filters['method']===$method)>{{ $label }}</option>@endforeach</select></div>
      <div><label for="al-from">از تاریخ</label><input id="al-from" type="date" name="from" value="{{ $filters['from'] }}"></div>
      <div><label for="al-to">تا تاریخ</label><input id="al-to" type="date" name="to" value="{{ $filters['to'] }}"></div>
    </div>
    <div class="fbar" style="margin-top:12px"><button class="btn" type="submit">اعمال فیلتر</button><a class="btn sec" href="{{ route('admin.activity-logs.index') }}">پاک‌کردن</a></div>
  </form>
  <div class="page-h" style="margin-bottom:10px"><h3 style="margin:0;font-size:15px">جزئیات عملیات</h3><span class="al-muted">{{ $F::num($logs->total()) }} نتیجه</span></div>
  @if($logs->isEmpty())
    <div class="empty">عملیاتی با این فیلترها پیدا نشد. این بخش از زمان فعال‌شدن ثبت رویدادها به بعد اطلاعات جمع می‌کند.</div>
  @else
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>مدیر</th><th>عملیات</th><th>نوع</th><th>مسیر</th><th>نتیجه</th><th>IP</th><th>زمان</th></tr></thead>
      <tbody>
      @foreach($logs as $log)
        <tr>
          <td><div class="al-admin">{{ $log->admin?->name ?: ($log->admin?->username ?: 'مدیر حذف‌شده') }}</div><div class="al-muted">شناسه: {{ $F::num($log->admin_user_id ?? 0) }}</div></td>
          <td><div>{{ $log->action }}</div><div class="al-muted">نام مسیر: {{ $log->route_name ?: '—' }}</div></td>
          <td><span class="al-method">{{ $methodLabels[$log->method] ?? $log->method }}</span></td>
          <td><div class="al-path">{{ $log->path }}</div><div class="al-muted" style="margin-top:4px">کد پاسخ: {{ $log->status_code ?? '—' }}</div></td>
          <td><span class="al-muted">{{ ($log->status_code >= 200 && $log->status_code < 400) ? 'موفق' : 'نامشخص' }}</span></td>
          <td><span class="al-path">{{ $log->ip_address ?: '—' }}</span></td>
          <td class="al-date">{{ $log->created_at ? $F::date($log->created_at, 'j F Y H:i') : '—' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    <div style="margin-top:16px">{{ $logs->links() }}</div>
  @endif
</div>
@endsection
