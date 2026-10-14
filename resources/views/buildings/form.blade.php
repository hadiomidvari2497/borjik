@php
  $editing = (bool) $building;
  $v = fn ($key, $default = null) => old($key, $building?->{$key} ?? $default);
@endphp
@extends('layouts.app')
@section('title', $editing ? 'تنظیمات ساختمان' : 'ساختمان جدید')
@section('content')
<div class="card">
  <div class="page-h"><h2>{{ $editing ? 'اطلاعات ساختمان' : 'ساخت ساختمان جدید' }}</h2>
    @if($editing)<span class="pill">کد ساختمان: {{ \App\Support\Fa::digits($building->code) }}</span>@endif</div>

  @if($blocked)
    <div class="warnbox" style="margin:12px 0">برای ساخت ساختمان باید اشتراک فعال داشته باشید. اشتراک را تهیه کنید یا (در توسعه) با دستور <code>php artisan borjik:grant-subscription {{ auth()->user()->mobile }}</code> فعال کنید.</div>
  @else
  <form method="POST" action="{{ $editing ? route('building.update') : route('buildings.store') }}" style="margin-top:12px">
    @csrf @if($editing) @method('PUT') @endif
    <div class="fgrid">
      <div class="fld full"><label for="name">نام ساختمان *</label><input id="name" name="name" value="{{ $v('name') }}" required>@error('name')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="type">نوع کاربری *</label>
        <select id="type" name="type">@foreach($types as $t)<option value="{{ $t->value }}" @selected($v('type', 'residential') === $t->value || $v('type') === $t)>{{ ['residential' => 'مسکونی', 'commercial' => 'تجاری', 'office' => 'اداری', 'educational' => 'آموزشی', 'mixed' => 'ترکیبی', 'other' => 'سایر'][$t->value] }}</option>@endforeach</select>
        @error('type')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="postal_code">کد پستی</label><input id="postal_code" name="postal_code" inputmode="numeric" maxlength="10" value="{{ $v('postal_code') }}">@error('postal_code')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="built_at">تاریخ ساخت (شمسی)</label><input id="built_at" name="built_at" placeholder="۱۳۹۵/۰۶/۱۵" value="{{ old('built_at', \App\Support\JalaliDate::format($building?->built_at)) }}">@error('built_at')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="parking_count_info">تعداد پارکینگ</label><input id="parking_count_info" type="number" min="0" name="parking_count_info" value="{{ $v('parking_count_info', 0) }}"></div>
      <div class="fld"><label for="storage_count_info">تعداد انبار</label><input id="storage_count_info" type="number" min="0" name="storage_count_info" value="{{ $v('storage_count_info', 0) }}"></div>
      <div class="fld"><label for="province_id">استان</label>
        <select id="province_id" name="province_id"><option value="">انتخاب کنید</option>@foreach($provinces as $p)<option value="{{ $p->id }}" @selected((int) $v("province_id") === (int) $p->id)>{{ $p->name }}</option>@endforeach</select></div>
      <div class="fld"><label for="city_id">شهر</label><select id="city_id" name="city_id" data-selected="{{ $v('city_id') }}"><option value="">انتخاب کنید</option></select></div>
      <div class="fld full"><label for="address">آدرس</label><textarea id="address" name="address" rows="2">{{ $v('address') }}</textarea></div>
    </div>
    <div class="fbar"><button class="btn" type="submit">{{ $editing ? 'ذخیره' : 'ساخت ساختمان' }}</button><a class="btn sec" href="{{ route($editing ? 'dashboard' : 'buildings.create') }}" @if(!$editing) hidden @endif>انصراف</a></div>
  </form>
  <script>
    (function () {
      var cities = @json($cities), prov = document.getElementById('province_id'), city = document.getElementById('city_id');
      function fill() {
        var list = cities[prov.value] || [], sel = city.dataset.selected;
        city.innerHTML = '<option value="">انتخاب کنید</option>' + list.map(function (c) { return '<option value="' + c.id + '"' + (String(c.id) === String(sel) ? ' selected' : '') + '>' + c.name + '</option>'; }).join('');
      }
      prov.addEventListener('change', function () { city.dataset.selected = ''; fill(); }); fill();
    })();
  </script>
  @endif
</div>
@endsection
