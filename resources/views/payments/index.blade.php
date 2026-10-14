@php
  $F = \App\Support\Fa::class;
  $tabs = ['pending' => 'در انتظار تأیید', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'];
@endphp
@extends('layouts.app')
@section('title', 'پرداخت‌ها')
@section('content')
<div class="card">
  <div class="page-h"><h2>بررسی فیش‌های پرداخت</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      @foreach($tabs as $k => $label)
        <a class="btn {{ $status === $k ? '' : 'sec' }} sm" href="{{ route('payments.index', ['status' => $k]) }}">{{ $label }} ({{ $F::num($counts[$k] ?? 0) }})</a>
      @endforeach
    </div>
  </div>

  @if($receipts->isEmpty())<div class="empty">فیشی در این وضعیت وجود ندارد.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>تاریخ</th><th>واحد</th><th>پرداخت‌کننده</th><th>مبلغ</th><th>توضیحات</th><th>فیش</th><th></th></tr></thead>
    <tbody>
    @foreach($receipts as $r)
      <tr>
        <td>{{ $F::date($r->paid_at, 'Y/m/d') }}</td>
        <td>{{ $r->unit->block->name }} / {{ $F::digits($r->unit->number) }}</td>
        <td>{{ $r->user->display_name ?: $r->user->mobile }}</td>
        <td><b>{{ $F::money($r->amount) }}</b></td>
        <td style="max-width:200px">{{ $r->note }}@if($r->reject_reason)<br><span class="pill u">{{ $r->reject_reason }}</span>@endif</td>
        <td>@if(isset($files[$r->id]))<a class="btn sec sm" href="{{ route('files.show', $files[$r->id]) }}" target="_blank" rel="noopener">مشاهده</a>@endif</td>
        <td class="act">
          @if($r->status->value === 'pending')
            <form method="POST" action="{{ route('payments.approve', $r->id) }}" onsubmit="return confirm('فیش تأیید شود؟')">@csrf<button class="btn sm" type="submit">تأیید</button></form>
            <form method="POST" action="{{ route('payments.reject', $r->id) }}" style="display:flex;gap:6px" onsubmit="var r = prompt('دلیل رد فیش:'); if (!r) return false; this.reason.value = r; return true;">@csrf<input type="hidden" name="reason"><button class="btn dng sm" type="submit">رد</button></form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $receipts->links() }}
  @endif
</div>
@endsection
