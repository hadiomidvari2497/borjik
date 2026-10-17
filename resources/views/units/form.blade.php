@php
  $v = fn ($k, $d = null) => old($k, $unit?->{$k} ?? $d);
  $canEditStatus = ! $unit || in_array($unit->status->value, ['vacant', 'repair'], true);
@endphp
@extends('layouts.app')
@section('title', $unit ? 'ویرایش واحد' : 'واحد جدید')
@section('content')
<div class="card">
  <div class="page-h"><h2>{{ $unit ? 'ویرایش واحد' : 'واحد جدید' }}</h2></div>
  @if($blocks->isEmpty())
    <div class="empty">ابتدا باید یک بلوک بسازید. <a href="{{ route('blocks.create') }}">ساخت بلوک</a></div>
  @else
  <form method="POST" action="{{ $unit ? route('units.update', $unit) : route('units.store') }}" style="margin-top:12px">
    @csrf @if($unit) @method('PUT') @endif
    <div class="fgrid">
      <div class="fld"><label for="block_id">بلوک *</label>
        <select id="block_id" name="block_id" required>@foreach($blocks as $b)<option value="{{ $b->id }}" @selected((int) $v('block_id') === $b->id)>{{ $b->name }}</option>@endforeach</select>@error('block_id')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="number">شماره‌ی واحد *</label><input id="number" name="number" value="{{ $v('number') }}" required>@error('number')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="floor">طبقه *</label><input id="floor" type="number" name="floor" value="{{ $v('floor', 1) }}" required><div class="h">۰ = همکف (عدد منفی = زیرزمین)</div>@error('floor')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="area_m2">متراژ (متر مربع) *</label><input id="area_m2" type="number" step="0.01" name="area_m2" value="{{ $v('area_m2') }}" required>@error('area_m2')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="bedrooms">تعداد خواب</label><input id="bedrooms" type="number" min="0" name="bedrooms" value="{{ $v('bedrooms') }}"><div class="h">برای شارژ «واحدی» بر اساس تعداد خواب لازم است.</div>@error('bedrooms')<div class="e">{{ $message }}</div>@enderror</div>
      @if($unit && ! $canEditStatus)
        <div class="fld"><label for="residents_count">تعداد ساکنین فعلی</label><input id="residents_count" type="number" min="0" name="residents_count" value="{{ $v('residents_count', 0) }}"><div class="h">فقط برای واحد دارای ساکن؛ مبنای روش‌های شارژ نفری.</div>@error('residents_count')<div class="e">{{ $message }}</div>@enderror</div>
      @endif
      <div class="fld"><label for="direction">جهت واحد</label>
        <select id="direction" name="direction"><option value="">—</option>@foreach($directions as $d)<option value="{{ $d->value }}" @selected($v('direction') === $d->value || $v('direction') === $d)>{{ ['north' => 'شمالی', 'south' => 'جنوبی', 'east' => 'شرقی', 'west' => 'غربی'][$d->value] }}</option>@endforeach</select></div>
      <div class="fld"><label for="postal_code">کد پستی</label><input id="postal_code" name="postal_code" inputmode="numeric" maxlength="10" value="{{ $v('postal_code') }}">@error('postal_code')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="status">وضعیت</label>
        @if($canEditStatus)
          <select id="status" name="status"><option value="vacant" @selected(($unit?->status->value ?? 'vacant') === 'vacant')>خالی</option><option value="repair" @selected(($unit?->status->value ?? '') === 'repair')>در حال تعمیر</option></select>
        @else
          <input value="{{ ['sold' => 'فروخته‌شده', 'rented' => 'اجاره‌داده‌شده'][$unit->status->value] }}" disabled><div class="h">وضعیت با ثبت قرارداد تغییر می‌کند.</div>
        @endif</div>
      <div class="fld"><label for="opening_balance">مانده‌ی اولیه (تومان)</label><input id="opening_balance" type="number" name="opening_balance" value="{{ $v('opening_balance', 0) }}"><div class="h">مثبت = بستانکار، منفی = بدهکار (برای انتقال از سیستم قبلی).</div></div>
      <div class="fld full"><label for="notes">توضیحات</label><textarea id="notes" name="notes" rows="2">{{ $v('notes') }}</textarea></div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ذخیره</button><a class="btn sec" href="{{ route('units.index') }}">انصراف</a></div>
  </form>
  @endif
</div>
@if($unit)
  @include('partials.history', ['subject' => $unit])
@endif
@endsection
