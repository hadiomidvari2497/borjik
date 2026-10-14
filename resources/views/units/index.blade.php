@php
  $st = ['sold' => ['فروخته‌شده', ''], 'rented' => ['اجاره‌داده‌شده', ''], 'vacant' => ['خالی', 'h'], 'repair' => ['در حال تعمیر', 'u']];
@endphp
@extends('layouts.app')
@section('title', 'واحدها')
@section('content')
<div class="card">
  <div class="page-h">
    <h2>واحدها</h2>
    @can('unit.manage')
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn sec" href="{{ route('units.bulk') }}">ساخت گروهی</a>
        <a class="btn" href="{{ route('units.create') }}">+ واحد جدید</a>
      </div>
    @endcan
  </div>
  @if($quota)
    <div class="quota" style="margin-top:6px"><span>سقف اشتراک: {{ \App\Support\Fa::num($quota['used']) }} از {{ \App\Support\Fa::num($quota['limit']) }} واحد</span>
      <div class="bar"><i style="width:{{ min(100, $quota['limit'] ? $quota['used'] / $quota['limit'] * 100 : 0) }}%"></i></div>
      @unless($quota['active'])<span class="pill u">اشتراک منقضی است</span>@endunless</div>
  @else
    <div class="warnbox" style="margin-top:8px">این ساختمان اشتراک فعالی ندارد.</div>
  @endif

  <form class="filters" method="GET">
    <select name="block_id"><option value="">همه‌ی بلوک‌ها</option>@foreach($blocks as $b)<option value="{{ $b->id }}" @selected((int) request('block_id') === $b->id)>{{ $b->name }}</option>@endforeach</select>
    <select name="status"><option value="">همه‌ی وضعیت‌ها</option>@foreach($st as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
    <input name="q" placeholder="شماره‌ی واحد" value="{{ request('q') }}">
    <button class="btn sec" type="submit">فیلتر</button>
  </form>

  @if($units->isEmpty())<div class="empty">واحدی پیدا نشد.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>بلوک</th><th>واحد</th><th>طبقه</th><th>متراژ</th><th>خواب</th><th>ساکنین</th><th>پارکینگ</th><th>انباری</th><th>وضعیت</th><th>وضعیت مالی</th><th></th></tr></thead>
    <tbody>
    @foreach($units as $u)
      <tr>
        <td>{{ $u->block->name }}</td><td><b>{{ \App\Support\Fa::digits($u->number) }}</b></td>
        <td>{{ \App\Support\Fa::digits($u->floor) }}</td><td>{{ \App\Support\Fa::num($u->area_m2) }} م</td>
        <td>{{ $u->bedrooms !== null ? \App\Support\Fa::digits($u->bedrooms) : '—' }}</td>
        <td>{{ \App\Support\Fa::digits($u->residents_count) }}</td>
        <td>{{ $u->parkings_count ? \App\Support\Fa::digits($u->parkings_count) : '—' }}</td>
        <td>{{ $u->storages_count ? \App\Support\Fa::digits($u->storages_count) : '—' }}</td>
        <td><span class="pill {{ $st[$u->status->value][1] }}">{{ $st[$u->status->value][0] }}</span></td>
        @php $bal = $balances[$u->id] ?? 0; @endphp
        <td>@if($bal < 0)<span class="pill u">بدهکار {{ \App\Support\Fa::num(abs($bal)) }}</span>@elseif($bal > 0)<span class="pill">بستانکار {{ \App\Support\Fa::num($bal) }}</span>@else<span class="pill">تسویه</span>@endif</td>
        <td class="act">
          @can('unit.manage')
            <a class="btn sec sm" href="{{ route('units.edit', $u) }}">ویرایش</a>
            <form method="POST" action="{{ route('units.destroy', $u) }}" onsubmit="return confirm('این واحد حذف شود؟')">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button></form>
          @endcan
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $units->links() }}
  @endif
</div>
@endsection
