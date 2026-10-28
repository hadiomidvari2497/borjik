@extends('admin.layout')
@section('title', 'اتصالات و درگاه‌های پرداخت')
@section('content')
<style>
.gw-wrap{display:grid;grid-template-columns:minmax(220px,.7fr) minmax(0,1.7fr);gap:18px}.gw-panel{min-width:0;padding:20px;border:1px solid var(--line);border-radius:18px;background:var(--card)}.gw-list{display:grid;gap:9px}.gw-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px;border:1px solid var(--line);border-radius:13px;text-decoration:none;color:var(--text)}.gw-item.active{border-color:var(--brand);background:var(--brand-soft)}.gw-item strong{display:block;font-size:13px}.gw-item small{display:block;color:var(--muted);font-size:11px;margin-top:4px}.gw-pill{font-size:10px;font-weight:800;padding:4px 8px;border-radius:999px;background:var(--brand-soft);white-space:nowrap}.gw-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.gw-field{min-width:0}.gw-field.full{grid-column:1/-1}.gw-field label{display:block;font-size:12px;font-weight:800;margin-bottom:7px}.gw-field input:not([type=checkbox]),.gw-field select,.gw-field textarea{width:100%;min-width:0;box-sizing:border-box;padding:11px 12px;border:1px solid var(--line);border-radius:11px;background:var(--panel);color:var(--text);font:inherit;font-size:12px}.gw-field small{display:block;color:var(--muted);font-size:10px;line-height:1.8;margin-top:5px}.gw-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:18px}.gw-actions .danger{background:rgba(220,65,65,.1);color:var(--danger);border:1px solid rgba(220,65,65,.25)}.gw-help{color:var(--muted);font-size:12px;line-height:1.9}.gw-secret{display:flex;align-items:center;gap:7px;font-size:11px!important;font-weight:500!important;margin-top:7px}@media(max-width:800px){.gw-wrap{grid-template-columns:1fr}}@media(max-width:560px){.gw-grid{grid-template-columns:1fr}.gw-field.full{grid-column:auto}}
</style>
<div class="card">
  <div class="page-h"><h2>مدیریت چند درگاه پرداخت</h2><span class="pill">{{ count($gateways) }} درگاه تعریف‌شده</span></div>
  <p class="gw-help">می‌توانید چند اتصال مستقل تعریف کنید و هر کدام را جداگانه فعال یا غیرفعال کنید. کاربر در صفحه خرید، بین درگاه‌های فعال انتخاب می‌کند. کلیدهای محرمانه رمزگذاری می‌شوند و وضعیت پرداخت فقط پس از استعلام سمت سرور موفق ثبت خواهد شد.</p>
  @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
  @if(session('warning'))<div class="notice">{{ session('warning') }}</div>@endif
  @if(session('error'))<div class="notice error">{{ session('error') }}</div>@endif
  @if($errors->any())<div class="notice error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
  <div class="gw-wrap">
    <aside class="gw-panel">
      <h3 style="margin-top:0">درگاه‌های تعریف‌شده</h3>
      <div class="gw-list">
        @forelse($gateways as $id => $item)
          <a class="gw-item {{ (string)$gatewayId === (string)$id ? 'active' : '' }}" href="{{ route('admin.payment-gateway.edit', ['gateway_id' => $id]) }}">
            <div><strong>{{ $item['provider_name'] ?? $item['driver'] ?? 'درگاه پرداخت' }}</strong><small dir="ltr">{{ $item['driver'] ?? 'unknown' }}</small></div>
            <span class="gw-pill">{{ !empty($item['active']) ? 'فعال' : 'غیرفعال' }}</span>
          </a>
        @empty
          <p class="gw-help">هنوز درگاهی تعریف نشده است.</p>
        @endforelse
      </div>
      <div class="gw-actions"><a class="btn" href="{{ route('admin.payment-gateway.edit', ['new' => 1]) }}">+ افزودن درگاه جدید</a></div>
      <form method="POST" action="{{ route('admin.payment-gateway.validate') }}" style="margin-top:12px">@csrf<button class="btn sec" type="submit">بررسی درگاه‌های فعال</button></form>
    </aside>
    <section class="gw-panel">
      <h3 style="margin-top:0">{{ $gatewayId ? 'ویرایش درگاه' : 'تعریف درگاه جدید' }}</h3>
      <form method="POST" action="{{ $gatewayId ? route('admin.payment-gateway.update-one', ['gatewayId' => $gatewayId]) : route('admin.payment-gateway.store') }}">
        @csrf
        @if($gatewayId) @method('PUT') <input type="hidden" name="gateway_id" value="{{ $gatewayId }}"> @endif
        <div class="gw-grid">
          <div class="gw-field full"><label for="gateway_template">قالب درگاه</label><select id="gateway_template" onchange="applyGatewayTemplate(this.value)"><option value="">انتخاب قالب</option><option value="zarinpal" @selected(($gateway['driver'] ?? '') === 'zarinpal')>زرین‌پال</option><option value="bitpay" @selected(($gateway['driver'] ?? '') === 'bitpay')>بیت‌پی ایران</option><option value="custom" @selected(($gateway['driver'] ?? '') === 'custom')>درگاه اختصاصی</option></select></div>
          <div class="gw-field"><label for="provider_name">نام نمایشی *</label><input id="provider_name" name="provider_name" required value="{{ old('provider_name', $gateway['provider_name'] ?? '') }}" placeholder="مثلاً بیت‌پی اصلی"></div>
          <div class="gw-field"><label for="driver">شناسه فنی *</label><select id="driver" name="driver" required><option value="bitpay" @selected(($gateway['driver'] ?? '') === 'bitpay')>bitpay</option><option value="zarinpal" @selected(($gateway['driver'] ?? '') === 'zarinpal')>zarinpal</option><option value="custom" @selected(($gateway['driver'] ?? '') === 'custom')>custom (نیازمند آداپتور)</option></select></div>
          <div class="gw-field"><label for="environment">محیط *</label><select id="environment" name="environment" required><option value="production" @selected(($gateway['environment'] ?? 'production') === 'production')>عملیاتی</option><option value="sandbox" @selected(($gateway['environment'] ?? '') === 'sandbox')>آزمایشی</option></select></div>
          <div class="gw-field"><label for="merchant_id">شناسه پذیرنده</label><input id="merchant_id" name="merchant_id" dir="ltr" value="{{ $gateway['merchant_id'] ?? '' }}"></div>
          <div class="gw-field full"><label for="api_base_url">آدرس پایه API (اختیاری)</label><input id="api_base_url" name="api_base_url" dir="ltr" value="{{ $gateway['api_base_url'] ?? '' }}" placeholder="https://..."></div>
          <div class="gw-field"><label for="api_key">کلید API {{ $hasApiKey ? '(برای حفظ فعلی خالی بگذارید)' : '' }}</label><input id="api_key" name="api_key" type="password" dir="ltr" autocomplete="new-password">@if($hasApiKey)<label class="gw-secret"><input type="checkbox" name="clear_api_key" value="1"> پاک کردن کلید</label>@endif</div>
          <div class="gw-field"><label for="api_secret">کلید محرمانه {{ $hasApiSecret ? '(برای حفظ فعلی خالی بگذارید)' : '' }}</label><input id="api_secret" name="api_secret" type="password" dir="ltr" autocomplete="new-password">@if($hasApiSecret)<label class="gw-secret"><input type="checkbox" name="clear_api_secret" value="1"> پاک کردن کلید</label>@endif</div>
          <div class="gw-field"><label for="webhook_secret">کلید وب‌هوک {{ $hasWebhookSecret ? '(برای حفظ فعلی خالی بگذارید)' : '' }}</label><input id="webhook_secret" name="webhook_secret" type="password" dir="ltr" autocomplete="new-password">@if($hasWebhookSecret)<label class="gw-secret"><input type="checkbox" name="clear_webhook_secret" value="1"> پاک کردن کلید</label>@endif</div>
          <div class="gw-field full"><label for="notes">یادداشت داخلی</label><textarea id="notes" name="notes" rows="2">{{ $gateway['notes'] ?? '' }}</textarea></div>
          <div class="gw-field full"><label class="gw-secret"><input type="checkbox" name="active" value="1" @checked(old('active', $gateway['active'] ?? false))> این درگاه برای انتخاب کاربران فعال باشد</label></div>
          <div class="gw-field full"><label>Callback URL</label><input readonly dir="ltr" value="{{ $callbackUrl }}" onclick="this.select()"><small>بازگشت از درگاه با استعلام سمت سرور بررسی می‌شود.</small></div>
        </div>
        <div class="gw-actions"><button class="btn" type="submit">ذخیره درگاه</button><a class="btn sec" href="{{ route('admin.payment-gateway.edit') }}">بازگشت به فهرست</a></div>
      </form>
      @if($gatewayId)
        <form method="POST" action="{{ route('admin.payment-gateway.destroy', ['gatewayId' => $gatewayId]) }}" class="gw-actions" data-confirm="این درگاه از تنظیمات حذف شود؟ تراکنش‌های قبلی حفظ می‌شوند." data-confirm-title="حذف درگاه پرداخت">@csrf @method('DELETE')<button class="btn danger" type="submit">حذف درگاه</button></form>
      @endif
    </section>
  </div>
</div>
<script>
function applyGatewayTemplate(value){
  const name=document.getElementById('provider_name'), driver=document.getElementById('driver'), base=document.getElementById('api_base_url');
  if(value==='bitpay'){name.value='بیت‌پی';driver.value='bitpay';base.value='';}
  if(value==='zarinpal'){name.value='زرین‌پال';driver.value='zarinpal';base.value=document.getElementById('environment').value==='sandbox'?'https://sandbox.zarinpal.com/pg/v4':'https://payment.zarinpal.com/pg/v4';}
  if(value==='custom'){driver.value='custom';}
}
document.getElementById('environment')?.addEventListener('change',function(){if(document.getElementById('driver').value==='zarinpal')document.getElementById('api_base_url').value=this.value==='sandbox'?'https://sandbox.zarinpal.com/pg/v4':'https://payment.zarinpal.com/pg/v4';});
</script>
@endsection
