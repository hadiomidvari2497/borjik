@extends('admin.layout')
@section('title', 'اختصاص اشتراک به ساختمان')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<div class="card">
  <div class="page-h">
    <h2>اختصاص اشتراک به ساختمان</h2>
    <a class="btn sec sm" href="{{ route('admin.subscriptions.index', request()->only('q')) }}">بازگشت به ساختمان‌ها</a>
  </div>
  <div class="card" style="background:var(--surface-2);margin:12px 0;padding:14px">
    <div style="font-size:12px;color:var(--muted);margin-bottom:6px">ساختمان انتخاب‌شده</div>
    <b>{{ $building->name }}</b>
    <div style="font-size:13px;color:var(--muted);margin-top:5px">کد ساختمان: {{ $F::num($building->code) }}</div>
    <div style="font-size:13px;margin-top:5px">مالک / سازنده: {{ $building->createdBy?->display_name ?: '—' }} · <span dir="ltr">{{ $building->createdBy?->mobile }}</span></div>
    @if($currentSubscription)
      <div style="margin-top:10px;font-size:13px;line-height:1.8">
        آخرین اشتراک: <b>{{ $currentSubscription->plan?->name ?? 'پلن حذف‌شده' }}</b>؛
        @if($currentSubscription->isActive())
          <span class="pill">فعال تا {{ $F::date($currentSubscription->expires_at) }}</span>
        @else
          <span class="pill u">فعال نیست</span>
        @endif
      </div>
    @else
      <div style="margin-top:8px;font-size:13px;color:var(--muted)">این ساختمان هنوز اشتراکی ندارد.</div>
    @endif
  </div>

  @if($plans->isEmpty())
    <div class="empty">هنوز هیچ پلنی تعریف نشده است. ابتدا از بخش «پلن‌های اشتراک» یک پلن بسازید.</div>
  @else
  <form method="POST" action="{{ route('admin.subscriptions.store') }}" style="margin-top:14px">
    @csrf
    <input type="hidden" name="building_id" value="{{ $building->id }}">
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
        <li>اشتراک از همین لحظه و فقط برای همین ساختمان فعال می‌شود.</li>
        <li>مدت اعتبار از تنظیمات پلن انتخاب‌شده محاسبه می‌شود.</li>
        <li>اشتراک قبلی همین ساختمان منقضی می‌شود؛ اشتراک سایر ساختمان‌های این مالک دست‌نخورده می‌ماند.</li>
        <li>این اقدام دستی است و پرداختی از درگاه دریافت نمی‌کند.</li>
      </ul>
    </div>
    <div class="fbar">
      <button class="btn" type="submit" data-confirm="اشتراک برای همین ساختمان فعال شود؟" data-confirm-title="تأیید اختصاص اشتراک" data-confirm-icon="warning">فعال‌سازی اشتراک ساختمان</button>
      <a class="btn sec" href="{{ route('admin.subscriptions.index', request()->only('q')) }}">انصراف</a>
    </div>
  </form>
  @endif
</div>
@endsection
