@extends('layouts.app')
@section('title', $canManage ? 'درخواست‌های ساکنین' : 'درخواست‌های ارجاع‌شده')
@section('content')
@php $priorityLabels = ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد', 'urgent' => 'فوری']; @endphp
<div class="card">
  <div class="page-h"><h2>{{ $canManage ? 'درخواست‌های ساکنین ساختمان' : 'درخواست‌های ارجاع‌شده به من' }}</h2><a class="btn sec sm" href="{{ route('my.requests') }}">درخواست‌های من</a></div>
  @if($tickets->isEmpty())<div class="empty">{{ $canManage ? 'درخواستی برای این ساختمان ثبت نشده است.' : 'درخواستی به شما ارجاع نشده است.' }}</div>
  @else
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>شماره / عنوان</th><th>ثبت‌کننده</th><th>اولویت</th><th>وضعیت</th><th>مسئول</th><th></th></tr></thead><tbody>
  @foreach($tickets as $ticket)
    <tr><td><b>{{ $ticket->title }}</b><small style="display:block;color:var(--muted)">{{ $ticket->number }} · {{ $ticket->created_at?->format('Y/m/d H:i') }}</small></td>
    <td>{{ $ticket->createdBy?->display_name ?: $ticket->createdBy?->mobile }}</td><td>{{ $priorityLabels[$ticket->priority->value] ?? $ticket->priority->value }}</td>
    <td>{{ $statuses[$ticket->status->value] ?? $ticket->status->value }}</td><td>{{ $ticket->acceptedBy ? ($ticket->acceptedBy->display_name ?: $ticket->acceptedBy->mobile) : 'بدون مسئول' }}</td>
    <td><a class="btn sec sm" href="{{ route('requests.show', $ticket->id) }}">بررسی</a></td></tr>
  @endforeach
  </tbody></table></div><div style="margin-top:12px">{{ $tickets->links() }}</div>
  @endif
</div>
@endsection
