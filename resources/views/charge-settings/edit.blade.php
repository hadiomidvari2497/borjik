@php
  $s = $setting;
  $o = fn ($k, $d = null) => old($k, $s->{$k} ?? $d);
  $cur = (int) old('method', $s->method->value);
@endphp
@extends('layouts.app')
@section('title', 'تنظیمات شارژ')
@section('content')
<div class="card">
  <div class="page-h"><h2>تنظیمات محاسبه‌ی شارژ</h2></div>
  <form method="POST" action="{{ route('charge-settings.update') }}" style="margin-top:12px">@csrf @method('PUT')
    <div class="fgrid">
      <div class="fld full"><label for="method">روش محاسبه *</label>
        <select id="method" name="method">@foreach($methods as $m)<option value="{{ $m->value }}" @selected($cur === $m->value)>{{ \App\Support\Fa::digits($m->value) }}. {{ $m->label() }}</option>@endforeach</select>
        @error('method')<div class="e">{{ $message }}</div>@enderror</div>

      <div class="fld" data-f="mode"><label for="mode">نوع محاسبه</label>
        <select id="mode" name="mode"><option value="rate" @selected($o('mode', 'rate') === 'rate')>نرخی (نرخ ثابت برای هر متر یا نفر)</option><option value="ratio" @selected($o('mode') === 'ratio')>نسبتی (تقسیم کل هزینه‌ها)</option></select></div>
      <div class="fld" data-f="fixed"><label for="fixed_amount">مبلغ ثابت هر واحد (تومان)</label><input id="fixed_amount" name="fixed_amount" inputmode="numeric" value="{{ $o('fixed_amount', 0) }}">@error('fixed_amount')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld" data-f="area"><label for="area_rate">نرخ هر متر مربع (تومان)</label><input id="area_rate" name="area_rate" inputmode="numeric" value="{{ $o('area_rate', 0) }}">@error('area_rate')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld" data-f="person"><label for="person_rate">نرخ هر نفر (تومان)</label><input id="person_rate" name="person_rate" inputmode="numeric" value="{{ $o('person_rate', 0) }}">@error('person_rate')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld" data-f="ratio"><label for="person_ratio_pct">سهم بخش نفری (درصد)</label><input id="person_ratio_pct" name="person_ratio_pct" inputmode="numeric" value="{{ $o('person_ratio_pct', 20) }}"><div class="h">باقی‌مانده سهم بخش متراژی است (پیش‌فرض ۲۰ به ۸۰).</div>@error('person_ratio_pct')<div class="e">{{ $message }}</div>@enderror</div>

      <div class="fld"><label for="calc_day">روز محاسبه‌ی شارژ در هر ماه *</label><input id="calc_day" name="calc_day" inputmode="numeric" value="{{ $o('calc_day', 25) }}">@error('calc_day')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="warn_days_before">یادآوری ثبت هزینه، چند روز قبل *</label><input id="warn_days_before" name="warn_days_before" inputmode="numeric" value="{{ $o('warn_days_before', 5) }}">@error('warn_days_before')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="due_days">مهلت پرداخت (روز بعد از صدور) *</label><input id="due_days" name="due_days" inputmode="numeric" value="{{ $o('due_days', 10) }}">@error('due_days')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="emergency_phone">شماره‌ی اضطراری ساختمان</label><input id="emergency_phone" name="emergency_phone" inputmode="tel" value="{{ $o('emergency_phone') }}">@error('emergency_phone')<div class="e">{{ $message }}</div>@enderror</div>

      <div class="fld full"><label><input type="checkbox" name="late_fee_enabled" value="1" id="late_on" style="width:auto;margin-inline-end:8px" @checked((bool) $o('late_fee_enabled', false))>جریمه‌ی دیرکرد فعال باشد</label></div>
      <div class="fld" data-late><label for="late_fee_type">نوع جریمه</label>
        <select id="late_fee_type" name="late_fee_type"><option value="percent" @selected($o('late_fee_type') === 'percent')>درصد ماهانه از مبلغ معوق</option><option value="fixed" @selected($o('late_fee_type') === 'fixed')>مبلغ ثابت روزانه</option></select>@error('late_fee_type')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld" data-late><label for="late_fee_value">مقدار جریمه</label><input id="late_fee_value" name="late_fee_value" inputmode="numeric" value="{{ $o('late_fee_value', 0) }}"><div class="h">درصد صحیح (مثلاً ۲) یا تومان در روز.</div></div>
      <div class="fld" data-late><label for="late_fee_cap">سقف جریمه (تومان)</label><input id="late_fee_cap" name="late_fee_cap" inputmode="numeric" value="{{ $o('late_fee_cap') }}"></div>
      <div class="fld" data-late><label for="late_fee_start_day">شروع جریمه، چند روز بعد از سررسید</label><input id="late_fee_start_day" name="late_fee_start_day" inputmode="numeric" value="{{ $o('late_fee_start_day', 1) }}"></div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ذخیره‌ی تنظیمات</button></div>
  </form>
</div>

<div class="card" id="rules-card" data-f="rules">
  <div class="page-h"><h2>قاعده‌های شارژ ثابت (روش ۱)</h2></div>
  <p style="color:var(--muted);font-size:12.5px;margin:4px 0 10px">مبلغ همه‌ی قاعده‌هایی که با یک واحد جور باشند با هم جمع می‌شود؛ مثلاً «دوخوابه ۶۰٬۰۰۰» و «انباری‌دار بدون پارکینگ ۵۰٬۰۰۰». اگر هیچ قاعده‌ای جور نبود، «مبلغ ثابت هر واحد» اعمال می‌شود.</p>
  @if($rules->isNotEmpty())
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>نام</th><th>خواب</th><th>پارکینگ</th><th>انباری</th><th>مبلغ</th><th></th></tr></thead><tbody>
    @foreach($rules as $r)
      <tr><td>{{ $r->name }}</td><td>{{ $r->bedrooms !== null ? \App\Support\Fa::digits($r->bedrooms) : 'مهم نیست' }}</td>
        <td>{{ $r->has_parking === null ? 'مهم نیست' : ($r->has_parking ? 'دارد' : 'ندارد') }}</td><td>{{ $r->has_storage === null ? 'مهم نیست' : ($r->has_storage ? 'دارد' : 'ندارد') }}</td>
        <td>{{ \App\Support\Fa::money($r->amount) }}</td>
        <td class="act"><form method="POST" action="{{ route('charge-settings.rules.destroy', $r->id) }}">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button></form></td></tr>
    @endforeach
  </tbody></table></div>
  @endif
  <form method="POST" action="{{ route('charge-settings.rules.store') }}" style="margin-top:12px">@csrf
    <div class="fgrid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
      <div class="fld"><label>نام قاعده</label><input name="name" required></div>
      <div class="fld"><label>تعداد خواب</label><input name="bedrooms" inputmode="numeric" placeholder="مهم نیست"></div>
      <div class="fld"><label>پارکینگ</label><select name="has_parking"><option value="any">مهم نیست</option><option value="yes">دارد</option><option value="no">ندارد</option></select></div>
      <div class="fld"><label>انباری</label><select name="has_storage"><option value="any">مهم نیست</option><option value="yes">دارد</option><option value="no">ندارد</option></select></div>
      <div class="fld"><label>مبلغ (تومان)</label><input name="amount" inputmode="numeric" required></div>
    </div>
    <div class="fbar"><button class="btn sec" type="submit">+ افزودن قاعده</button></div>
  </form>
</div>

<div class="card">
  <div class="page-h"><h2>دسته‌های هزینه</h2></div>
  <p style="color:var(--muted);font-size:12.5px;margin:4px 0 10px">در روش ۱۰ هر هزینه بر اساس «مبنای تقسیم» دسته‌اش بین واحدها پخش می‌شود (مثلاً آب ← تعداد نفرات، گاز ← متراژ).</p>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>نام</th><th>نوع</th><th>مبنای تقسیم</th><th></th></tr></thead><tbody>
    @foreach($categories as $c)
      <tr><td>{{ $c->name }}</td><td>{{ $c->kind === 'fixed' ? 'ثابت' : 'متغیر' }}</td><td>{{ ['equal' => 'مساوی بین واحدها', 'area' => 'متراژ', 'persons' => 'تعداد نفرات'][$c->split_basis] ?? $c->split_basis }}</td>
        <td class="act"><form method="POST" action="{{ route('charge-settings.categories.destroy', $c->id) }}" onsubmit="return confirm('حذف شود؟')">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button></form></td></tr>
    @endforeach
  </tbody></table></div>
  <form method="POST" action="{{ route('charge-settings.categories.store') }}" style="margin-top:12px">@csrf
    <div class="fgrid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
      <div class="fld"><label>نام دسته</label><input name="name" required></div>
      <div class="fld"><label>نوع</label><select name="kind"><option value="variable">متغیر</option><option value="fixed">ثابت</option></select></div>
      <div class="fld"><label>مبنای تقسیم</label><select name="split_basis"><option value="equal">مساوی بین واحدها</option><option value="area">متراژ</option><option value="persons">تعداد نفرات</option></select></div>
    </div>
    <div class="fbar"><button class="btn sec" type="submit">+ افزودن دسته</button></div>
  </form>
</div>

<script>
(function () {
  var m = document.getElementById('method'), mode = document.getElementById('mode'), late = document.getElementById('late_on');
  function show(sel, on) { document.querySelectorAll(sel).forEach(function (e) { e.hidden = !on; }); }
  function sync() {
    var n = +m.value, rate = mode.value === 'rate', modal = n === 2 || n === 4;
    show('[data-f="mode"]', modal);
    show('[data-f="fixed"]', [1, 5, 6, 7, 9].indexOf(n) > -1);
    show('[data-f="area"]', [5, 8, 9].indexOf(n) > -1 || (modal && rate));
    show('[data-f="person"]', [3, 6, 8, 9].indexOf(n) > -1 || (n === 4 && rate));
    show('[data-f="ratio"]', n === 7 || (n === 4 && !rate));
    show('[data-f="rules"]', n === 1);
    show('[data-late]', late.checked);
  }
  [m, mode, late].forEach(function (e) { e.addEventListener('change', sync); }); sync();
})();
</script>
@endsection
