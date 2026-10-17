@php
  $F = \App\Support\Fa::class;
  $st = ['pending' => ['در انتظار تأیید', 'h'], 'approved' => ['تأییدشده', ''], 'rejected' => ['ردشده', 'u']];
@endphp
@extends('layouts.app')
@section('title', 'ثبت پرداخت')
@section('content')
<div class="card">
  <div class="page-h"><h2>ثبت فیش پرداخت</h2></div>
  @if($units->isEmpty())
    <div class="empty">واحدی به نام شما ثبت نشده است؛ پس از ثبت مالک یا مستأجر بودن شما توسط مدیر، می‌توانید پرداخت ثبت کنید.</div>
  @else
  <form method="POST" action="{{ route('my.payments.store') }}" enctype="multipart/form-data" style="margin-top:10px">@csrf
    <div class="fgrid">
      <div class="fld"><label for="unit_id">واحد *</label>
        <select id="unit_id" name="unit_id">@foreach($units as $u)<option value="{{ $u->id }}" @selected((int) old('unit_id') === $u->id)>{{ $u->block->name }} / {{ $F::digits($u->number) }}@if(($debts[$u->id] ?? 0) > 0) — بدهی {{ $F::num($debts[$u->id]) }} تومان @endif</option>@endforeach</select>
        @error('unit_id')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="amount">مبلغ پرداختی (تومان) *</label><input id="amount" name="amount" inputmode="numeric" value="{{ old('amount') }}" required>@error('amount')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="paid_at">تاریخ پرداخت (شمسی) *</label><input id="paid_at" name="paid_at" class="jdate" autocomplete="off" placeholder="انتخاب از تقویم" value="{{ old('paid_at', \App\Support\JalaliDate::format(today())) }}" required>@error('paid_at')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="receipt">تصویر فیش *</label><input id="receipt" type="file" name="receipt" accept="image/*,application/pdf" required><div class="h">jpg، png، webp یا pdf — حداکثر ۵ مگابایت</div>@error('receipt')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld full"><label for="note">توضیحات</label><input id="note" name="note" value="{{ old('note') }}" maxlength="500"></div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ثبت فیش</button></div>
  </form>
  @endif
</div>

<div class="card">
  <div class="page-h"><h2>فیش‌های من</h2></div>
  @if($receipts->isEmpty())<div class="empty">هنوز فیشی ثبت نکرده‌اید.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>تاریخ پرداخت</th><th>واحد</th><th>مبلغ</th><th>وضعیت</th></tr></thead>
    <tbody>
    @foreach($receipts as $r)
      <tr><td>{{ $F::date($r->paid_at, 'Y/m/d') }}</td><td>{{ $r->unit->block->name }} / {{ $F::digits($r->unit->number) }}</td><td><b>{{ $F::money($r->amount) }}</b></td>
        <td><span class="pill {{ $st[$r->status->value][1] }}">{{ $st[$r->status->value][0] }}</span>@if($r->reject_reason)<br><span style="color:var(--muted);font-size:12px">{{ $r->reject_reason }}</span>@endif</td></tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $receipts->links() }}
  @endif
</div>
@endsection
