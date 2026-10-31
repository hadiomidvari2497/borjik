@extends('admin.layout')
@section('title', 'اشتراک ساختمان‌ها')
@section('content')
@php $F = \\App\\Support\\Fa::class; @endphp
<div class="card">
  <div class="page-h">
    <h2>اشتراک ساختمان‌ها</h2>
    <span class="pill">{{ $F::num($buildings->total()) }} ساختمان</span>
  </div>
  <p style="color:var(--muted);font-size:13px;margin:0 0 14px;line-height:1.8">
    هر ساختمان اشتراک مستقل خودش را دارد. با فعال‌سازی یا تمدید اشتراک، فقط اشتراک همان ساختمان جایگزین می‌شود و وضعیت سایر ساختمان‌های مالک تغییری نمی‌کند.
  </p>
  <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="fgrid" style="align-items:end;margin-bottom:16px">
    <div class="fld full">
      <label for="q">جست‌وجوی ساختمان، کد ساختمان یا مالک</label>
      <input id="q" name="q" value="{{ $q }}" placeholder="نام ساختمان، کد یا شماره موبایل مالک">
    </div>
    <div class="fld"><button class="btn" type="submit">جست‌وجو</button>
      @if($q !== '') <a class="btn sec" href="{{ route('admin.subscriptions.index') }}">پاک‌کردن</a> @endif
    </div>
  </form>
  @if($buildings->isEmpty())
    <div class="empty">ساختمانی با این مشخصات پیدا نشد.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>ساختمان</th><th>کد</th><th>مالک / سازنده</th><th>پلن</th><th>وضعیت</th><th>اعتبار تا</th><th></th></tr></thead>
    <tbody>
    @foreach($buildings as $building)
      @php
        $sub = $building->subscription;
        $active = $sub && $sub->isActive();
      @endphp
      <tr>
        <td><b>{{ $building->name }}</b></td>
        <td>{{ $F::num($building->code) }}</td>
        <td>{{ $building->createdBy?->display_name ?: '—' }}<small style="display:block;color:var(--muted)" dir="ltr">{{ $building->createdBy?->mobile }}</small></td>
        <td>{{ $sub?->plan?->name ?? 'بدون اشتراک' }}</td>
        <td>@if($active)<span class="pill">فعال</span>@elseif($sub)<span class="pill u">منقضی / غیرفعال</span>@else<span class="pill u">ندارد</span>@endif</td>
        <td>{{ $sub?->expires_at ? $F::date($sub->expires_at) : '—' }}</td>
        <td class="act"><a class="btn sm" href="{{ route('admin.subscriptions.create', ['building_id' => $building->id, 'q' => $q]) }}">{{ $active ? 'تمدید / تغییر پلن' : 'اختصاص اشتراک' }}</a></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  <div style="margin-top:14px">{{ $buildings->links() }}</div>
  @endif
</div>
@endsection
