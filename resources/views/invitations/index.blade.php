@extends('layouts.guest')
@section('title', 'دعوت‌نامه‌های من | برجیک')
@section('wide', 'wide')
@section('content')
  <h1 class="h1">دعوت‌نامه‌های من</h1>
  <p class="lead">دعوت‌های مربوط به حساب شما را ببینید و برای هرکدام تصمیم بگیرید.</p>

  @if($invitations->isEmpty())
    <div class="card" style="padding:24px;text-align:center;margin-top:20px">
      <h2 style="margin:0 0 8px">دعوت‌نامه‌ی فعالی ندارید</h2>
      <p class="lead" style="margin:0 0 18px">اگر مدیری شما را به ساختمانی دعوت کند، دعوت‌نامه در این صفحه نمایش داده می‌شود.</p>
      <a class="btn" href="{{ route('dashboard') }}">بازگشت به برجیک</a>
    </div>
  @else
    <div style="display:grid;gap:14px;margin-top:20px">
      @foreach($invitations as $invitation)
        <article class="card" style="border:1px solid var(--line);border-radius:16px;padding:18px">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
              <h2 style="margin:0 0 6px;font-size:18px">{{ $invitation->building?->name ?? 'ساختمان' }}</h2>
              <p class="lead" style="margin:0">نقش پیشنهادی: <b>{{ $invitation->role?->name_fa ?? '—' }}</b></p>
              @if($invitation->createdBy?->display_name)
                <p class="lead" style="margin:6px 0 0">دعوت از طرف: {{ $invitation->createdBy->display_name }}</p>
              @endif
              <p class="lead" style="margin:6px 0 0">مهلت پاسخ تا {{ \App\Support\Fa::date($invitation->expires_at) }}</p>
            </div>
            <span class="pill">در انتظار پاسخ</span>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
            <form method="POST" action="{{ route('invitations.accept', $invitation->id) }}">@csrf
              <button class="btn" type="submit">پذیرفتن دعوت</button>
            </form>
            <form method="POST" action="{{ route('invitations.reject', $invitation->id) }}" data-confirm="از رد این دعوت‌نامه مطمئن هستید؟" data-confirm-title="رد دعوت‌نامه" data-confirm-icon="warning">@csrf
              <button class="btn sec" type="submit">رد کردن</button>
            </form>
          </div>
        </article>
      @endforeach
    </div>
  @endif
@endsection
