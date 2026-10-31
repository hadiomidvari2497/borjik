@php $F = \App\Support\Fa::class; @endphp
@extends('layouts.app')
@section('title', 'تاریخچه تغییرات')
@section('content')
<div class="card">
  <div class="page-h"><h2>تاریخچه‌ی تغییرات</h2></div>
  <div class="h" style="margin-top:6px">هر ثبت، ویرایش و حذف اطلاعات این ساختمان همراه با فرد انجام‌دهنده و زمان آن نگهداری می‌شود.</div>


  @if(session('status'))<div class="alert success" role="status">{{ session('status') }}</div>@endif
  <details style="margin:14px 0;padding:12px;border:1px solid var(--line);border-radius:12px">
    <summary style="cursor:pointer;font-weight:800;color:#b42318">پاک‌سازی لاگ‌ها</summary>
    <form method="POST" action="{{ route('audit.clear') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;align-items:end;margin-top:14px">
      @csrf @method('DELETE')
      <div><label>محدوده پاک‌سازی</label><select name="scope" required><option value="all">تمام لاگ‌های این بخش</option><option value="range">فقط بازه تاریخی</option></select></div>
      <div><label>از تاریخ (شمسی)</label><input name="from" class="jdate" autocomplete="off"></div>
      <div><label>تا تاریخ (شمسی)</label><input name="to" class="jdate" autocomplete="off"></div>
      <div><label>برای تأیید، «حذف» را وارد کن</label><input name="confirmation" required pattern="حذف" placeholder="حذف"></div>
      <div><button class="btn" type="submit" style="background:#b42318;color:#fff">پاک‌سازی لاگ‌ها</button></div>
    </form>
    <p class="sd-muted" style="margin:9px 0 0">این عملیات برگشت‌پذیر نیست و فقط رکوردهای همین آرشیو را حذف می‌کند.</p>
  </details>
  <form class="filters" method="GET" style="margin-top:12px">
    <select name="model"><option value="">همه‌ی موجودیت‌ها</option>
      @foreach(config('audit.models') as $class => $cfg)<option value="{{ $class }}" @selected(request('model') === $class)>{{ $cfg['label'] }}</option>@endforeach
    </select>
    <select name="event"><option value="">همه‌ی رویدادها</option>
      @foreach(config('audit.events') as $k => $l)<option value="{{ $k }}" @selected(request('event') === $k)>{{ $l }}</option>@endforeach
    </select>
    <input name="id" inputmode="numeric" placeholder="شناسه‌ی رکورد" value="{{ request('id') }}" style="max-width:130px">
    <input name="from" class="jdate" data-max="today" placeholder="از تاریخ" value="{{ request('from') }}" style="max-width:130px">
    <input name="to" class="jdate" data-max="today" placeholder="تا تاریخ" value="{{ request('to') }}" style="max-width:130px">
    <button class="btn sec" type="submit">فیلتر</button>
  </form>

  @if($rows->isEmpty())
    <div class="empty">موردی پیدا نشد.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>زمان</th><th>توسط</th><th>موجودیت</th><th>رویداد</th><th>تغییرات</th></tr></thead>
    <tbody>
    @foreach($rows as $e)
      <tr>
        <td style="white-space:nowrap">{{ $e['time'] }}</td>
        <td>{{ $e['actor'] }}</td>
        <td>@if($e['url'])<a href="{{ $e['url'] }}"><b>{{ $e['entity'] }}</b></a>@else<b>{{ $e['entity'] }}</b>@endif</td>
        <td><span class="pill {{ $e['event'] === 'deleted' ? 'u' : ($e['event'] === 'created' ? '' : 'h') }}">{{ $e['event_label'] }}</span></td>
        <td>
          @foreach($e['changes'] as $c)
            <div><b>{{ $c['field'] }}:</b>
              @if($e['event'] === 'updated'){{ $c['old'] }} ← {{ $c['new'] }}@elseif($e['event'] === 'created'){{ $c['new'] }}@else{{ $c['old'] }}@endif
            </div>
          @endforeach
          @if($e['more'] > 0)<div style="color:var(--muted);font-size:12px">و {{ $F::num($e['more']) }} مورد دیگر</div>@endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $logs->links() }}
  @endif
</div>
@endsection
