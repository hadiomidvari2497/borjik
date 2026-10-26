@extends('admin.layout')
@section('title', $coupon ? 'ویرایش کد تخفیف' : 'کد تخفیف جدید')
@section('content')
@php $v = fn ($key, $default = null) => old($key, $coupon?->{$key} ?? $default); $selectedPlans = old('plan_ids', $coupon?->plan_ids ?? []); $dateValue = fn ($key) => old($key) !== null ? old($key) : \App\Support\JalaliDate::format($coupon?->{$key}); @endphp
<div class="card">
  <div class="page-h"><h2>{{ $coupon ? 'ویرایش کد تخفیف' : 'ایجاد کد تخفیف' }}</h2><a class="btn sec sm" href="{{ route('admin.coupons.index') }}">بازگشت</a></div>
  <form method="POST" action="{{ $coupon ? route('admin.coupons.update', $coupon->id) : route('admin.coupons.store') }}" style="margin-top:12px">
    @csrf @if($coupon) @method('PUT') @endif
    <div class="fgrid">
      <div class="fld"><label for="code">کد تخفیف *</label><input id="code" name="code" value="{{ $v('code') }}" placeholder="مثلاً AUTUMN20" required pattern="[A-Za-z0-9_-]{3,64}">@error('code')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="name">عنوان کمپین</label><input id="name" name="name" value="{{ $v('name') }}" placeholder="تخفیف پاییزی"></div>
      <div class="fld"><label for="discount_type">نوع تخفیف</label><select id="discount_type" name="discount_type"><option value="percent" @selected($v('discount_type','percent')==='percent')>درصدی</option><option value="fixed" @selected($v('discount_type')==='fixed')>مبلغ ثابت (تومان)</option></select></div>
      <div class="fld"><label for="discount_value">مقدار تخفیف *</label><input id="discount_value" name="discount_value" inputmode="numeric" value="{{ $v('discount_value') }}" required>@error('discount_value')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="min_order_amount">حداقل مبلغ خرید (تومان)</label><input id="min_order_amount" name="min_order_amount" inputmode="numeric" value="{{ $v('min_order_amount',0) }}"></div>
      <div class="fld"><label for="max_uses">حداکثر کل استفاده (خالی = نامحدود)</label><input id="max_uses" name="max_uses" inputmode="numeric" value="{{ $v('max_uses') }}"></div>
      <div class="fld"><label for="max_uses_per_user">حداکثر استفاده برای هر کاربر</label><input id="max_uses_per_user" name="max_uses_per_user" inputmode="numeric" value="{{ $v('max_uses_per_user',1) }}" required></div>
      <div class="fld"><label for="starts_at">شروع اعتبار (تاریخ شمسی)</label><input id="starts_at" name="starts_at" class="jdate" autocomplete="off" placeholder="۱۴۰۵/۰۷/۰۱" value="{{ $dateValue('starts_at') }}">@error('starts_at')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="ends_at">پایان اعتبار (تاریخ شمسی)</label><input id="ends_at" name="ends_at" class="jdate" autocomplete="off" placeholder="۱۴۰۵/۰۷/۳۰" value="{{ $dateValue('ends_at') }}">@error('ends_at')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld full"><label>محدود به پلن‌های مشخص (انتخاب نکنید = همه پلن‌ها)</label><div class="feat">@foreach($plans as $plan)<label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="plan_ids[]" value="{{ $plan->id }}" @checked(in_array($plan->id, $selectedPlans)) style="width:auto">{{ $plan->name }} — {{ \App\Support\Fa::money($plan->price) }}</label>@endforeach</div></div>
      <div class="fld"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_active" value="1" @checked($v('is_active',true)) style="width:auto">کد فعال باشد</label></div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ذخیره کد تخفیف</button><a class="btn sec" href="{{ route('admin.coupons.index') }}">انصراف</a></div>
  </form>
</div>
@endsection
