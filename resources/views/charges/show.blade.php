@php
  $F = \App\Support\Fa::class;
  $status = $period->status->value;
  $monthName = \App\Support\Fa::MONTHS[$period->jalali_month - 1].' '.$F::digits($period->jalali_year);
  $expTotal = (int) $expenses->sum('amount');
  $total = array_sum($amounts);
  $reason = ['missing_residents' => 'تعداد ساکنین ثبت نشده است'];
  $warnText = function (string $w) use ($F) {
    if (str_starts_with($w, 'undistributed:')) return 'مبلغ '.$F::money((int) substr($w, 14)).' قابل تقسیم نبود (مبنای تقسیم صفر است).';
    return ['fixed_exceeds_total' => 'مجموع مبلغ ثابت واحدها از کل هزینه‌ها بیشتر است؛ باقی‌مانده صفر در نظر گرفته شد.'][$w] ?? $w;
  };
@endphp
@extends('layouts.app')
@section('title', 'شارژ '.$monthName)
@section('content')
<div class="card">
  <div class="page-h">
    <h2>شارژ {{ $monthName }}
      <span class="pill {{ $status === 'draft' ? 'h' : '' }}">{{ ['draft' => 'پیش‌نویس', 'calculated' => 'محاسبه‌شده', 'published' => 'منتشرشده'][$status] }}</span></h2>
    <a class="btn sec sm" href="{{ route('charges.index') }}">همه‌ی دوره‌ها</a>
  </div>
  @if(! $setting)
    <div class="warnbox" style="margin-top:10px">تنظیمات شارژ انجام نشده است. <a href="{{ route('charge-settings.edit') }}">رفتن به تنظیمات</a></div>
  @else
    <div class="kv"><span>روش محاسبه</span><b>{{ $setting->method->label() }}</b></div>
    <div class="kv"><span>تاریخ محاسبه</span><b>{{ $F::date($period->calc_date, 'Y/m/d') }}</b></div>
    <div class="kv"><span>جمع هزینه‌های ثبت‌شده</span><b>{{ $F::money($expTotal) }} ({{ $F::num($expenses->count()) }} مورد)</b></div>
  @endif
  @if($expenses->isEmpty() && $setting && in_array($setting->method->value, [2, 3, 4, 7, 10], true))
    <div class="warnbox" style="margin-top:10px">برای این روش هزینه‌ها مبنای تقسیم هستند ولی هنوز هزینه‌ای ثبت نشده است. <a href="{{ route('expenses.index', ['year' => $period->jalali_year, 'month' => $period->jalali_month]) }}">ثبت هزینه</a></div>
  @endif
  <div class="fbar">
    @if($status !== 'published')
      <form method="POST" action="{{ route('charges.calculate', $period->id) }}">@csrf<button class="btn" type="submit">{{ $status === 'calculated' ? 'محاسبه‌ی مجدد' : 'محاسبه‌ی شارژ' }}</button></form>
    @endif
    <a class="btn sec" href="{{ route('expenses.index', ['year' => $period->jalali_year, 'month' => $period->jalali_month]) }}">هزینه‌های این ماه</a>
  </div>
</div>

@if($status !== 'draft' && $amounts !== [])
<div class="card">
  <div class="page-h"><h2>نتیجه‌ی محاسبه</h2><span class="pill">جمع شارژها: {{ $F::money($total) }}</span></div>
  @foreach($warnings as $w)<div class="warnbox" style="margin:8px 0">{{ $warnText($w) }}</div>@endforeach
  @if($skipped)
    <div class="warnbox" style="margin:8px 0"><b>{{ $F::num(count($skipped)) }} واحد محاسبه نشد:</b>
      @foreach($skipped as $uid => $why){{ $loop->first ? '' : '،' }} {{ isset($units[$uid]) ? $units[$uid]->block->name.'/'.$F::digits($units[$uid]->number) : $uid }} ({{ $reason[$why] ?? $why }})@endforeach
      — تعداد ساکنین را در ویرایش واحد ثبت کنید و دوباره محاسبه کنید.</div>
  @endif
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>بلوک</th><th>واحد</th><th>متراژ</th><th>ساکنین</th><th>شارژ</th></tr></thead>
    <tbody>
    @foreach($amounts as $uid => $amount)
      @php $u = $units[$uid] ?? null; @endphp
      @if($u)<tr><td>{{ $u->block->name }}</td><td><b>{{ $F::digits($u->number) }}</b></td><td>{{ $F::num($u->area_m2) }} م</td><td>{{ $F::digits($u->residents_count) }}</td><td><b>{{ $F::money($amount) }}</b></td></tr>@endif
    @endforeach
    <tr><td colspan="4"><b>جمع</b></td><td><b>{{ $F::money($total) }}</b></td></tr>
    @if($expTotal > 0 && $total !== $expTotal)
      <tr><td colspan="4" style="color:var(--muted)">اختلاف با جمع هزینه‌ها ({{ $F::money($expTotal) }})</td><td style="color:var(--muted)">{{ $total - $expTotal > 0 ? '+' : '−' }}{{ $F::money(abs($total - $expTotal)) }}</td></tr>
    @endif
    </tbody>
  </table></div>

  @if($status === 'calculated')
    <form method="POST" action="{{ route('charges.publish', $period->id) }}" data-confirm="با انتشار، صورت‌حساب شارژ برای واحدها صادر می‌شود. ادامه می‌دهید؟" data-confirm-title="انتشار شارژ">@csrf
      @if($skipped)<label style="display:flex;gap:8px;align-items:center;margin-top:12px;font-size:13px"><input type="checkbox" name="confirm_skipped" value="1" style="width:auto">بدون واحدهای محاسبه‌نشده منتشر شود</label>@endif
      <div class="fbar"><button class="btn" type="submit">انتشار و صدور صورت‌حساب</button></div>
    </form>
  @endif
</div>
@endif

@if($status === 'published' && $invoices)
<div class="card"><b>{{ $F::num($invoices->c) }} صورت‌حساب شارژ به مبلغ کل {{ $F::money($invoices->s) }} صادر شده است.</b></div>
@endif
@endsection
