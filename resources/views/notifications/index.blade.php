@php
  $F = \App\Support\Fa::class;
  $when = function ($n) use ($F) {
      $t = $n->created_at->copy()->timezone('Asia/Tehran');
      return $F::date($t, 'Y/m/d').' '.$F::digits($t->format('H:i'));
  };
  $glyph = ['success' => '✓', 'warning' => '!', 'info' => 'i'];
@endphp
@extends('layouts.app')
@section('title', 'اعلان‌ها')
@section('content')
<style>
.notice-page{display:grid;gap:16px}.notice-hero{padding:22px 24px;border-radius:20px;background:linear-gradient(115deg,#174d49,#25877b);color:#fff}.notice-hero h2{font-size:20px;margin:0 0 7px}.notice-hero p{font-size:12px;opacity:.85;margin:0}.notice-bar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between}.notice-list{padding:0;overflow:hidden}.notice-item{display:flex;align-items:flex-start;gap:13px;padding:17px 20px;border-bottom:1px solid var(--line)}.notice-item:last-child{border-bottom:0}.notice-dot{width:38px;height:38px;display:grid;place-items:center;border-radius:13px;background:var(--brand-soft);color:var(--brand-d);flex:none;font-weight:800}.notice-dot.warning{background:rgba(232,163,61,.18);color:#b9770e}.notice-dot.info{background:rgba(59,130,196,.15);color:#2b6aa5}.notice-copy{flex:1;min-width:0}.notice-copy b{display:block;font-size:13px;margin-bottom:5px}.notice-copy p{font-size:12px;line-height:1.8;color:var(--muted);margin:0 0 7px}.notice-copy small{font-size:10px;color:var(--muted)}.notice-link{display:inline-flex;text-decoration:none;font-size:11px;font-weight:800;color:var(--brand-d);margin-top:7px;border:0;background:transparent;padding:0;cursor:pointer;font-family:inherit}.notice-unread{background:color-mix(in srgb,var(--brand-soft) 35%,transparent)}.notice-empty{padding:45px 20px;text-align:center;color:var(--muted);font-size:13px}.notice-new{font-size:10px;font-weight:800;color:var(--brand-d);background:var(--brand-soft);padding:5px 8px;border-radius:8px}
@media(max-width:600px){.notice-hero{padding:18px}.notice-item{padding:14px;gap:10px}.notice-dot{width:34px;height:34px}}
</style>
<div class="notice-page">
  <section class="notice-hero">
    <h2>اعلان‌های شما</h2>
    <p>صورت‌حساب‌ها، پرداخت‌ها، قراردادها، درخواست‌ها و یادآوری‌های همین ساختمان در این بخش نمایش داده می‌شود.</p>
  </section>

  <div class="notice-bar">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn {{ $filter === 'all' ? '' : 'sec' }} sm" href="{{ route('notifications.index') }}">همه</a>
      <a class="btn {{ $filter === 'unread' ? '' : 'sec' }} sm" href="{{ route('notifications.index', ['filter' => 'unread']) }}">خوانده‌نشده @if($unreadCount > 0)({{ $F::num($unreadCount) }})@endif</a>
    </div>
    @if($unreadCount > 0)
      <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
        <input type="hidden" name="filter" value="{{ $filter === 'unread' ? 'unread' : '' }}">
        <button class="btn sec sm" type="submit">همه را خوانده‌شده کن</button>
      </form>
    @endif
  </div>

  <section class="card notice-list">
    @forelse($notifications as $notification)
      @php
        $data = $notification->data ?? [];
        $level = $data['level'] ?? (($data['event'] ?? '') === 'overdue' ? 'warning' : 'success');
        $label = $data['link_label'] ?? (isset($data['ticket_id']) ? 'مشاهده درخواست' : 'مشاهده');
      @endphp
      <article class="notice-item {{ $notification->read_at ? '' : 'notice-unread' }}">
        <div class="notice-dot {{ $level }}" aria-hidden="true">{{ $glyph[$level] ?? '✓' }}</div>
        <div class="notice-copy">
          <b>{{ $data['title'] ?? 'اعلان جدید' }}</b>
          <p>{{ $data['message'] ?? '' }}</p>
          <small>{{ $when($notification) }}</small>
          @if(!empty($data['url']))
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="notice-link" type="submit">{{ $label }} ←</button></form>
          @elseif(!$notification->read_at)
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="notice-link" type="submit">خواندم</button></form>
          @endif
        </div>
        @if(!$notification->read_at)<span class="notice-new">جدید</span>@endif
      </article>
    @empty
      <div class="notice-empty">{{ $filter === 'unread' ? 'اعلان خوانده‌نشده‌ای ندارید.' : 'اعلانی برای این ساختمان ندارید.' }}</div>
    @endforelse
    @if($notifications->hasPages())
      <div class="req-pagination">{{ $notifications->links() }}</div>
    @endif
  </section>
</div>
@endsection
