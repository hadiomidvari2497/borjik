@extends('admin.layout')
@section('title', 'تحلیل ساختمان‌ها')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<style>
.ba-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}.ba-stat,.ba-panel{border:1px solid var(--line);border-radius:14px;padding:15px;min-width:0}.ba-label{font-size:12px;color:var(--muted);display:block;margin-bottom:8px}.ba-value{font-size:25px;font-weight:850}.ba-muted{font-size:12px;color:var(--muted);line-height:1.8}.ba-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.ba-table{width:100%;border-collapse:collapse}.ba-table th,.ba-table td{padding:11px;border-bottom:1px solid var(--line);text-align:right;white-space:nowrap}.ba-table th{font-size:12px;color:var(--muted)}.ba-bar{height:7px;background:var(--line);border-radius:20px;overflow:hidden;min-width:65px}.ba-bar span{display:block;height:100%;background:var(--brand,#1c4f4a);border-radius:20px}
@media(max-width:900px){.ba-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ba-table-wrap{overflow:auto}.ba-table{min-width:760px}}@media(max-width:560px){.ba-grid{grid-template-columns:1fr}.ba-value{font-size:21px}}
</style>
<div class="card">
  <div class="ba-head"><div><h2 style="margin:0">تحلیل عملکرد ساختمان‌ها</h2><p class="ba-muted">آمار تجمیعی ساختمان‌ها و مقایسهٔ فعالیت‌ها در بازهٔ انتخابی؛ داده‌ها به تفکیک شناسهٔ هر ساختمان محاسبه می‌شوند.</p></div>
    <form method="GET" style="display:flex;align-items:center;gap:8px"><label for="period" class="ba-muted">بازه:</label><select id="period" name="period" onchange="this.form.submit()"><option value="7" @selected($days===7)>۷ روز</option><option value="30" @selected($days===30)>۳۰ روز</option><option value="90" @selected($days===90)>۹۰ روز</option></select></form>
  </div>
  <div class="ba-grid">
    <div class="ba-stat"><span class="ba-label">کل ساختمان‌ها</span><div class="ba-value">{{ $F::num($stats['buildings']) }}</div><span class="ba-muted">{{ $F::num($stats['new_buildings']) }} ساختمان جدید در این بازه</span></div>
    <div class="ba-stat"><span class="ba-label">واحدها</span><div class="ba-value">{{ $F::num($stats['units']) }}</div><span class="ba-muted">مجموع واحدهای ثبت‌شده</span></div>
    <div class="ba-stat"><span class="ba-label">عضویت‌های فعال</span><div class="ba-value">{{ $F::num($stats['memberships']) }}</div><span class="ba-muted">{{ $F::num($stats['new_users']) }} کاربر جدید در سامانه</span></div>
    <div class="ba-stat"><span class="ba-label">تیکت‌ها</span><div class="ba-value">{{ $F::num($stats['tickets']) }}</div><span class="ba-muted">{{ $F::num($stats['new_tickets']) }} تیکت جدید در این بازه</span></div>
    <div class="ba-stat"><span class="ba-label">رسیدهای پرداخت در انتظار تأیید</span><div class="ba-value">{{ $F::num($stats['pending_receipts']) }}</div><span class="ba-muted">مجموع رسیدهای ثبت‌شده: {{ $F::num($stats['receipts']) }}</span></div>
  </div>
  <section class="ba-panel" style="margin-top:16px"><div class="ba-head"><h3 style="margin:0">مقایسهٔ ساختمان‌ها</h3><span class="ba-muted">بازه از {{ $F::date($periodStart, 'j F Y') }}</span></div>
    <div class="ba-table-wrap" style="margin-top:12px"><table class="ba-table"><thead><tr><th>ساختمان</th><th>واحدها</th><th>عضویت فعال</th><th>عضویت جدید</th><th>کل تیکت</th><th>تیکت جدید</th><th>صورتحساب‌ها</th><th>رسید پرداخت</th><th>در انتظار تأیید</th><th>مقایسه فعالیت</th></tr></thead><tbody>
    @forelse($rows as $row)
      @php $activityMax = max(1, (int) $rows->max('new_tickets')); $activity = min(100, (int) round($row['new_tickets'] / $activityMax * 100)); @endphp
      <tr><td><strong>{{ $row['name'] }}</strong><div class="ba-muted">شناسه: {{ $F::num($row['id']) }}</div></td><td>{{ $F::num($row['units']) }}</td><td>{{ $F::num($row['memberships']) }}</td><td>{{ $F::num($row['new_memberships']) }}</td><td>{{ $F::num($row['tickets']) }}</td><td>{{ $F::num($row['new_tickets']) }}</td><td>{{ $F::num($row['invoices']) }}</td><td>{{ $F::num($row['receipts']) }}</td><td>{{ $F::num($row['pending_receipts']) }}</td><td><div class="ba-bar"><span style="width:{{ $activity }}%"></span></div></td></tr>
    @empty<tr><td colspan="10">هنوز ساختمانی ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div>
  </section>
  <p class="ba-muted" style="margin-top:14px">این گزارش بر اساس جدول‌های موجود در نصب فعلی ساخته می‌شود. اگر جدول تیکت یا صورتحساب در یک نسخه وجود نداشته باشد، برای آن شاخص صفر نمایش داده می‌شود؛ این مقدار به معنی اثبات نبودن فعالیت در سیستم خارجی نیست. در این نسخه درآمد مالی و وضعیت تأیید پرداخت‌ها هنوز در این گزارش محاسبه نمی‌شود تا از نسبت‌دادن مبالغ به ساختمان اشتباه جلوگیری شود.</p>
</div>
@endsection
