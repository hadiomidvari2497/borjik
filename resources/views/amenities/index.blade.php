@extends('layouts.app')
@section('title', $cfg['plural'])
@section('content')
@php $p = $cfg['prefix']; @endphp
<div class="card">
  <div class="page-h">
    <h2>{{ $cfg['plural'] }} ساختمان</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn sec" href="{{ route("{$p}.bulk") }}">ثبت گروهی</a>
      <a class="btn" href="{{ route("{$p}.create") }}">+ {{ $cfg['title'] }} جدید</a>
    </div>
  </div>
  <div class="quota" style="margin-top:6px;flex-wrap:wrap">
    <span>کل: {{ \App\Support\Fa::num($stats['total']) }}</span>
    <span>اختصاص‌یافته: {{ \App\Support\Fa::num($stats['assigned']) }}</span>
    <span>آزاد: {{ \App\Support\Fa::num($stats['total'] - $stats['assigned']) }}</span>
    @if($declared > 0 && $declared !== $stats['total'])
      <span class="pill h">در اطلاعات ساختمان {{ \App\Support\Fa::num($declared) }} {{ $cfg['title'] }} اعلام شده است</span>
    @endif
  </div>

  <form class="filters" method="GET">
    <select name="block_id"><option value="">همه‌ی بلوک‌ها</option>@foreach($blocks as $b)<option value="{{ $b->id }}" @selected((int) request('block_id') === $b->id)>{{ $b->name }}</option>@endforeach</select>
    <select name="status"><option value="">همه‌ی وضعیت‌ها</option><option value="free" @selected(request('status') === 'free')>آزاد</option><option value="assigned" @selected(request('status') === 'assigned')>اختصاص‌یافته</option></select>
    <button class="btn sec" type="submit">فیلتر</button>
  </form>

  @if($items->isEmpty())<div class="empty">هنوز {{ $cfg['title'] }}ی ثبت نشده است.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>شماره</th><th>بلوک</th><th>طبقه</th><th>وضعیت</th><th>واحد صاحب</th><th></th></tr></thead>
    <tbody>
    @foreach($items as $it)
      @php $open = $it->openAssignment; @endphp
      <tr>
        <td><b>{{ \App\Support\Fa::digits($it->number) }}</b></td>
        <td>{{ $it->block?->name ?? '—' }}</td>
        <td>{{ $it->floor !== null ? \App\Support\Fa::digits($it->floor) : '—' }}</td>
        <td><span class="pill {{ $open ? '' : 'h' }}">{{ $open ? 'اختصاص‌یافته' : 'آزاد' }}</span></td>
        <td>
          @if($open)
            {{ $open->unit->block->name }} · واحد {{ \App\Support\Fa::digits($open->unit->number) }}
          @else
            <form method="POST" action="{{ route("{$p}.assign", $it->id) }}" style="display:flex;gap:6px;align-items:center">@csrf
              <select name="unit_id" required aria-label="واحد" style="padding:6px 8px;border-radius:10px;border:1px solid var(--line);background:var(--card);font:inherit;color:inherit">
                <option value="">انتخاب واحد</option>
                @foreach($units as $u)<option value="{{ $u->id }}">{{ $u->block->name }} · {{ \App\Support\Fa::digits($u->number) }}</option>@endforeach
              </select>
              <button class="btn sm" type="submit">اختصاص</button>
            </form>
          @endif
        </td>
        <td class="act">
          @if($open)
            <form method="POST" action="{{ route("{$p}.release", $it->id) }}" onsubmit="return confirm('اختصاص این {{ $cfg['title'] }} لغو شود؟')">@csrf<button class="btn sec sm" type="submit">آزادسازی</button></form>
          @endif
          <a class="btn sec sm" href="{{ route("{$p}.edit", $it->id) }}">ویرایش</a>
          <form method="POST" action="{{ route("{$p}.destroy", $it->id) }}" onsubmit="return confirm('حذف شود؟')">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button></form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $items->links() }}
  @endif
</div>
@endsection
