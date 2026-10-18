@extends('admin.layout')
@section('title', 'فعال‌سازی اشتراک کاربر')
@section('content')
@php $F = \\App\\Support\\Fa::class; @endphp
<div class="card">
  <div class="page-h">
    <h2>فعال‌سازی اشتراک</h2>
    <a class="btn sec sm" href="{{ route('admin.subscriptions.index', request()->only('q')) }}">بازگشت به کاربران</a>
  </div>
  <div class="card" style="background:var(--surface-2);margin:12px 0;padding:14px">
    <div style="font-size:12px;color:var(--muted);margin-bottom:6px">کاربر انتخاب‌شده</div>
    <b>{{ $user->display_name ?: 'بدون نام' }}</b>
    <div dir="ltr" style="text-align:right;margin-top:4px">{{ $user->mobile }}</div>
    @if($currentSubscription)
      <div style="margin-top:10px;font-size:13px;line-height:1.8">
        آخرین اشتراک: <b>{{ $currentSubscription->plan?->name ?? 'پلن حذف‌شده' }}</b>؛
        @if($currentSubscription->status === 'active' && $currentSubscription->expires_at?->isFuture())
          <span class="pill">فعال تا {{ $F::date($currentSubscription->expires_at) }}</span>
        @else
          <span class="pill u">فعال نیست</span>
        @endif
      </div>
    @else
      <div style="margin-top:8px;font-size:13px;color:var(--muted)">این کاربر هنوز سابقهٔ اشتراک ندارد.</div>
    @endif
  </div>

  @if($plans->isEmpty())
    <div class="empty">هنوز هیچ پلنی تعریف نشده است. ابتدا از بخش «پلن‌های اشتراک» یک پلن بسازید.</div>
  @else
  <form method="POST" action="{{ route('admin.subscriptions.store') }}" style="margin-top:14px">
    @csrf
    <input type="hidden" name="user_id" value="{{ $user->id }}">
    <input type="hidden" name="q" value="{{ request('q') }}">
    <div class="fld">
      <label for="plan_id">پلن موردنظر *</label>
      <select id="plan_id" name="plan_id" required>
        <option value="">انتخاب پلن</option>
        @foreach($plans as $plan)
          <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>
            {{ $plan->name }} — {{ $F::num($plan->units_limit) }} واحد، {{ $F::num($plan->duration_months) }} ماه، {{ (int) $plan->price === 0 ? 'رایگان' : $F::money($plan->price) }}{{ $plan->is_active ? '' : ' (غیرفعال برای خرید کاربران)' }}
          </option>
        @endforeach
      </select>
      @error('plan_id')<div class="e">{{ $message }}</div>@enderror
    </div>
    <div style="margin-top:14px;padding:12px;border:1px solid var(--border);border-radius:12px;font-size:13px;line-height:1.9">
      <b>قبل از تأیید:</b>
      <ul style="margin:6px 0 0;padding-right:20px">
        <li>اشتراک از همین لحظه فعال می‌شود و مدت آن از تنظیمات پلن گرفته می‌شود.</li>
        <li>اشتراک فعال قبلی کاربر منقضی می‌شود.</li>
        <li>ساختمان‌های متصل به اشتراک‌های قبلی به اشتراک جدید منتقل می‌شوند.</li>
        <li>این اقدام دستی است و پرداختی از درگاه دریافت نمی‌کند.</li>
      </ul>
    </div>
    <div class="fbar">
      <button class="btn" type="submit" data-confirm="اشتراک این کاربر با پلن انتخاب‌شده فعال شود؟ اشتراک فعال قبلی جایگزین می‌شود." data-confirm-title="تأیید فعال‌سازی" data-confirm-icon="warning">فعال‌سازی اشتراک</button>
      <a class="btn sec" href="{{ route('admin.subscriptions.index', request()->only('q')) }}">انصراف</a>
    </div>
  </form>
  @endif
</div>
@endsection
