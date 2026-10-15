@extends('layouts.app')
@section('title', 'دعوت‌نامه‌های من')
@section('content')
<div class="card">
  <div class="page-h">
    <h2>دعوت‌نامه‌های من</h2>
    <span class="pill">{{ \App\Support\Fa::num($invitations->count()) }} دعوت در انتظار پاسخ</span>
  </div>
  <p style="color:var(--muted);font-size:13px;margin:0">دعوت‌های مربوط به حساب شما را ببینید و برای هرکدام تصمیم بگیرید.</p>

  @if($invitations->isEmpty())
    <div class="empty" style="padding:34px 16px">
      <h3 style="margin:0 0 8px;color:var(--text)">دعوت‌نامه‌ی فعالی ندارید</h3>
      <p style="margin:0 0 18px;color:var(--muted)">اگر مدیری شما را به ساختمانی دعوت کند، دعوت‌نامه در این صفحه نمایش داده می‌شود.</p>
      <a class="btn" href="{{ route('dashboard') }}">بازگشت به برجیک</a>
    </div>
  @else
    <div style="display:grid;gap:14px;margin-top:18px">
      @foreach($invitations as $invitation)
        <article style="border:1px solid var(--line);border-radius:16px;padding:18px;background:var(--card)">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
              <h3 style="margin:0 0 8px;font-size:17px;font-weight:800">{{ $invitation->building?->name ?? 'ساختمان' }}</h3>
              <p style="color:var(--muted);font-size:13px;margin:0">نقش پیشنهادی: <b style="color:var(--text)">{{ $invitation->role?->name_fa ?? '—' }}</b></p>
              @if($invitation->createdBy?->display_name)
                <p style="color:var(--muted);font-size:13px;margin:6px 0 0">دعوت از طرف: {{ $invitation->createdBy->display_name }}</p>
              @endif
              <p style="color:var(--muted);font-size:13px;margin:6px 0 0">مهلت پاسخ تا {{ \App\Support\Fa::date($invitation->expires_at) }}</p>
            </div>
            <span class="pill h">در انتظار پاسخ</span>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
            <form method="POST" action="{{ route('invitations.accept', $invitation->id) }}">@csrf
              <button class="btn" type="submit">پذیرفتن دعوت</button>
            </form>
            <form method="POST" action="{{ route('invitations.reject', $invitation->id) }}" data-confirm="از رد این دعوت‌نامه مطمئن هستید؟" data-confirm-title="رد دعوت‌نامه" data-confirm-icon="warning">@csrf
              <button class="btn dng" type="submit">رد کردن</button>
            </form>
          </div>
        </article>
      @endforeach
    </div>
  @endif
</div>
@endsection
