@extends('layouts.app')
@section('title', 'وضعیت سکونت واحد')
@section('content')
<div class="card" style="max-width:760px;margin:auto">
  <div class="page-h">
    <div><h2>وضعیت سکونت واحد {{ \App\Support\Fa::digits($unit->number) }}</h2><p class="muted">{{ $unit->block?->name }} · ثبت اطلاعات توسط مالک</p></div>
    <a class="btn sec" href="{{ route('my.units') }}">بازگشت</a>
  </div>
  @if($errors->any())<div class="alert error" style="margin:12px 0">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('my.units.occupancy.update', $unit) }}" style="margin-top:16px">
    @csrf @method('PUT')
    <div class="fgrid">
      <div class="fld full">
        <label for="occupancy_type">وضعیت سکونت واحد *</label>
        <select id="occupancy_type" name="occupancy_type" required>
          <option value="owner" @selected(old('occupancy_type', $unit->occupancy_type) === 'owner')>مالک خودم ساکن هستم</option>
          <option value="rented" @selected(old('occupancy_type', $unit->occupancy_type) === 'rented')>واحد را اجاره داده‌ام</option>
          <option value="vacant" @selected(old('occupancy_type', $unit->occupancy_type) === 'vacant')>واحد فعلاً خالی است</option>
        </select>
      </div>
      <div id="tenant-fields" class="fld full" style="display:none">
        <div class="fgrid">
          <div class="fld"><label for="representative_name">نام و نام خانوادگی مستأجر *</label><input id="representative_name" name="representative_name" value="{{ old('representative_name', $unit->representative_name) }}" maxlength="150">@error('representative_name')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld"><label for="representative_mobile">شماره موبایل مستأجر *</label><input id="representative_mobile" name="representative_mobile" inputmode="numeric" maxlength="11" value="{{ old('representative_mobile', $unit->representative_mobile) }}">@error('representative_mobile')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld"><label for="lease_start_date">تاریخ شروع اجاره *</label><input id="lease_start_date" name="lease_start_date" value="{{ old('lease_start_date', $unit->lease_start_date ? \App\Support\Fa::date($unit->lease_start_date, 'Y/m/d') : '') }}" placeholder="۱۴۰۵/۰۷/۰۱">@error('lease_start_date')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld"><label for="lease_end_date">تاریخ پایان اجاره *</label><input id="lease_end_date" name="lease_end_date" value="{{ old('lease_end_date', $unit->lease_end_date ? \App\Support\Fa::date($unit->lease_end_date, 'Y/m/d') : '') }}" placeholder="۱۴۰۶/۰۷/۰۱">@error('lease_end_date')<div class="e">{{ $message }}</div>@enderror</div>
        </div>
        <div class="h">اطلاعات مستأجر فقط برای شناسایی سرپرست واحد ثبت می‌شود؛ برای او حساب کاربری یا دعوت‌نامه ساخته نمی‌شود.</div>
      </div>
      <div id="family-field" class="fld">
        <label for="family_count">تعداد اعضای خانواده *</label>
        <input id="family_count" type="number" name="family_count" min="0" max="99" value="{{ old('family_count', $unit->family_count) }}" required>
        <div class="h">تعداد کل افراد ساکن در واحد را وارد کنید.</div>
        @error('family_count')<div class="e">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ذخیره اطلاعات سکونت</button><a class="btn sec" href="{{ route('my.units') }}">انصراف</a></div>
  </form>
</div>
<script>
(() => {
  const type = document.getElementById('occupancy_type');
  const tenant = document.getElementById('tenant-fields');
  const family = document.getElementById('family-field');
  const count = document.getElementById('family_count');
  const sync = () => {
    tenant.style.display = type.value === 'rented' ? '' : 'none';
    family.style.display = type.value === 'vacant' ? 'none' : '';
    count.required = type.value !== 'vacant';
  };
  type.addEventListener('change', sync);
  sync();
})();
</script>
@endsection
