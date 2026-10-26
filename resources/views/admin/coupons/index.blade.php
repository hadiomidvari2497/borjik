@extends('admin.layout')
@section('title', 'کدهای تخفیف')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<div class="card">
  <div class="page-h"><h2>کدهای تخفیف</h2><a class="btn" href="{{ route('admin.coupons.create') }}">+ کد تخفیف جدید</a></div>
  <p style="color:var(--muted);line-height:1.8">کدها در مرحله خرید پلن وارد می‌شوند. محدودیت مصرف، بازه اعتبار و پلن‌های مجاز برای هر کد اعمال می‌شود.</p>
  @if($coupons->isEmpty())<div class="empty">هنوز کد تخفیفی ساخته نشده است.</div>@else
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>کد</th><th>تخفیف</th><th>مصرف‌شده</th><th>اعتبار</th><th>وضعیت</th><th></th></tr></thead><tbody>
  @foreach($coupons as $coupon)
    <tr>
      <td><b>{{ $coupon->code }}</b><div style="color:var(--muted);font-size:12px">{{ $coupon->name }}</div></td>
      <td>{{ $coupon->discount_type === 'percent' ? $F::num($coupon->discount_value).'٪' : $F::money($coupon->discount_value) }}</td>
      <td>{{ $F::num($coupon->used_count) }}{{ $coupon->max_uses ? ' / '.$F::num($coupon->max_uses) : '' }}</td>
      <td>{{ $coupon->starts_at ? $F::date($coupon->starts_at, 'Y/m/d') : 'از همین حالا' }} تا {{ $coupon->ends_at ? $F::date($coupon->ends_at, 'Y/m/d') : 'بدون پایان' }}</td>
      <td><span class="pill">{{ $coupon->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
      <td class="act"><a class="btn sec sm" href="{{ route('admin.coupons.edit', $coupon->id) }}">ویرایش</a><form method="POST" action="{{ route('admin.coupons.toggle', $coupon->id) }}">@csrf<button class="btn sm" type="submit">{{ $coupon->is_active ? 'غیرفعال‌کردن' : 'فعال‌کردن' }}</button></form></td>
    </tr>
  @endforeach
  </tbody></table></div>
  {{ $coupons->links() }}
  @endif
</div>
@endsection
