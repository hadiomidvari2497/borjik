@php
  $F = \App\Support\Fa::class;
  $type = ['charge' => 'شارژ', 'rent' => 'اجاره', 'penalty' => 'جریمه'];
  $status = ['unpaid' => ['پرداخت‌نشده', 'h'], 'partial' => ['پرداخت جزئی', 'h'], 'paid' => ['پرداخت‌شده', ''], 'void' => ['باطل', 'u']];
  $basis = ['equal' => 'تقسیم مساوی', 'area' => 'بر اساس متراژ', 'people' => 'بر اساس تعداد ساکنان', 'residents' => 'بر اساس تعداد ساکنان'];
  $remaining = max(0, (int) $invoice->amount - (int) $invoice->paid_amount);
  $unitName = $invoice->unit->block->name.' / '.$F::digits($invoice->unit->number);
@endphp
@extends('layouts.app')
@section('title', 'جزئیات صورت‌حساب '.$invoice->number)
@section('content')
<style>
.invoice-detail{display:grid;gap:14px}.invoice-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:10px}.invoice-stat{padding:14px;border:1px solid var(--line);border-radius:13px;min-width:0}.invoice-stat small{display:block;color:var(--muted);font-size:11px}.invoice-stat strong{display:block;margin-top:7px;font-size:17px;overflow-wrap:anywhere}.invoice-section{padding:17px}.invoice-section h3{font-size:14px;margin:0 0 12px}.invoice-note{padding:12px;border-radius:11px;background:var(--brand-soft);font-size:11px;line-height:1.9;color:var(--text)}.invoice-list{display:grid;gap:0}.invoice-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--line);font-size:12px}.invoice-row:last-child{border-bottom:0}.invoice-row span{color:var(--muted)}.invoice-actions{display:flex;gap:8px;flex-wrap:wrap}
@media(max-width:560px){.invoice-section{padding:13px}.invoice-row{gap:8px}.invoice-stat strong{font-size:15px}}
</style>
<div class="invoice-detail">
  <section class="card invoice-section">
    <div class="page-h" style="gap:10px;flex-wrap:wrap">
      <div><a class="link" href="{{ route('my.invoices') }}">← بازگشت به صورت‌حساب‌ها</a><h2 style="margin:8px 0 0">صورت‌حساب {{ $type[$invoice->type->value] ?? 'سایر' }}</h2><small style="color:var(--muted)">{{ $invoice->number }}</small></div>
      <span class="pill {{ $status[$invoice->status->value][1] ?? '' }}">{{ $status[$invoice->status->value][0] ?? 'نامشخص' }}</span>
    </div>
    <div class="invoice-summary">
      <div class="invoice-stat"><small>مبلغ کل</small><strong>{{ $F::money($invoice->amount) }}</strong></div>
      <div class="invoice-stat"><small>پرداخت‌شده</small><strong>{{ $F::money($invoice->paid_amount) }}</strong></div>
      <div class="invoice-stat"><small>مانده قابل پرداخت</small><strong style="color:{{ $remaining ? '#d97706' : '#238779' }}">{{ $F::money($remaining) }}</strong></div>
      <div class="invoice-stat"><small>سررسید</small><strong>{{ $F::date($invoice->due_date, 'Y/m/d') }}</strong></div>
    </div>
    <div class="invoice-list" style="margin-top:12px">
      <div class="invoice-row"><span>واحد</span><b>{{ $unitName }}</b></div>
      <div class="invoice-row"><span>تاریخ صدور</span><b>{{ $F::date($invoice->issued_at, 'Y/m/d') }}</b></div>
      @if($invoice->period)<div class="invoice-row"><span>دوره شارژ</span><b>{{ $F::MONTHS[$invoice->period->jalali_month - 1] }} {{ $F::digits($invoice->period->jalali_year) }}</b></div>@endif
      @if($method)<div class="invoice-row"><span>روش محاسبه شارژ</span><b>{{ $method }}</b></div>@endif
    </div>
    @if($remaining > 0 && $invoice->status->value !== 'void' && \Illuminate\Support\Facades\Route::has('my.payments'))
      <div class="invoice-actions" style="margin-top:14px">
        <a class="btn" href="{{ route('my.payments', ['unit_id' => $invoice->unit_id]) }}">ثبت فیش پرداخت این واحد</a>
      </div>
    @endif
  </section>

  @if($invoice->type->value === 'charge' && $expenseLines !== [])
    <section class="card invoice-section">
      <h3>هزینه‌های ثبت‌شده برای دوره</h3>
      <div class="invoice-note">مبالغ زیر هزینه‌های کل ساختمان در این دوره هستند، نه سهم مستقل واحد شما. مبلغ نهایی این صورت‌حساب با روش محاسبه ثبت‌شده و اطلاعات واحد تعیین شده است.</div>
      <div class="invoice-list" style="margin-top:8px">
        @foreach($expenseLines as $line)
          <div class="invoice-row">
            <div><b>{{ $line['title'] ?? ($line['category_name'] ?? 'هزینه ساختمان') }}</b><br><span>{{ $line['category_name'] ?? 'هزینه مشترک' }} · {{ $basis[$line['split_basis'] ?? 'equal'] ?? 'طبق مبنای ثبت‌شده' }}</span></div>
            <b>{{ $F::money((int) ($line['amount'] ?? 0)) }}</b>
          </div>
        @endforeach
      </div>
      <div class="invoice-row"><span>جمع هزینه‌های ثبت‌شده ساختمان</span><b>{{ $F::money(array_sum(array_map(fn($line) => (int) ($line['amount'] ?? 0), $expenseLines))) }}</b></div>
    </section>
  @elseif($invoice->type->value === 'charge')
    <section class="card invoice-section"><h3>جزئیات محاسبه</h3><p style="color:var(--muted);font-size:12px;line-height:1.9;margin:0">اطلاعات تفصیلی هزینه‌های این دوره در نسخه‌ی ثبت‌شده‌ی محاسبه موجود نیست؛ مبلغ نهایی و پرداخت‌های تخصیص‌یافته در بالا نمایش داده شده‌اند.</p></section>
  @endif

  <section class="card invoice-section">
    <h3>پرداخت‌های تخصیص‌یافته به این صورت‌حساب</h3>
    @forelse($invoice->allocations as $allocation)
      @if($allocation->receipt)
        <div class="invoice-row">
          <div><b>فیش شماره {{ $F::digits($allocation->receipt->id) }}</b><br><span>تاریخ پرداخت {{ $F::date($allocation->receipt->paid_at, 'Y/m/d') }} · {{ ['pending' => 'در انتظار بررسی', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'][$allocation->receipt->status->value] ?? 'نامشخص' }}</span></div>
          <b>{{ $F::money($allocation->amount) }}</b>
        </div>
      @endif
    @empty
      <div class="empty">هنوز پرداختی به این صورت‌حساب تخصیص داده نشده است.</div>
    @endforelse
    <div class="invoice-note" style="margin-top:12px">فقط پرداخت‌های تأییدشده در مانده بدهی این صورت‌حساب محاسبه می‌شوند. فیش‌های در انتظار بررسی پس از تأیید مدیر به صورت‌حساب‌ها تخصیص پیدا می‌کنند.</div>
  </section>
</div>
@endsection
