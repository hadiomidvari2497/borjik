@php
  $F = \App\Support\Fa::class;
  $typeLabel = ['rent' => 'اجاره', 'sale' => 'فروش'];
  $c = fn (string $k) => $counts[$k] ?? 0;
@endphp
@extends('layouts.app')
@section('title', 'قراردادها')
@section('content')
<div class="card">
  <div class="page-h">
    <h2>قراردادهای فروش و اجاره</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn sec" href="{{ route('contracts.create', ['type' => 'sale']) }}">+ قرارداد فروش</a>
      <a class="btn" href="{{ route('contracts.create', ['type' => 'rent']) }}">+ قرارداد اجاره</a>
    </div>
  </div>

  <div class="stats" style="margin-top:12px">
    <div><small>اجاره‌ی فعال</small><b>{{ $F::num($c('rent.active')) }}</b></div>
    <div><small>اجاره‌ی پایان‌یافته</small><b>{{ $F::num($c('rent.ended')) }}</b></div>
    <div><small>فروش ثبت‌شده</small><b>{{ $F::num($c('sale.active') + $c('sale.ended')) }}</b></div>
  </div>

  <form class="filters" method="GET">
    <select name="type"><option value="">همه‌ی انواع</option>@foreach($typeLabel as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
    <select name="status"><option value="">همه‌ی وضعیت‌ها</option><option value="active" @selected(request('status') === 'active')>فعال</option><option value="ended" @selected(request('status') === 'ended')>پایان‌یافته</option></select>
    <input name="q" placeholder="شماره‌ی واحد، نام یا موبایل" value="{{ request('q') }}">
    <button class="btn sec" type="submit">فیلتر</button>
  </form>

  @if($contracts->isEmpty())
    <div class="empty">قراردادی پیدا نشد. برای شروع، یک قرارداد فروش یا اجاره ثبت کنید.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>شماره</th><th>نوع</th><th>واحد</th><th>طرف قرارداد</th><th>مبلغ</th><th>تاریخ عقد</th><th>پایان</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    @foreach($contracts as $ct)
      @php $rent = $ct->type->value === 'rent'; @endphp
      <tr>
        <td>{{ $F::digits($ct->id) }}</td>
        <td><span class="pill {{ $rent ? '' : 'h' }}">{{ $rent ? 'اجاره' : 'فروش' }}</span></td>
        <td><b>{{ $ct->unit->block->name }} / {{ $F::digits($ct->unit->number) }}</b></td>
        <td>{{ $ct->user->display_name }}</td>
        <td>@if($rent){{ $F::money($ct->monthly_rent) }} <span style="color:var(--muted);font-size:12px">/ ماه</span>@else{{ $F::money($ct->sale_price) }}@endif</td>
        <td>{{ $F::date($ct->signed_at, 'Y/m/d') }}</td>
        <td>@if($ct->ended_at){{ $F::date($ct->ended_at, 'Y/m/d') }}@elseif($ct->end_date){{ $F::date($ct->end_date, 'Y/m/d') }}@else—@endif</td>
        <td><span class="pill {{ $ct->status->value === 'active' ? '' : 'h' }}">{{ $ct->status->label() }}</span></td>
        <td class="act"><a class="btn sec sm" href="{{ route('contracts.show', $ct->id) }}">مشاهده</a></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $contracts->links() }}
  @endif
</div>
@endsection
