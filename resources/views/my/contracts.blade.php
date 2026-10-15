@php
  $F = \App\Support\Fa::class;
  $payerLabel = ['tenant' => 'مستأجر', 'owner' => 'مالک واحد'];
@endphp
@extends('layouts.app')
@section('title', 'قرارداد من')
@section('content')
<div class="card">
  <div class="page-h"><h2>قرارداد من</h2></div>
  @if($contracts->isEmpty())
    <div class="empty">قراردادی برای شما ثبت نشده است. پس از ثبت قرارداد توسط مدیر ساختمان، اینجا نمایش داده می‌شود.</div>
  @endif
</div>

@foreach($contracts as $ct)
  @php $rent = $ct->type->value === 'rent'; $mine = (int) $ct->user_id === (int) $uid; $active = $ct->status->value === 'active'; @endphp
  <div class="card">
    <div class="page-h">
      <h2>{{ $rent ? 'قرارداد اجاره' : 'قرارداد خرید' }} — {{ $ct->unit->block->name }} / {{ $F::digits($ct->unit->number) }}
        <span class="pill {{ $active ? '' : 'h' }}">{{ $ct->status->label() }}</span></h2>
      @if($files->has($ct->id))<a class="btn sec sm" href="{{ route('files.show', $files[$ct->id]) }}" target="_blank" rel="noopener">فایل قرارداد</a>@endif
    </div>
    <div style="margin-top:6px">
      @if($rent)
        @unless($mine)<div class="kv"><span>مستأجر</span><b>{{ $ct->user->display_name }}</b></div>@endunless
        <div class="kv"><span>اجاره‌ی ماهانه</span><b>{{ $F::money($ct->monthly_rent) }}</b></div>
        <div class="kv"><span>مبلغ پیش‌پرداخت</span><b>{{ $F::money($ct->deposit_amount) }}</b></div>
        <div class="kv"><span>تاریخ عقد</span><b>{{ $F::date($ct->signed_at, 'Y/m/d') }}</b></div>
        <div class="kv"><span>تاریخ پایان قرارداد</span><b>{{ $ct->end_date ? $F::date($ct->end_date, 'Y/m/d') : '—' }}</b></div>
        <div class="kv"><span>شارژ بر عهده‌ی</span><b>{{ $payerLabel[$ct->charge_payer->value] }}</b></div>
      @else
        <div class="kv"><span>مبلغ</span><b>{{ $F::money($ct->sale_price) }}</b></div>
        <div class="kv"><span>تاریخ فروش</span><b>{{ $F::date($ct->signed_at, 'Y/m/d') }}</b></div>
      @endif
    </div>
  </div>
@endforeach
@endsection
