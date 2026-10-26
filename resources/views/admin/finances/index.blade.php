@extends('admin.layout')
@section('title', 'مدیریت مالی و درآمد')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<div class="card">
  <div class="page-h"><h2>مدیریت مالی و درآمد</h2><a class="btn sec" href="{{ route('admin.subscription-payments.index') }}">دفتر پرداخت‌ها</a></div>
  <p style="color:var(--muted);line-height:1.8">مدیریت قیمت‌گذاری، کمپین‌های تخفیف و کنترل درآمد اشتراک‌ها. مبالغ پرداخت‌شده در سوابق مالی ثابت می‌مانند.</p>
  <div class="feat">
    <div class="card"><small>درآمد امروز</small><h2>{{ $F::money($stats['revenue_today']) }}</h2></div>
    <div class="card"><small>درآمد این ماه</small><h2>{{ $F::money($stats['revenue_month']) }}</h2></div>
    <div class="card"><small>تراکنش موفق</small><h2>{{ $F::num($stats['paid_count']) }}</h2></div>
    <div class="card"><small>در انتظار / ناموفق</small><h2>{{ $F::num($stats['pending_count']) }} / {{ $F::num($stats['failed_count']) }}</h2></div>
  </div>
</div>
<div class="card">
  <div class="page-h"><h2>قیمت‌گذاری و تخفیف‌ها</h2></div>
  <div class="feat">
    <div class="card"><h3>قیمت و تخفیف هر پلن</h3><p style="color:var(--muted);line-height:1.8">قیمت پایه، تخفیف درصدی یا مبلغ ثابت و بازه اعتبار برای هر پلن.</p><a class="btn" href="{{ route('admin.plans.index') }}">مدیریت پلن‌ها</a></div>
    <div class="card"><h3>کدهای تخفیف</h3><p style="color:var(--muted);line-height:1.8">ساخت کمپین، تعیین سقف مصرف، محدودیت پلن و تاریخ شروع و پایان.</p><a class="btn" href="{{ route('admin.coupons.index') }}">مدیریت کدهای تخفیف</a></div>
    <div class="card"><h3>تغییر گروهی قیمت‌ها</h3><p style="color:var(--muted);line-height:1.8">افزایش یا کاهش درصدی یا مبلغ ثابت روی قیمت پایه چند پلن به‌صورت یکجا.</p><a class="btn" href="{{ route('admin.finances.bulk-edit') }}">تنظیم گروهی قیمت‌ها</a></div>
    <div class="card"><h3>گزارش پرداخت‌ها</h3><p style="color:var(--muted);line-height:1.8">وضعیت تراکنش‌ها، درآمد، بازه زمانی و خروجی CSV.</p><a class="btn sec" href="{{ route('admin.subscription-payments.index') }}">مشاهده گزارش</a></div>
  </div>
</div>
<div class="card">
  <div class="page-h"><h2>تاریخچه تغییرات قیمت</h2></div>
  @if($recentAdjustments->isEmpty())
    <div class="empty">هنوز تغییر گروهی قیمت ثبت نشده است.</div>
  @else
    <div class="tbl-wrap"><table class="tbl"><thead><tr><th>تاریخ</th><th>نوع تغییر</th><th>مقدار</th><th>تعداد پلن</th></tr></thead><tbody>
    @foreach($recentAdjustments as $item)
      <tr><td>{{ $F::date($item->created_at, 'Y/m/d H:i') }}</td><td>{{ ['increase_percent'=>'افزایش درصدی','decrease_percent'=>'کاهش درصدی','increase_fixed'=>'افزایش مبلغ ثابت','decrease_fixed'=>'کاهش مبلغ ثابت'][$item->adjustment_type] ?? $item->adjustment_type }}</td><td>{{ $F::num($item->adjustment_value) }}</td><td>{{ $F::num($item->affected_plans) }}</td></tr>
    @endforeach
    </tbody></table></div>
  @endif
</div>
@endsection
