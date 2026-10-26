@extends('admin.layout')
@section('title', 'تنظیم گروهی قیمت‌ها')
@section('content')
<div class="card">
  <div class="page-h"><h2>تغییر گروهی قیمت پلن‌ها</h2><a class="btn sec sm" href="{{ route('admin.finances.index') }}">بازگشت</a></div>
  <div class="warnbox" style="margin:12px 0;line-height:1.8">این عملیات قیمت پایه پلن‌ها را تغییر می‌دهد و روی قیمت‌های آینده اثر دارد؛ تراکنش‌ها و پرداخت‌های قبلی تغییر نمی‌کنند. تخفیف اختصاصی پلن پس از قیمت پایه محاسبه می‌شود.</div>
  <form method="POST" action="{{ route('admin.finances.bulk-adjust') }}">
    @csrf
    <div class="fgrid">
      <div class="fld"><label for="adjustment_type">نوع تغییر</label><select id="adjustment_type" name="adjustment_type" required><option value="increase_percent">افزایش درصدی</option><option value="decrease_percent">کاهش درصدی</option><option value="increase_fixed">افزایش مبلغ ثابت</option><option value="decrease_fixed">کاهش مبلغ ثابت</option></select></div>
      <div class="fld"><label for="value">مقدار (درصد یا تومان)</label><input id="value" name="value" inputmode="decimal" value="{{ old('value') }}" required><small style="color:var(--muted)">برای تغییر درصدی حداکثر ۱۰۰ وارد کنید.</small>@error('value')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="scope">دامنه اعمال</label><select id="scope" name="scope"><option value="all">همه پلن‌ها (به‌جز رایگان‌ها)</option><option value="active">فقط پلن‌های فعال</option></select></div>
    </div>
    <div class="fbar"><button class="btn" type="submit" data-confirm="قیمت پایه پلن‌های انتخاب‌شده تغییر کند؟" data-confirm-title="تأیید تغییر قیمت">اعمال تغییر قیمت</button></div>
  </form>
</div>
@endsection
