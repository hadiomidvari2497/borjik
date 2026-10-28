@extends('admin.layout')
@section('title', 'اتصالات و درگاه پرداخت')
@section('content')
<div class="card">
  <div class="page-h">
    <h2>اتصالات و تنظیمات درگاه پرداخت</h2>
    @if(!empty($gateway['active'])) <span class="pill">فعال در تنظیمات</span> @else <span class="pill u">غیرفعال</span> @endif
  </div>
  <p style="color:var(--muted);font-size:13px;line-height:1.9;margin:0 0 16px">
    این فرم مستقل از نام شرکت ارائه‌دهنده طراحی شده تا اطلاعات پذیرنده و کلیدهای سرویس را ثبت کنید. اتصال‌های پیاده‌سازی‌شده: زرین‌پال و بیت‌پی. ذخیره تنظیمات به‌تنهایی اتصال زنده را اثبات نمی‌کند؛ پیش از فعال‌سازی عمومی، پرداخت کنترل‌شده و تأیید تراکنش را آزمایش کنید.
  </p>

  <div class="card" style="margin-bottom:16px">
    <h3 style="margin-top:0">وضعیت اتصال</h3>
    <div class="fgrid">
      <div class="fld"><label>نام درگاه</label><div>{{ $gateway['provider_name'] ?? 'تنظیم نشده' }}</div></div>
      <div class="fld"><label>شناسه فنی</label><div dir="ltr">{{ $gateway['driver'] ?? 'not_configured' }}</div></div>
      <div class="fld"><label>محیط</label><div>{{ ($gateway['environment'] ?? 'sandbox') === 'production' ? 'عملیاتی' : 'آزمایشی' }}</div></div>
      <div class="fld"><label>کلید API</label><div>{{ $hasApiKey ? 'ذخیره شده (محرمانه)' : 'ثبت نشده' }}</div></div>
      <div class="fld"><label>کلید محرمانه</label><div>{{ $hasApiSecret ? 'ذخیره شده (محرمانه)' : 'ثبت نشده' }}</div></div>
      <div class="fld"><label>کلید وب‌هوک</label><div>{{ $hasWebhookSecret ? 'ذخیره شده (محرمانه)' : 'ثبت نشده' }}</div></div>
    </div>
  </div>

  <form method="POST" action="{{ route('admin.payment-gateway.update') }}">
    @csrf @method('PUT')
    <h3 style="margin:0 0 10px">مشخصات درگاه</h3>
    <div class="fgrid">
      <div class="fld full">
        <label for="gateway_template">قالب آماده درگاه</label>
        <select id="gateway_template" onchange="applyGatewayTemplate(this.value)">
          <option value="custom">تنظیم دستی / درگاه اختصاصی</option>
          <option value="zarinpal" @selected(($gateway['driver'] ?? '') === 'zarinpal')>زرین‌پال (Zarinpal)</option>
          <option value="bitpay" @selected(($gateway['driver'] ?? '') === 'bitpay')>بیت‌پی ایران (Bitpay)</option>
        </select>
        <small>زرین‌پال از شناسه پذیرنده استفاده می‌کند؛ بیت‌پی ایران از کد API در فیلد «کلید API» استفاده می‌کند و نیازی به شناسه پذیرنده جداگانه ندارد.</small>
      </div>
      <div class="fld">
        <label for="provider_name">نام نمایشی درگاه</label>
        <input id="provider_name" name="provider_name" value="{{ old('provider_name', $gateway['provider_name'] ?? '') }}" placeholder="مثلاً درگاه پرداخت شرکت موردنظر">
        @error('provider_name')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="driver">شناسه فنی (انگلیسی) *</label>
        <input id="driver" name="driver" dir="ltr" required value="{{ old('driver', $gateway['driver'] ?? 'custom') }}" placeholder="custom">
        <small>برای نمونه: custom یا نام کوتاه آداپتور اختصاصی</small>
        @error('driver')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="environment">محیط اجرا *</label>
        <select id="environment" name="environment" required>
          <option value="sandbox" @selected(old('environment', $gateway['environment'] ?? 'sandbox') === 'sandbox')>آزمایشی (Sandbox)</option>
          <option value="production" @selected(old('environment', $gateway['environment'] ?? 'sandbox') === 'production')>عملیاتی (Production)</option>
        </select>
      </div>
      <div class="fld">
        <label for="merchant_id">شناسه پذیرنده / ترمینال</label>
        <input id="merchant_id" name="merchant_id" dir="ltr" value="{{ old('merchant_id', $gateway['merchant_id'] ?? '') }}">
        @error('merchant_id')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld full">
        <label for="api_base_url">آدرس پایه API</label>
        <input id="api_base_url" name="api_base_url" dir="ltr" value="{{ old('api_base_url', $gateway['api_base_url'] ?? '') }}" placeholder="https://gateway.example/api">
        @error('api_base_url')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="api_key">کلید API {{ $hasApiKey ? '(برای حفظ مقدار فعلی خالی بگذارید)' : '' }}</label>
        <input id="api_key" name="api_key" type="password" dir="ltr" autocomplete="new-password">
        @if($hasApiKey)<label><input type="checkbox" name="clear_api_key" value="1" style="width:auto"> پاک کردن کلید ذخیره‌شده</label>@endif
        @error('api_key')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="api_secret">کلید محرمانه {{ $hasApiSecret ? '(برای حفظ مقدار فعلی خالی بگذارید)' : '' }}</label>
        <input id="api_secret" name="api_secret" type="password" dir="ltr" autocomplete="new-password">
        @if($hasApiSecret)<label><input type="checkbox" name="clear_api_secret" value="1" style="width:auto"> پاک کردن کلید ذخیره‌شده</label>@endif
        @error('api_secret')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="webhook_secret">کلید محرمانه وب‌هوک {{ $hasWebhookSecret ? '(برای حفظ مقدار فعلی خالی بگذارید)' : '' }}</label>
        <input id="webhook_secret" name="webhook_secret" type="password" dir="ltr" autocomplete="new-password">
        @if($hasWebhookSecret)<label><input type="checkbox" name="clear_webhook_secret" value="1" style="width:auto"> پاک کردن کلید ذخیره‌شده</label>@endif
        @error('webhook_secret')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld full">
        <label>آدرس بازگشت از درگاه (Callback URL)</label>
        <input readonly dir="ltr" value="{{ $callbackUrl }}" onclick="this.select()">
        <small>این نشانی را در تنظیمات درگاه تغییر ندهید؛ نتیجه پرداخت پس از بازگشت، از سمت سرور استعلام می‌شود.</small>
      </div>
      <div class="fld full">
        <label for="notes">یادداشت داخلی</label>
        <textarea id="notes" name="notes" rows="3">{{ old('notes', $gateway['notes'] ?? '') }}</textarea>
      </div>
      <div class="fld full">
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="active" value="1" style="width:auto" @checked(old('active', $gateway['active'] ?? false))> این اتصال در تنظیمات فعال باشد</label>
        @error('active')<div class="e">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ذخیره تنظیمات درگاه</button></div>
  </form>

  <script>
    function applyGatewayTemplate(template) {
      if (template === 'bitpay') {
        document.getElementById('provider_name').value = 'بیت‌پی';
        document.getElementById('driver').value = 'bitpay';
        document.getElementById('api_base_url').value = '';
      }
      if (template === 'zarinpal') {
        document.getElementById('provider_name').value = 'زرین‌پال';
        document.getElementById('driver').value = 'zarinpal';
        document.getElementById('api_base_url').value = document.getElementById('environment').value === 'sandbox'
          ? 'https://sandbox.zarinpal.com/pg/v4' : 'https://payment.zarinpal.com/pg/v4';
      }
    }
    document.getElementById('environment').addEventListener('change', function () {
      if (document.getElementById('driver').value === 'zarinpal') {
        document.getElementById('api_base_url').value = this.value === 'sandbox'
          ? 'https://sandbox.zarinpal.com/pg/v4' : 'https://payment.zarinpal.com/pg/v4';
      }
    });
  </script>
  <form method="POST" action="{{ route('admin.payment-gateway.validate') }}" style="margin-top:10px">
    @csrf
    <button class="btn sec" type="submit">بررسی کامل بودن تنظیمات</button>
  </form>
</div>
@endsection
