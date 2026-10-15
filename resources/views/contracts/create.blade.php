@php $F = \App\Support\Fa::class; $rent = $type === 'rent'; $stLabel = ['sold' => 'فروخته‌شده', 'rented' => 'اجاره‌داده‌شده', 'vacant' => 'خالی', 'repair' => 'در حال تعمیر']; @endphp
@extends('layouts.app')
@section('title', $rent ? 'قرارداد اجاره‌ی جدید' : 'قرارداد فروش جدید')
@section('content')
<div class="card">
  <div class="page-h">
    <h2>{{ $rent ? 'ثبت قرارداد اجاره' : 'ثبت قرارداد فروش' }}</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn {{ $rent ? '' : 'sec' }} sm" href="{{ route('contracts.create', ['type' => 'rent']) }}">اجاره</a>
      <a class="btn {{ $rent ? 'sec' : '' }} sm" href="{{ route('contracts.create', ['type' => 'sale']) }}">فروش</a>
      <a class="btn sec sm" href="{{ route('contracts.index') }}">بازگشت</a>
    </div>
  </div>

  @if($units->isEmpty())
    <div class="warnbox" style="margin-top:12px">{{ $rent ? 'واحد آزادی برای اجاره وجود ندارد (واحدهای دارای قرارداد فعال یا در حال تعمیر نمایش داده نمی‌شوند).' : 'هنوز واحدی ثبت نشده است.' }}</div>
  @elseif($people->isEmpty())
    <div class="warnbox" style="margin-top:12px">
      ابتدا {{ $rent ? 'مستأجر' : 'خریدار' }} را با نقش «{{ $rent ? 'مستأجر' : 'مالک واحد' }}» در بخش
      <a href="{{ route('members.index') }}"><b>اعضای ساختمان</b></a> ثبت (دعوت) کنید؛ سپس اینجا قابل انتخاب می‌شود.
    </div>
  @else
  <form method="POST" action="{{ route('contracts.store') }}" enctype="multipart/form-data" style="margin-top:10px">@csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="fgrid">
      <div class="fld"><label for="unit_id">واحد *</label>
        <select id="unit_id" name="unit_id" required><option value="">انتخاب کنید…</option>
          @foreach($units as $u)<option value="{{ $u->id }}" @selected((int) old('unit_id') === $u->id)>{{ $u->block->name }} / {{ $F::digits($u->number) }} — {{ $stLabel[$u->status->value] }}</option>@endforeach
        </select>@error('unit_id')<div class="e">{{ $message }}</div>@enderror</div>

      <div class="fld"><label for="user_id">{{ $rent ? 'مستأجر' : 'خریدار' }} *</label>
        <select id="user_id" name="user_id" required><option value="">انتخاب کنید…</option>
          @foreach($people as $p)<option value="{{ $p->id }}" @selected((int) old('user_id') === $p->id)>{{ $p->name }}@if($p->mobile) — {{ $F::digits($p->mobile) }}@endif</option>@endforeach
        </select>@error('user_id')<div class="e">{{ $message }}</div>@enderror</div>

      @if($rent)
        <div class="fld"><label for="deposit_amount">مبلغ پیش‌پرداخت (تومان) *</label><input id="deposit_amount" name="deposit_amount" inputmode="numeric" value="{{ old('deposit_amount', '0') }}" required>@error('deposit_amount')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="monthly_rent">اجاره‌ی ماهانه (تومان) *</label><input id="monthly_rent" name="monthly_rent" inputmode="numeric" value="{{ old('monthly_rent') }}" required>@error('monthly_rent')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="signed_at">تاریخ عقد قرارداد *</label><input id="signed_at" name="signed_at" class="jdate" data-max="today" data-min="1380/01/01" placeholder="انتخاب از تقویم" value="{{ old('signed_at', \App\Support\JalaliDate::format(today())) }}" required>@error('signed_at')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="end_date">تاریخ پایان قرارداد *</label><input id="end_date" name="end_date" class="jdate" data-min="today" placeholder="انتخاب از تقویم" value="{{ old('end_date') }}" required>@error('end_date')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="charge_payer">شارژ بر عهده‌ی *</label>
          <select id="charge_payer" name="charge_payer"><option value="tenant" @selected(old('charge_payer', 'tenant') === 'tenant')>مستأجر (پیش‌فرض)</option><option value="owner" @selected(old('charge_payer') === 'owner')>مالک واحد</option></select>
          @error('charge_payer')<div class="e">{{ $message }}</div>@enderror</div>
      @else
        <div class="fld"><label for="sale_price">مبلغ فروش (تومان) *</label><input id="sale_price" name="sale_price" inputmode="numeric" value="{{ old('sale_price') }}" required>@error('sale_price')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="signed_at">تاریخ فروش *</label><input id="signed_at" name="signed_at" class="jdate" data-max="today" data-min="1380/01/01" placeholder="انتخاب از تقویم" value="{{ old('signed_at', \App\Support\JalaliDate::format(today())) }}" required>@error('signed_at')<div class="e">{{ $message }}</div>@enderror</div>
      @endif

      <div class="fld full"><label for="contract_file">فایل قرارداد (اختیاری)</label><input id="contract_file" type="file" name="contract_file" accept="image/*,application/pdf"><div class="h">pdf یا تصویر (jpg، png، webp) — حداکثر ۱۰ مگابایت</div>@error('contract_file')<div class="e">{{ $message }}</div>@enderror</div>
    </div>

    <div class="warnbox" style="margin-top:12px">
      @if($rent)
        پس از ثبت، وضعیت واحد «اجاره‌داده‌شده» می‌شود و مستأجر به‌عنوان ساکن واحد ثبت می‌گردد. هر واحد فقط یک قرارداد اجاره‌ی فعال دارد.
      @else
        پس از ثبت، مالکیت واحد به خریدار منتقل می‌شود و مالک قبلی (در صورت وجود) از تاریخ فروش پایان می‌یابد. این عمل قابل بازگشت نیست.
      @endif
    </div>
    <div class="fbar"><button class="btn" type="submit">ثبت قرارداد {{ $rent ? 'اجاره' : 'فروش' }}</button></div>
  </form>
  @endif
</div>
@endsection
