@php
  $F = \App\Support\Fa::class;
  $type = ['charge' => 'شارژ', 'rent' => 'اجاره', 'penalty' => 'جریمه'];
  $st = ['unpaid' => ['پرداخت‌نشده', 'h'], 'partial' => ['پرداخت جزئی', 'h'], 'paid' => ['پرداخت‌شده', ''], 'void' => ['باطل', 'u']];
@endphp
@extends('layouts.app')
@section('title', 'صورت‌حساب‌های من')
@section('content')
<div class="card">
  <div class="page-h"><h2>صورت‌حساب‌های من</h2>
    @can('payment.submit')<a class="btn" href="{{ route('my.payments') }}">ثبت پرداخت</a>@endcan</div>
  <div class="kv"><span>مانده‌ی بدهی</span><b style="color:{{ $debt ? '#e0453a' : 'inherit' }}">{{ $debt ? $F::money($debt) : 'تسویه‌شده' }}</b></div>

  @if($invoices->isEmpty())<div class="empty">صورت‌حسابی برای شما صادر نشده است.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>شماره</th><th>نوع</th><th>دوره</th><th>واحد</th><th>سررسید</th><th>مبلغ</th><th>پرداخت‌شده</th><th>وضعیت</th></tr></thead>
    <tbody>
    @foreach($invoices as $i)
      @php $overdue = in_array($i->status->value, ['unpaid', 'partial'], true) && $i->due_date->isPast(); @endphp
      <tr>
        <td dir="ltr" style="text-align:end">{{ $i->number }}</td>
        <td>{{ $type[$i->type->value] }}</td>
        <td>{{ $i->period ? \App\Support\Fa::MONTHS[$i->period->jalali_month - 1].' '.$F::digits($i->period->jalali_year) : '—' }}</td>
        <td>{{ $i->unit->block->name }} / {{ $F::digits($i->unit->number) }}</td>
        <td>{{ $F::date($i->due_date, 'Y/m/d') }}</td>
        <td><b>{{ $F::money($i->amount) }}</b></td><td>{{ $F::money($i->paid_amount) }}</td>
        <td><span class="pill {{ $st[$i->status->value][1] }}">{{ $st[$i->status->value][0] }}</span> @if($overdue)<span class="pill u">معوق</span>@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $invoices->links() }}
  @endif
</div>
@endsection
