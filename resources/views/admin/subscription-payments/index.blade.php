@extends('admin.layout')
@section('title', 'گزارش پرداخت اشتراک‌ها')
@section('content')
@php
  $F = \App\Support\Fa::class;
  $statusLabels = ['paid' => 'پرداخت موفق', 'pending' => 'در انتظار پرداخت', 'failed' => 'ناموفق'];
  $gatewayLabels = ['zarinpal' => 'زرین‌پال'];
  $subscriptionStatusLabels = ['active' => 'فعال', 'pending' => 'در انتظار', 'expired' => 'منقضی‌شده', 'failed' => 'ناموفق'];
@endphp

<div class="card">
  <div class="page-h">
    <div>
      <h2>گزارش پرداخت و تاریخچه اشتراک‌ها</h2>
      <p style="color:var(--muted);font-size:13px;line-height:1.9;margin:7px 0 0">
        گزارش تراکنش‌های خرید پلن، درآمد پرداخت‌های تأییدشده و تاریخچه فعال‌سازی اشتراک کاربران.
      </p>
    </div>
    <a class="btn sec sm" href="{{ route('admin.subscription-payments.export', request()->query()) }}">خروجی CSV تراکنش‌ها</a>
  </div>

  <form method="GET" action="{{ route('admin.subscription-payments.index') }}" class="fgrid" style="grid-template-columns:repeat(3,minmax(0,1fr));align-items:end;margin-top:18px">
    <div class="fld"><label for="from">از تاریخ شمسی</label><input id="from" name="from" class="jdate" data-clearable="1" autocomplete="off" placeholder="۱۴۰۵/۰۷/۰۱" value="{{ $filters['from_input'] }}"></div>
    <div class="fld"><label for="to">تا تاریخ شمسی</label><input id="to" name="to" class="jdate" data-clearable="1" autocomplete="off" placeholder="۱۴۰۵/۰۷/۳۰" value="{{ $filters['to_input'] }}"></div>
    <div class="fld"><label for="q">جست‌وجوی کاربر یا تراکنش</label><input id="q" name="q" value="{{ $filters['q'] }}" placeholder="نام، موبایل یا شماره پیگیری"></div>
    <div class="fld"><label for="status">وضعیت پرداخت</label>
      <select id="status" name="status">
        <option value="">همه وضعیت‌ها</option>
        @foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach
      </select>
    </div>
    <div class="fld"><label for="gateway">درگاه پرداخت</label>
      <select id="gateway" name="gateway"><option value="">همه درگاه‌ها</option>
        @foreach($gateways as $gateway)<option value="{{ $gateway }}" @selected($filters['gateway'] === $gateway)>{{ $gatewayLabels[$gateway] ?? $gateway }}</option>@endforeach
      </select>
    </div>
    <div class="fld" style="display:flex;gap:8px;align-items:end">
      <button class="btn" type="submit">اعمال فیلتر</button>
      <a class="btn sec" href="{{ route('admin.subscription-payments.index') }}">پاک‌کردن</a>
    </div>
  </form>
</div>

<div class="grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px">
  <div class="card" style="margin:0"><div style="color:var(--muted);font-size:12px">کل تراکنش‌ها</div><div style="font-size:25px;font-weight:800;margin-top:9px">{{ $F::num($summary->total_count) }}</div><div style="color:var(--muted);font-size:11px;margin-top:5px">مطابق فیلترها</div></div>
  <div class="card" style="margin:0"><div style="color:var(--muted);font-size:12px">پرداخت‌های موفق</div><div style="font-size:25px;font-weight:800;margin-top:9px">{{ $F::num($summary->paid_count) }}</div><div style="color:var(--muted);font-size:11px;margin-top:5px">تأییدشده توسط درگاه</div></div>
  <div class="card" style="margin:0"><div style="color:var(--muted);font-size:12px">درآمد پرداخت‌شده</div><div style="font-size:clamp(18px,2vw,25px);font-weight:800;margin-top:9px">{{ $F::money($summary->paid_amount) }}</div><div style="color:var(--muted);font-size:11px;margin-top:5px">فقط تراکنش‌های موفق</div></div>
  <div class="card" style="margin:0"><div style="color:var(--muted);font-size:12px">در انتظار پرداخت</div><div style="font-size:25px;font-weight:800;margin-top:9px">{{ $F::num($summary->pending_count) }}</div><div style="color:var(--muted);font-size:11px;margin-top:5px">مبلغ در انتظار: {{ $F::money($summary->pending_amount) }}</div></div>
</div>

<div class="card">
  <div class="page-h"><h2>گزارش روزانه تراکنش‌ها</h2><span class="pill">{{ $F::num($daily->count()) }} روز</span></div>
  @if($daily->isEmpty())
    <div class="empty">برای این بازه تراکنشی ثبت نشده است.</div>
  @else
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>تاریخ</th><th>تعداد تراکنش</th><th>موفق</th><th>ناموفق</th><th>درآمد موفق</th></tr></thead>
      <tbody>
      @foreach($daily as $day)
        <tr>
          <td style="white-space:nowrap">{{ $F::date(\Carbon\Carbon::parse($day->day), 'j F Y') }}</td>
          <td>{{ $F::num($day->total) }}</td><td>{{ $F::num($day->paid_count) }}</td><td>{{ $F::num($day->failed_count) }}</td>
          <td><b>{{ $F::money($day->revenue) }}</b></td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    <p style="color:var(--muted);font-size:11.5px;margin:10px 0 0">حداکثر ۳۱ روز اخیر در بازهٔ انتخاب‌شده؛ مبلغ درآمد فقط از پرداخت‌های موفق محاسبه شده است.</p>
  @endif
</div>

<div class="card">
  <div class="page-h"><h2>تراکنش‌های پرداخت</h2><span class="pill">{{ $F::num($payments->total()) }} مورد</span></div>
  @if($payments->isEmpty())
    <div class="empty">تراکنشی با این فیلترها پیدا نشد.</div>
  @else
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>شناسه / تاریخ</th><th>کاربر</th><th>پلن</th><th>مبلغ پرداختی</th><th>تخفیف</th><th>کد تخفیف</th><th>درگاه</th><th>وضعیت</th><th>شماره پیگیری</th></tr></thead>
      <tbody>
      @foreach($payments as $payment)
        <tr>
          <td style="white-space:nowrap"><b>#{{ $F::num($payment->id) }}</b><div style="color:var(--muted);font-size:11.5px;margin-top:4px">{{ $payment->created_at ? $F::date($payment->created_at, 'j F Y') : '—' }} @if($payment->created_at) · {{ $F::digits($payment->created_at->format('H:i')) }} @endif</div>
            @if($payment->paid_at)<div style="color:var(--muted);font-size:11px">پرداخت: {{ $F::date($payment->paid_at, 'j F Y') }} {{ $F::digits($payment->paid_at->format('H:i')) }}</div>@endif
          </td>
          <td><b>{{ $payment->user?->display_name ?: 'کاربر حذف‌شده' }}</b><div dir="ltr" style="text-align:right;color:var(--muted);font-size:11.5px">{{ $payment->user?->mobile ?? '—' }}</div></td>
          <td>{{ $payment->subscription?->plan?->name ?? 'اشتراک نامشخص' }}</td>
          <td style="white-space:nowrap"><b>{{ $F::money($payment->amount) }}</b>
            @if((int) data_get($payment->raw_response, 'discount_amount', 0) > 0)
              <div style="color:var(--muted);font-size:11px">قبل از تخفیف: {{ $F::money(data_get($payment->raw_response, 'base_amount', $payment->amount)) }}</div>
            @endif
          </td>
          <td>{{ (int) data_get($payment->raw_response, 'discount_amount', 0) > 0 ? $F::money(data_get($payment->raw_response, 'discount_amount')) : '—' }}</td>
          <td>{{ data_get($payment->raw_response, 'coupon_code') ?: '—' }}</td>
          <td>{{ $gatewayLabels[$payment->gateway] ?? $payment->gateway }}</td>
          <td><span class="pill {{ ['paid' => '', 'pending' => 'h', 'failed' => 'u'][$payment->status] ?? 'h' }}">{{ $statusLabels[$payment->status] ?? $payment->status }}</span></td>
          <td dir="ltr" style="text-align:right;font-size:12px">{{ $payment->ref_id ?: '—' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    <div style="margin-top:14px">{{ $payments->links() }}</div>
  @endif
</div>

<div class="card">
  <div class="page-h"><h2>تاریخچه فعال‌سازی پلن‌ها</h2><span class="pill">{{ $F::num($subscriptions->total()) }} سابقه</span></div>
  <p style="color:var(--muted);font-size:12px;line-height:1.8;margin:0 0 12px">این بخش تمام سوابق اشتراک را نشان می‌دهد؛ از جمله پلن رایگان و فعال‌سازی دستی مدیر که الزاماً تراکنش مالی ندارند.</p>
  @if($subscriptions->isEmpty())
    <div class="empty">سابقه‌ای برای این فیلترها پیدا نشد.</div>
  @else
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>کاربر</th><th>پلن</th><th>سقف واحد</th><th>شروع</th><th>پایان اعتبار</th><th>وضعیت</th><th>تاریخ ثبت</th></tr></thead>
      <tbody>
      @foreach($subscriptions as $subscription)
        @php
          $subStatus = $subscription->status;
          if ($subStatus === 'active' && $subscription->expires_at && $subscription->expires_at->isPast()) $subStatus = 'expired';
        @endphp
        <tr>
          <td><b>{{ $subscription->user?->display_name ?: 'کاربر حذف‌شده' }}</b><div dir="ltr" style="text-align:right;color:var(--muted);font-size:11.5px">{{ $subscription->user?->mobile ?? '—' }}</div></td>
          <td>{{ $subscription->plan?->name ?? 'پلن حذف‌شده' }}</td>
          <td>{{ $F::num($subscription->units_limit) }}</td>
          <td style="white-space:nowrap">{{ $F::date($subscription->starts_at, 'j F Y') }}</td>
          <td style="white-space:nowrap">{{ $F::date($subscription->expires_at, 'j F Y') }}</td>
          <td><span class="pill {{ $subStatus === 'active' ? '' : ($subStatus === 'pending' ? 'h' : 'u') }}">{{ $subscriptionStatusLabels[$subStatus] ?? $subStatus }}</span></td>
          <td style="white-space:nowrap">{{ $F::date($subscription->created_at, 'j F Y') }}</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    <div style="margin-top:14px">{{ $subscriptions->links() }}</div>
  @endif
</div>

<style>
@media (max-width: 900px) {
  .grid { grid-template-columns: repeat(2,minmax(0,1fr)) !important; }
}
@media (max-width: 560px) {
  .grid { grid-template-columns: minmax(0,1fr) !important; }
  form.fgrid { grid-template-columns: minmax(0,1fr) !important; }
}
</style>
@endsection
