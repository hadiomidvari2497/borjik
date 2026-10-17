@php
  $F = \App\Support\Fa::class;
  $rent = $contract->type->value === 'rent';
  $active = $contract->status->value === 'active';
  $payerLabel = ['tenant' => 'مستأجر', 'owner' => 'مالک واحد'];
  $inv = ['unpaid' => ['پرداخت‌نشده', 'u'], 'partial' => ['پرداخت جزئی', 'h'], 'paid' => ['پرداخت‌شده', ''], 'void' => ['باطل', 'h']];
  $invType = ['charge' => 'شارژ', 'rent' => 'اجاره', 'penalty' => 'جریمه'];
@endphp
@extends('layouts.app')
@section('title', 'قرارداد '.($rent ? 'اجاره' : 'فروش'))
@section('content')
<div class="card">
  <div class="page-h">
    <h2>قرارداد {{ $rent ? 'اجاره' : 'فروش' }} شماره‌ی {{ $F::digits($contract->id) }} <span class="pill {{ $active ? '' : 'h' }}">{{ $contract->status->label() }}</span></h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn sec sm" href="{{ route('contracts.index') }}">بازگشت به فهرست</a>
      @if($contract->isActiveRent())
        <form method="POST" action="{{ route('contracts.end', $contract->id) }}" data-confirm="مستأجر از ساکنین واحد حذف می‌شود و واحد از اجاره خارج می‌شود." data-confirm-title="قرارداد اجاره پایان یابد؟" data-confirm-icon="warning">@csrf<button class="btn dng sm" type="submit">پایان قرارداد</button></form>
      @endif
    </div>
  </div>

  <div style="margin-top:8px">
    <div class="kv"><span>واحد</span><b>{{ $contract->unit->block->name }} / {{ $F::digits($contract->unit->number) }}</b></div>
    <div class="kv"><span>{{ $rent ? 'مستأجر' : 'خریدار' }}</span><b>{{ $contract->user->display_name }}@if($contract->user->mobile) <span style="color:var(--muted);font-weight:400">({{ $F::digits($contract->user->mobile) }})</span>@endif</b></div>
    @if($rent)
      <div class="kv"><span>مالک فعلی واحد</span><b>{{ $owner?->display_name ?? '—' }}</b></div>
      <div class="kv"><span>مبلغ پیش‌پرداخت</span><b>{{ $F::money($contract->deposit_amount) }}</b></div>
      <div class="kv"><span>اجاره‌ی ماهانه</span><b>{{ $F::money($contract->monthly_rent) }}</b></div>
      <div class="kv"><span>تاریخ عقد</span><b>{{ $F::date($contract->signed_at, 'Y/m/d') }}</b></div>
      <div class="kv"><span>تاریخ پایان قرارداد</span><b>{{ $contract->end_date ? $F::date($contract->end_date, 'Y/m/d') : '—' }}</b></div>
      @if($contract->ended_at)<div class="kv"><span>تاریخ خاتمه‌ی واقعی</span><b>{{ $F::date($contract->ended_at, 'Y/m/d') }}</b></div>@endif
      <div class="kv"><span>شارژ بر عهده‌ی</span><b>{{ $payerLabel[$contract->charge_payer->value] }}</b></div>
    @else
      <div class="kv"><span>مبلغ فروش</span><b>{{ $F::money($contract->sale_price) }}</b></div>
      <div class="kv"><span>تاریخ فروش</span><b>{{ $F::date($contract->signed_at, 'Y/m/d') }}</b></div>
      @if($contract->ended_at)<div class="kv"><span>مالکیت تا</span><b>{{ $F::date($contract->ended_at, 'Y/m/d') }} (به خریدار بعدی منتقل شد)</b></div>@endif
    @endif
    <div class="kv"><span>ثبت‌کننده</span><b>{{ $contract->createdBy?->display_name ?? '—' }}</b></div>
  </div>
</div>

@if($contract->isActiveRent())
<div class="card">
  <div class="page-h"><h2>مسئول پرداخت شارژ</h2></div>
  <form method="POST" action="{{ route('contracts.payer', $contract->id) }}" style="margin-top:10px">@csrf @method('PUT')
    <div class="fgrid">
      <div class="fld"><label for="charge_payer">شارژ بر عهده‌ی</label>
        <select id="charge_payer" name="charge_payer"><option value="tenant" @selected($contract->charge_payer->value === 'tenant')>مستأجر</option><option value="owner" @selected($contract->charge_payer->value === 'owner')>مالک واحد</option></select>
        <div class="h">تغییر از دوره‌ی محاسبه‌ی بعدی شارژ اعمال می‌شود.</div>@error('charge_payer')<div class="e">{{ $message }}</div>@enderror</div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ذخیره</button></div>
  </form>
</div>
@endif

<div class="card">
  <div class="page-h"><h2>فایل قرارداد</h2></div>
  @if($file)
    <div class="kv"><span>فایل فعلی</span><a class="btn sec sm" href="{{ route('files.show', $file->id) }}" target="_blank" rel="noopener">{{ $file->original_name }} — مشاهده</a></div>
  @else
    <div class="empty">فایلی برای این قرارداد پیوست نشده است.</div>
  @endif
  <form method="POST" action="{{ route('contracts.file', $contract->id) }}" enctype="multipart/form-data" style="margin-top:10px">@csrf
    <div class="fgrid">
      <div class="fld full"><label for="contract_file">{{ $file ? 'جایگزینی فایل' : 'بارگذاری فایل' }}</label><input id="contract_file" type="file" name="contract_file" accept="image/*,application/pdf" required><div class="h">pdf یا تصویر — حداکثر ۱۰ مگابایت</div>@error('contract_file')<div class="e">{{ $message }}</div>@enderror</div>
    </div>
    <div class="fbar"><button class="btn sec" type="submit">ذخیره‌ی فایل</button></div>
  </form>
</div>

@if($invoices->isNotEmpty())
<div class="card">
  <div class="page-h"><h2>صورت‌حساب‌های این قرارداد</h2></div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>شماره</th><th>نوع</th><th>مبلغ</th><th>مهلت پرداخت</th><th>وضعیت</th></tr></thead>
    <tbody>
    @foreach($invoices as $i)
      <tr><td>{{ $i->number }}</td><td>{{ $invType[$i->type->value] ?? $i->type->value }}</td><td><b>{{ $F::money($i->amount) }}</b></td><td>{{ $F::date($i->due_date, 'Y/m/d') }}</td>
        <td><span class="pill {{ $inv[$i->status->value][1] ?? '' }}">{{ $inv[$i->status->value][0] ?? $i->status->value }}</span></td></tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@endif
@include('partials.history', ['subject' => $contract])
@endsection
