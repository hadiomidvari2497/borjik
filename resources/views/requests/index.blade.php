@extends('layouts.app')
@section('title', $canManage ? 'درخواست‌های ساکنین' : 'درخواست‌های ارجاع‌شده')
@section('content')
@php
  $priorityLabels = ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد', 'urgent' => 'فوری'];
  $categoryLabels = ['general' => 'عمومی', 'maintenance' => 'تعمیرات', 'cleaning' => 'نظافت', 'security' => 'حراست', 'facilities' => 'تأسیسات و مشاعات'];
  $statusClasses = ['new' => 'new', 'review' => 'review', 'doing' => 'doing', 'waiting' => 'waiting', 'done' => 'done', 'closed' => 'closed'];
  $priorityClasses = ['low' => 'low', 'medium' => 'medium', 'high' => 'high', 'urgent' => 'urgent'];
@endphp
<style>
.req-page{display:grid;gap:18px}.req-hero{position:relative;overflow:hidden;padding:26px;border-radius:24px;background:linear-gradient(115deg,#174d49 0%,#217f74 58%,#35a99a 100%);color:#fff;box-shadow:0 12px 28px rgba(24,103,94,.2)}.req-hero:after{content:"";position:absolute;width:230px;height:230px;border:1px solid rgba(255,255,255,.15);border-radius:50%;left:-50px;top:-120px;box-shadow:0 0 0 24px rgba(255,255,255,.04),0 0 0 50px rgba(255,255,255,.035)}.req-hero-inner{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;gap:18px}.req-kicker{font-size:11px;letter-spacing:.04em;opacity:.78;margin-bottom:8px}.req-hero h2{font-size:22px;margin:0 0 8px;font-weight:800}.req-hero p{font-size:13px;line-height:1.9;opacity:.86;max-width:600px}.req-hero .req-hero-action{display:inline-flex;align-items:center;gap:8px;flex:none;text-decoration:none;background:#fff;color:#17645c;border-radius:13px;padding:11px 15px;font-weight:800;box-shadow:0 5px 14px rgba(0,0,0,.12)}.req-hero .req-hero-action:hover{transform:translateY(-1px)}.req-list-card{padding:0;overflow:hidden}.req-list-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:20px 22px;border-bottom:1px solid var(--line)}.req-list-head h3{font-size:15px;margin:0 0 4px}.req-list-head p{font-size:12px;color:var(--muted)}.req-count{font-size:12px;font-weight:700;padding:7px 11px;border-radius:10px;background:var(--brand-soft);color:var(--brand-d);white-space:nowrap}.req-table-wrap{overflow-x:auto}.req-table{width:100%;border-collapse:separate;border-spacing:0;min-width:720px}.req-table th{padding:13px 18px;text-align:right;font-size:11px;color:var(--muted);font-weight:700;background:color-mix(in srgb,var(--track) 38%,transparent);white-space:nowrap}.req-table td{padding:15px 18px;border-top:1px solid var(--line);vertical-align:middle;font-size:12px}.req-table tbody tr{transition:background .15s}.req-table tbody tr:hover{background:var(--brand-soft)}.req-title{display:inline-block;color:var(--text);text-decoration:none;font-size:13px;font-weight:800;line-height:1.8}.req-title:hover{color:var(--brand-d);text-decoration:underline;text-underline-offset:4px}.req-meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:4px;color:var(--muted);font-size:10px}.req-id{font-weight:700;direction:ltr;unicode-bidi:isolate}.req-pill{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:9px;font-size:11px;font-weight:700;white-space:nowrap}.req-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.8}.req-status-new{background:rgba(59,130,196,.12);color:#4383bb}.req-status-review{background:rgba(232,163,61,.15);color:#b57b20}.req-status-doing{background:rgba(42,155,143,.15);color:#21877c}.req-status-waiting{background:rgba(145,120,190,.15);color:#9275bd}.req-status-done{background:rgba(42,155,143,.15);color:#21877c}.req-status-closed{background:var(--track);color:var(--muted)}.req-priority-urgent{background:rgba(224,69,58,.13);color:#d94c42}.req-priority-high{background:rgba(232,163,61,.15);color:#b57b20}.req-priority-medium{background:var(--track);color:var(--muted)}.req-priority-low{background:rgba(42,155,143,.12);color:#21877c}.req-owner{display:flex;align-items:center;gap:8px;min-width:130px}.req-owner-avatar{width:30px;height:30px;border-radius:10px;background:var(--brand-soft);color:var(--brand-d);display:grid;place-items:center;font-size:11px;font-weight:800;flex:none}.req-owner-name{font-size:11px;font-weight:700}.req-owner-sub{font-size:10px;color:var(--muted);margin-top:2px}.req-empty{padding:40px 20px;text-align:center}.req-empty-icon{width:54px;height:54px;border-radius:18px;display:grid;place-items:center;margin:0 auto 12px;background:var(--brand-soft);color:var(--brand-d);font-size:24px}.req-empty h3{margin-bottom:6px;font-size:14px}.req-empty p{font-size:12px;color:var(--muted)}.req-pagination{padding:16px 20px;border-top:1px solid var(--line)}@media(max-width:680px){.req-hero{padding:20px}.req-hero-inner{align-items:flex-start;flex-direction:column}.req-hero h2{font-size:19px}.req-hero .req-hero-action{width:100%;justify-content:center}.req-list-head{padding:16px;align-items:flex-start}.req-list-head h3{font-size:14px}.req-count{font-size:10px}.req-table{min-width:680px}.req-list-card{border-radius:18px}}
</style>
<div class="req-page">
  <section class="req-hero">
    <div class="req-hero-inner">
      <div>
        <div class="req-kicker">BORJIK · REQUEST CENTER</div>
        <h2>{{ $canManage ? 'همه‌چیز زیر نظر شماست.' : 'درخواست‌هایی که منتظر رسیدگی شما هستند.' }}</h2>
        <p>{{ $canManage ? 'درخواست‌های ساکنین را یک‌جا ببینید، مسئول رسیدگی را مشخص کنید و روند انجام کار را پیگیری کنید.' : 'درخواست را باز کنید، جزئیات و گفت‌وگو را ببینید و اگر هنوز مسئول ندارد، رسیدگی به آن را بپذیرید.' }}</p>
      </div>
      <a class="req-hero-action" href="{{ route('my.requests') }}"><span aria-hidden="true">＋</span> درخواست‌های من</a>
    </div>
  </section>
  <section class="card req-list-card">
    <div class="req-list-head">
      <div><h3>{{ $canManage ? 'درخواست‌های ساختمان' : 'صف رسیدگی من' }}</h3><p>برای دیدن جزئیات، گفت‌وگو و تاریخچه روی عنوان درخواست بزنید.</p></div>
      <span class="req-count">{{ \App\Support\Fa::num($tickets->total()) }} درخواست</span>
    </div>
    @if($tickets->isEmpty())
      <div class="req-empty"><div class="req-empty-icon">✓</div><h3>{{ $canManage ? 'فعلاً درخواستی ثبت نشده' : 'درخواستی در صف شما نیست' }}</h3><p>{{ $canManage ? 'وقتی ساکنی درخواستی ثبت کند، اینجا نمایش داده می‌شود.' : 'درخواست‌های جدید مرتبط با نقش شما در این بخش ظاهر می‌شوند.' }}</p></div>
    @else
      <div class="req-table-wrap"><table class="req-table">
        <thead><tr><th>درخواست</th><th>ثبت‌کننده</th><th>اولویت</th><th>وضعیت</th><th>مسئول رسیدگی</th></tr></thead>
        <tbody>
        @foreach($tickets as $ticket)
          @php
            $priority = $ticket->priority->value;
            $status = $ticket->status->value;
            $owner = $ticket->acceptedBy;
            $ownerName = $owner?->display_name ?: $owner?->mobile;
          @endphp
          <tr>
            <td>
              <a class="req-title" href="{{ route('requests.show', $ticket->id) }}">{{ $ticket->title }}</a>
              <div class="req-meta"><span class="req-id">{{ $ticket->number }}</span><span>·</span><span>{{ $ticket->created_at?->format('Y/m/d H:i') }}</span><span>·</span><span>{{ $categoryLabels[$ticket->category] ?? $ticket->category }}</span></div>
            </td>
            <td>{{ $ticket->createdBy?->display_name ?: $ticket->createdBy?->mobile ?: '—' }}</td>
            <td><span class="req-pill req-priority-{{ $priorityClasses[$priority] ?? 'medium' }}">{{ $priorityLabels[$priority] ?? $priority }}</span></td>
            <td><span class="req-pill req-status-{{ $statusClasses[$status] ?? 'new' }}">{{ $statuses[$status] ?? $status }}</span></td>
            <td><div class="req-owner"><div class="req-owner-avatar">{{ $owner ? mb_substr($ownerName, 0, 1) : '…' }}</div><div><div class="req-owner-name">{{ $ownerName ?: 'بدون مسئول' }}</div><div class="req-owner-sub">{{ $owner ? 'مسئول رسیدگی' : 'در انتظار قبول مسئولیت' }}</div></div></div></td>
          </tr>
        @endforeach
        </tbody>
      </table></div>
      <div class="req-pagination">{{ $tickets->links() }}</div>
    @endif
  </section>
</div>
@endsection
