@php $F = \App\Support\Fa::class; $st = ['draft' => ['پیش‌نویس', 'h'], 'calculated' => ['محاسبه‌شده', ''], 'published' => ['منتشرشده', '']]; @endphp
@extends('layouts.app')
@section('title', 'محاسبه‌ی شارژ')
@section('content')
@unless($configured)<div class="warnbox">هنوز تنظیمات شارژ انجام نشده است. <a href="{{ route('charge-settings.edit') }}">رفتن به تنظیمات</a></div>@endunless
<div class="card">
  <div class="page-h"><h2>دوره‌های شارژ</h2>
    <form method="POST" action="{{ route('charges.open') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">@csrf
      <select name="month" aria-label="ماه" style="padding:9px 12px;border-radius:12px;border:1px solid var(--line);background:var(--card);font:inherit;color:inherit">
        @foreach(\App\Support\Fa::MONTHS as $i => $name)<option value="{{ $i + 1 }}" @selected($month === $i + 1)>{{ $name }}</option>@endforeach</select>
      <input name="year" value="{{ $year }}" inputmode="numeric" aria-label="سال" style="width:90px;padding:9px 12px;border-radius:12px;border:1px solid var(--line);background:var(--card);font:inherit;color:inherit">
      <button class="btn" type="submit">باز کردن ماه</button>
    </form></div>
  @if($periods->isEmpty())<div class="empty">هنوز دوره‌ای ایجاد نشده است. ماه موردنظر را باز کنید، هزینه‌ها را ثبت و شارژ را محاسبه کنید.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>ماه</th><th>تاریخ محاسبه</th><th>جمع هزینه‌ها</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    @foreach($periods as $p)
      <tr><td><b>{{ \App\Support\Fa::MONTHS[$p->jalali_month - 1] }} {{ $F::digits($p->jalali_year) }}</b></td><td>{{ $F::date($p->calc_date, 'Y/m/d') }}</td><td>{{ $F::money($p->total_expenses) }}</td>
        <td><span class="pill {{ $st[$p->status->value][1] }}">{{ $st[$p->status->value][0] }}</span></td>
        <td class="act"><a class="btn sec sm" href="{{ route('charges.show', $p->id) }}">مشاهده</a></td></tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>
@endsection
