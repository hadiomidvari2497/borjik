@extends('layouts.app')
@section('title','خزانه‌داری و گردش مالی')
@section('content')
@php
 $monthNames = \App\Support\Fa::MONTHS;
 $money = fn($n) => $F::digits(number_format((int)$n));
@endphp
<style>
.tr-hero{padding:20px;border-radius:16px;background:linear-gradient(120deg,#194f49,#287e6d);color:#fff;margin-bottom:16px}.tr-hero h2{margin:0 0 6px}.tr-hero p{margin:0;color:#d8f1e9;line-height:1.9}.tr-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:14px 0}.tr-stat,.tr-card{border:1px solid var(--line);border-radius:14px;padding:15px;background:var(--surface);min-width:0}.tr-stat span,.tr-muted{font-size:12px;color:var(--muted)}.tr-stat strong{display:block;font-size:21px;margin:8px 0;overflow-wrap:anywhere}.tr-layout{display:grid;grid-template-columns:minmax(260px,340px) minmax(0,1fr);gap:14px}.tr-card h3{font-size:15px;margin:0 0 13px}.tr-form label{display:block;font-size:12px;color:var(--muted);margin:10px 0 5px}.tr-form input,.tr-form select,.tr-form textarea{width:100%;min-width:0}.tr-btn{border:0;border-radius:9px;padding:9px 13px;background:#176c5d;color:#fff;font:inherit;font-size:12px;font-weight:800;cursor:pointer}.tr-filter{display:flex;align-items:end;gap:8px;flex-wrap:wrap;margin-bottom:14px}.tr-filter label{display:block;font-size:12px;color:var(--muted);margin-bottom:5px}.tr-filter select{min-width:100px}.tr-tablewrap{overflow:auto}.tr-table{width:100%;border-collapse:collapse;min-width:650px}.tr-table th,.tr-table td{padding:10px;border-bottom:1px solid var(--line);text-align:right;vertical-align:top}.tr-table th{font-size:12px;color:var(--muted)}.tr-tag{font-size:11px;padding:4px 8px;border-radius:20px;display:inline-block}.tr-in{background:#e4f7ed;color:#17683a}.tr-out{background:#fff0ef;color:#a52b24}.tr-note{padding:10px;border-radius:10px;background:var(--surface);color:var(--muted);font-size:12px;line-height:1.9;margin-bottom:14px}
@media(max-width:950px){.tr-layout{grid-template-columns:1fr}.tr-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:480px){.tr-stat{padding:11px}.tr-stat strong{font-size:17px}}
</style>
<div class="tr-hero"><h2>خزانه‌داری ساختمان</h2><p>درآمدها و هزینه‌های نقدی را با دسته‌بندی، روش پرداخت و شماره پیگیری ثبت کن و گزارش ماهانه بگیر.</p></div>
<div class="tr-note">دفتر خزانه، تراکنش‌های دستی ثبت‌شده در این بخش را جمع می‌زند. بدهی صورت‌حساب‌ها و فیش‌های تأییدشده در شاخص‌های جداگانه نمایش داده می‌شوند تا با تراکنش‌های دستی دوباره‌شماری نشوند.</div>
<form method="GET" action="{{ route('treasury.index') }}" class="tr-filter"><div><label for="tr-year">سال شمسی</label><input id="tr-year" name="year" type="number" min="1380" max="1500" value="{{ $year }}" required></div><div><label for="tr-month">ماه</label><select id="tr-month" name="month">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month===$m)>{{ $monthNames[$m-1] }}</option>@endfor</select></div><button class="tr-btn" type="submit">نمایش گزارش</button></form>
<div class="tr-stats">
 <div class="tr-stat"><span>درآمد ثبت‌شده در دفتر</span><strong>{{ $money($income) }}</strong><span>ریال · ماه انتخابی</span></div>
 <div class="tr-stat"><span>هزینه ثبت‌شده در دفتر</span><strong>{{ $money($expense) }}</strong><span>ریال · ماه انتخابی</span></div>
 <div class="tr-stat"><span>خالص گردش دفتر</span><strong>{{ $money($net) }}</strong><span>درآمد منهای هزینه</span></div>
 <div class="tr-stat"><span>بدهی باز واحدها</span><strong>{{ $money($debt) }}</strong><span>از صورت‌حساب‌های پرداخت‌نشده</span></div>
 <div class="tr-stat"><span>بدهی سررسیدگذشته</span><strong>{{ $money($overdueDebt) }}</strong><span>ریال</span></div>
 <div class="tr-stat"><span>فیش‌های تأییدشده ماه</span><strong>{{ $money($approvedReceipts) }}</strong><span>ریال · جدا از دفتر دستی</span></div>
</div>
<div class="tr-layout">
 <section class="tr-card"><h3>ثبت تراکنش جدید</h3><form method="POST" action="{{ route('treasury.store') }}" class="tr-form">@csrf
  <label for="tr-type">نوع تراکنش *</label><select id="tr-type" name="type" required><option value="income">درآمد / دریافت</option><option value="expense">هزینه / پرداخت</option></select>
  <label for="tr-category">دسته‌بندی *</label><input id="tr-category" name="category" required maxlength="100" placeholder="مثلاً تعمیرات، نظافت، اجاره فضا">
  <label for="tr-title">شرح تراکنش *</label><input id="tr-title" name="title" required maxlength="180" placeholder="شرح کوتاه و قابل پیگیری">
  <label for="tr-amount">مبلغ (ریال) *</label><input id="tr-amount" name="amount" type="number" min="1" max="99999999999" required>
  <label for="tr-date">تاریخ (شمسی) *</label><input id="tr-date" name="transaction_date" class="jdate" autocomplete="off" required value="{{ \Morilog\Jalali\Jalalian::now()->format('Y/m/d') }}">
  <label for="tr-method">روش پرداخت *</label><select id="tr-method" name="payment_method" required><option value="transfer">کارت‌به‌کارت / انتقال</option><option value="cash">نقدی</option><option value="card">کارت‌خوان</option><option value="cheque">چک</option><option value="other">سایر</option></select>
  <label for="tr-reference">شماره پیگیری / مرجع</label><input id="tr-reference" name="reference" maxlength="120">
  <label for="tr-notes">یادداشت</label><textarea id="tr-notes" name="notes" rows="3" maxlength="5000"></textarea>
  <button class="tr-btn" type="submit" style="width:100%;margin-top:14px">ثبت در دفتر خزانه</button>
 </form></section>
 <section class="tr-card"><h3>گردش مالی {{ $monthNames[$month-1] }} {{ $F::digits($year) }}</h3>
  @if($transactions->isEmpty())<div class="tr-muted" style="padding:20px 0">برای این ماه تراکنشی در دفتر خزانه ثبت نشده است.</div>@else
  <div class="tr-tablewrap"><table class="tr-table"><thead><tr><th>تاریخ</th><th>نوع</th><th>شرح و دسته‌بندی</th><th>مبلغ (ریال)</th><th>روش / مرجع</th><th>عملیات</th></tr></thead><tbody>
  @foreach($transactions as $transaction)<tr><td>{{ $F::date($transaction->transaction_date,'j F Y') }}</td><td><span class="tr-tag {{ $transaction->type==='income'?'tr-in':'tr-out' }}">{{ $transaction->type==='income'?'درآمد':'هزینه' }}</span></td><td><strong>{{ $transaction->title }}</strong><div class="tr-muted">{{ $transaction->category }}</div>@if($transaction->notes)<div class="tr-muted">{{ $transaction->notes }}</div>@endif</td><td><strong>{{ $money($transaction->amount) }}</strong></td><td>{{ ['cash'=>'نقدی','transfer'=>'انتقال','card'=>'کارت‌خوان','cheque'=>'چک','other'=>'سایر'][$transaction->payment_method] ?? $transaction->payment_method }}<div class="tr-muted">{{ $transaction->reference ?: '—' }}</div></td><td><form method="POST" action="{{ route('treasury.destroy',$transaction) }}" data-confirm="این تراکنش از دفتر خزانه حذف شود؟" data-confirm-title="حذف تراکنش" data-confirm-icon="warning">@csrf @method('DELETE')<button class="tr-btn" type="submit" style="background:#a52b24">حذف</button></form></td></tr>@endforeach
  </tbody></table></div><div style="margin-top:12px">{{ $transactions->links() }}</div>@endif
 </section>
</div>
@endsection