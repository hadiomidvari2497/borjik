@extends('admin.layout')
@section('title', 'پرونده مشتری')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $membershipLabels = ['active' => 'عضو فعال', 'invited' => 'دعوت‌شده', 'inactive' => 'غیرفعال', 'rejected' => 'دعوت رد شده'];
  $paymentLabels = ['paid' => 'موفق', 'pending' => 'در انتظار', 'failed' => 'ناموفق'];
@endphp
<div class="card">
  <div class="page-h" style="align-items:flex-start">
    <div><h2>پرونده مشتری</h2><p style="margin:6px 0 0;color:var(--muted);font-size:13px">نمای یکپارچه حساب، عضویت‌ها، ساختمان‌ها و سوابق مالی مجاز</p></div>
    <a class="btn sec sm" href="{{ route('admin.building-users.index') }}">بازگشت به کاربران</a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:16px 0">
    <div class="card" style="padding:14px"><small style="color:var(--muted)">نام مشتری</small><strong style="display:block;margin-top:7px">{{ $customer->display_name ?: 'بدون نام' }}</strong></div>
    <div class="card" style="padding:14px"><small style="color:var(--muted)">شماره همراه</small><strong dir="ltr" style="display:block;text-align:right;margin-top:7px">{{ $customer->mobile }}</strong></div>
    <div class="card" style="padding:14px"><small style="color:var(--muted)">وضعیت حساب</small><strong style="display:block;margin-top:7px">{{ $customer->status === 'active' ? 'فعال' : 'غیرفعال' }}</strong></div>
    <div class="card" style="padding:14px"><small style="color:var(--muted)">تأیید شماره</small><strong style="display:block;margin-top:7px">{{ $customer->mobile_verified_at ? 'تأییدشده' : 'تأییدنشده' }}</strong></div>
    @if($canViewFinance)
      <div class="card" style="padding:14px"><small style="color:var(--muted)">مجموع پرداخت موفق اشتراک</small><strong style="display:block;margin-top:7px">{{ $F::money($paidTotal) }} تومان</strong></div>
    @endif
  </div>
  <section style="margin-top:22px">
    <h3 style="font-size:15px;margin-bottom:10px">ساختمان‌ها و نقش‌ها</h3>
    @forelse($customer->buildingRoles as $membership)
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px;border:1px solid var(--line);border-radius:12px;margin-bottom:8px">
        <div><strong>{{ $membership->building?->name ?: 'ساختمان حذف‌شده' }}</strong><small style="display:block;color:var(--muted);margin-top:4px">کد: {{ $membership->building?->code ?: '—' }} · {{ $membership->role?->name_fa ?: 'بدون نقش' }}</small></div>
        <span class="pill">{{ $membershipLabels[$membership->status?->value ?? $membership->status] ?? 'نامشخص' }}</span>
      </div>
    @empty<div class="empty">برای این مشتری عضویتی ثبت نشده است.</div>@endforelse
  </section>
  @if($canViewFinance)
    <section style="margin-top:24px">
      <h3 style="font-size:15px;margin-bottom:10px">اشتراک‌ها</h3>
      <div class="tbl-wrap"><table class="tbl"><thead><tr><th>ساختمان</th><th>پلن</th><th>سقف واحد</th><th>وضعیت</th><th>شروع</th><th>پایان</th></tr></thead><tbody>
      @forelse($subscriptions as $subscription)
        <tr><td>{{ $subscription->building?->name ?: '—' }}</td><td>{{ $subscription->plan?->name ?: '—' }}</td><td>{{ $F::num($subscription->units_limit) }}</td><td>{{ $subscription->status === 'active' && $subscription->expires_at?->isFuture() ? 'فعال' : ($subscription->status === 'expired' ? 'منقضی' : $subscription->status) }}</td><td>{{ $subscription->starts_at ? $F::date($subscription->starts_at, 'j F Y') : '—' }}</td><td>{{ $subscription->expires_at ? $F::date($subscription->expires_at, 'j F Y') : '—' }}</td></tr>
      @empty<tr><td colspan="6">سابقه اشتراکی وجود ندارد.</td></tr>@endforelse
      </tbody></table></div>
    </section>
    <section style="margin-top:24px">
      <h3 style="font-size:15px;margin-bottom:10px">پرداخت‌های اشتراک (۵۰ مورد اخیر)</h3>
      <div class="tbl-wrap"><table class="tbl"><thead><tr><th>شناسه</th><th>مبلغ</th><th>درگاه</th><th>وضعیت</th><th>شناسه پیگیری</th><th>زمان پرداخت</th></tr></thead><tbody>
      @forelse($payments as $payment)
        <tr><td>{{ $F::digits($payment->id) }}</td><td>{{ $F::money($payment->amount) }} تومان</td><td>{{ $payment->gateway }}</td><td>{{ $paymentLabels[$payment->status] ?? $payment->status }}</td><td>{{ $payment->ref_id ?: '—' }}</td><td>{{ $payment->paid_at ? $F::date($payment->paid_at, 'j F Y - H:i') : '—' }}</td></tr>
      @empty<tr><td colspan="6">پرداختی ثبت نشده است.</td></tr>@endforelse
      </tbody></table></div>
      <p style="color:var(--muted);font-size:11px;margin-top:8px">اطلاعات مالی فقط برای مدیرانی نمایش داده می‌شود که مجوز «مدیریت اشتراک کاربران» دارند.</p>
    </section>
  @else
    <p style="color:var(--muted);font-size:12px;margin-top:20px">برای مشاهده سوابق مالی این مشتری، مجوز مدیریت اشتراک کاربران لازم است.</p>
  @endif
</div>
@endsection
