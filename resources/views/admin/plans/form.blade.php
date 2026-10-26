@extends('admin.layout')
@section('title', $plan ? 'ویرایش پلن' : 'پلن جدید')
@section('content')
@php
  $v = fn ($key, $default = null) => old($key, $plan?->{$key} ?? $default);
  $dateValue = fn ($key) => old($key) !== null ? old($key) : \App\Support\JalaliDate::format($plan?->{$key});
@endphp
<div class="card">
  <div class="page-h"><h2>{{ $plan ? 'ویرایش پلن اشتراک' : 'ایجاد پلن اشتراک' }}</h2>
    <a class="btn sec sm" href="{{ route('admin.plans.index') }}">بازگشت</a>
  </div>
  <form method="POST" action="{{ $plan ? route('admin.plans.update', $plan->id) : route('admin.plans.store') }}" style="margin-top:12px">
    @csrf
    @if($plan) @method('PUT') @endif
    <div class="fgrid">
      <div class="fld full">
        <label for="name">نام پلن *</label>
        <input id="name" name="name" value="{{ $v('name') }}" placeholder="مثلاً رایگان - ۱۰ واحد" required>
        @error('name')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="units_limit">سقف تعداد واحد *</label>
        <input id="units_limit" name="units_limit" inputmode="numeric" value="{{ $v('units_limit', 10) }}" required>
        @error('units_limit')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="duration_months">مدت (ماه) *</label>
        <input id="duration_months" name="duration_months" inputmode="numeric" value="{{ $v('duration_months', 1) }}" required>
        @error('duration_months')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="price">قیمت (تومان) *</label>
        <input id="price" name="price" inputmode="numeric" value="{{ $v('price', 0) }}" required>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">برای پلن رایگان عدد <b>۰</b> وارد کنید.</div>
        @error('price')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="discount_type">تخفیف مستقیم پلن</label>
        <select id="discount_type" name="discount_type">
          <option value="" @selected(!$v('discount_type'))>بدون تخفیف</option>
          <option value="percent" @selected($v('discount_type') === 'percent')>درصدی</option>
          <option value="fixed" @selected($v('discount_type') === 'fixed')>مبلغ ثابت (تومان)</option>
        </select>
      </div>
      <div class="fld">
        <label for="discount_value">مقدار تخفیف</label>
        <input id="discount_value" name="discount_value" inputmode="numeric" value="{{ $v('discount_value', 0) }}">
        @error('discount_value')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="discount_starts_at">شروع تخفیف (تاریخ شمسی)</label>
        <input id="discount_starts_at" name="discount_starts_at" class="jdate" autocomplete="off" placeholder="۱۴۰۵/۰۷/۰۱" value="{{ $dateValue('discount_starts_at') }}">
        @error('discount_starts_at')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="discount_ends_at">پایان تخفیف (تاریخ شمسی)</label>
        <input id="discount_ends_at" name="discount_ends_at" class="jdate" autocomplete="off" placeholder="۱۴۰۵/۰۷/۳۰" value="{{ $dateValue('discount_ends_at') }}">
        @error('discount_ends_at')<div class="e">{{ $message }}</div>@enderror
      </div>@enderror
      </div>
      <div class="fld">
        <label style="display:flex;gap:8px;align-items:center;margin-top:28px">
          <input type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $plan?->is_active ?? true))>
          نمایش در صفحهٔ اشتراک کاربران (فعال)
        </label>
      </div>
    </div>
    <div class="fbar">
      <button class="btn" type="submit">{{ $plan ? 'ذخیره تغییرات' : 'ایجاد پلن' }}</button>
      <a class="btn sec" href="{{ route('admin.plans.index') }}">انصراف</a>
    </div>
  </form>
</div>
@endsection
