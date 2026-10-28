@php $F = \App\Support\Fa::class; @endphp
@extends('layouts.app')
@section('title', 'فاکتور و رسید پرداخت')
@section('content')
<style>
.receipt-wrap{max-width:920px;margin:0 auto;color:var(--text)}.receipt-card{background:var(--card);border:1px solid var(--line);border-radius:22px;overflow:hidden;box-shadow:0 12px 36px rgba(25,60,55,.06)}.receipt-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:26px;background:linear-gradient(135deg,var(--brand-soft),transparent);border-bottom:1px solid var(--line)}.receipt-head h2{margin:0 0 6px;font-size:23px;font-weight:900}.receipt-head p{margin:0;color:var(--muted);font-size:12px}.receipt-status{padding:8px 12px;border-radius:999px;background:rgba(35,145,103,.12);color:#23845f;font-weight:900;font-size:12px;white-space:nowrap}.receipt-body{padding:24px}.receipt-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.receipt-item{padding:14px;border:1px solid var(--line);border-radius:14px;min-width:0}.receipt-item span{display:block;color:var(--muted);font-size:11px;margin-bottom:6px}.receipt-item strong{display:block;font-size:13px;overflow-wrap:anywhere}.receipt-total{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:18px;padding:18px;border-radius:16px;background:var(--brand-soft)}.receipt-total span{font-size:13px;font-weight:800}.receipt-total strong{font-size:25px;font-weight:950}.receipt-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.receipt-actions a,.receipt-actions button{display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border:0;border-radius:12px;background:var(--brand);color:#fff;text-decoration:none;font:inherit;font-size:12px;font-weight:850;cursor:pointer}.receipt-actions .secondary{background:var(--panel);color:var(--text);border:1px solid var(--line)}@media(max-width:600px){.receipt-head{padding:19px;flex-direction:column}.receipt-body{padding:16px}.receipt-grid{grid-template-columns:1fr}.receipt-total strong{font-size:21px}}@media print{body{background:#fff!important}.receipt-wrap{max-width:none}.receipt-card{box-shadow:none;border:1px solid #ddd}.receipt-actions{display:none!important}.receipt-head{background:#fff!important}.receipt-item,.receipt-total{break-inside:avoid}}
</style>
<div class="receipt-wrap">
  <div class="receipt-card">
    <div class="receipt-head">
      <div><h2>فاکتور و رسید پرداخت صابخونه</h2><p>این رسید پس از تأیید پرداخت از سمت درگاه صادر شده است.</p></div>
      <span class="receipt-status">پرداخت موفق</span>
    </div>
    <div class="receipt-body">
      <div class="receipt-grid">
        <div class="receipt-item"><span>شماره فاکتور</span><strong dir="ltr">{{ $invoiceNumber }}</strong></div>
        <div class="receipt-item"><span>شناسه پرداخت</span><strong dir="ltr">#{{ $payment->id }}</strong></div>
        <div class="receipt-item"><span>پرداخت‌کننده</span><strong>{{ $payment->user?->name ?? 'کاربر صابخونه' }}</strong></div>
        <div class="receipt-item"><span>پلن خریداری‌شده</span><strong>{{ $payment->subscription?->plan?->name ?? 'اشتراک صابخونه' }}</strong></div>
        <div class="receipt-item"><span>درگاه پرداخت</span><strong>{{ data_get($payment->raw_response, 'gateway_name', $payment->gateway === 'bitpay' ? 'بیت‌پی' : ($payment->gateway === 'zarinpal' ? 'زرین‌پال' : $payment->gateway)) }}</strong></div>
        <div class="receipt-item"><span>شماره پیگیری تراکنش</span><strong dir="ltr">{{ $payment->ref_id ?: '—' }}</strong></div>
        <div class="receipt-item"><span>تاریخ پرداخت</span><strong>{{ $payment->paid_at ? $F::date($payment->paid_at, 'Y/m/d H:i') : '—' }}</strong></div>
        <div class="receipt-item"><span>وضعیت</span><strong>تأییدشده توسط درگاه</strong></div>
      </div>
      <div class="receipt-total"><span>مبلغ پرداخت‌شده</span><strong>{{ $F::money($payment->amount) }} تومان</strong></div>
      <div class="receipt-actions"><button type="button" onclick="window.print()">چاپ / ذخیره PDF</button><a class="secondary" href="{{ route('subscription.show') }}">بازگشت به اشتراک</a></div>
    </div>
  </div>
</div>
@endsection
