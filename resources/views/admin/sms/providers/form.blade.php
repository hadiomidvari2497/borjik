@extends('admin.layout')
@section('title', $provider ? 'ویرایش اتصال پیامک' : 'اتصال پیامک جدید')
@section('content')
@php
  $v = fn ($key, $default = '') => old($key, $default);
  $headers = old('headers', $s['headers']);
  $params = old('params', $s['params']);
  $editing = (bool) $provider;
@endphp
<form method="POST" action="{{ $provider ? route('admin.sms.providers.update', $provider->id) : route('admin.sms.providers.store') }}" autocomplete="off" id="prov-form">
  @csrf
  @if($provider) @method('PUT') @endif

  <div class="card">
    <div class="page-h"><h2>{{ $provider ? 'ویرایش «'.$provider->name.'»' : 'اتصال جدید' }}</h2><a class="btn sec sm" href="{{ route('admin.sms.providers.index') }}">بازگشت</a></div>

    @if(! $editing)
    <div class="fld" style="margin:6px 0 14px">
      <label for="preset">شروع سریع با نمونهٔ آماده</label>
      <select id="preset">
        <option value="">اتصال دلخواه (خالی)</option>
        @foreach($presets as $key => $p)<option value="{{ $key }}">{{ $p['label'] }}</option>@endforeach
      </select>
      <div class="h" id="preset-note" style="line-height:1.8">با انتخاب نمونه، فرم زیر پر می‌شود؛ فقط کلید و شمارهٔ خط خودتان را وارد کنید. مشخصات همهٔ اپراتورها را می‌توانید خودتان هم بنویسید.</div>
    </div>
    @endif

    <div class="fgrid">
      <div class="fld">
        <label for="name">نام اتصال *</label>
        <input id="name" name="name" value="{{ $v('name', $provider?->name) }}" required>
        @error('name')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label style="display:flex;gap:8px;align-items:center;margin-top:30px;color:inherit"><input type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $provider ? $provider->is_active : true))> فعال</label>
        <label style="display:flex;gap:8px;align-items:center;margin-top:8px;color:inherit"><input type="checkbox" name="is_default" value="1" style="width:auto" @checked(old('is_default', $provider?->is_default ?? false))> پیش‌فرض (پیامک‌های سامانه از این اتصال ارسال شود)</label>
        @error('is_default')<div class="e">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="page-h"><h2>درخواست HTTP به وب‌سرویس اپراتور</h2></div>
    <p class="hdr-sub">
      در آدرس، هدرها و بدنه می‌توانید از متغیرها استفاده کنید:
      <code dir="ltr">@{{mobile}}</code> شماره (طبق «قالب شماره»)،
      <code dir="ltr">@{{message}}</code> متن پیام،
      و هر پارامتری که پایین‌تر تعریف می‌کنید، مثل <code dir="ltr">@{{api_key}}</code>.
    </p>
    <div class="fgrid">
      <div class="fld">
        <label for="method">روش</label>
        <select id="method" name="method">
          @foreach(['POST', 'GET'] as $m)<option value="{{ $m }}" @selected($v('method', $s['method']) === $m)>{{ $m }}</option>@endforeach
        </select>
        @error('method')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="number_format">قالب شماره‌ی موبایل در <code dir="ltr">@{{mobile}}</code></label>
        <select id="number_format" name="number_format">
          @foreach(['local' => '09123456789', 'intl' => '989123456789', 'plus' => '+989123456789'] as $k => $label)
            <option value="{{ $k }}" @selected($v('number_format', $s['number_format']) === $k)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="fld full">
        <label for="url">آدرس (URL) *</label>
        <input id="url" name="url" dir="ltr" value="{{ $v('url', $s['url']) }}" placeholder="https://api.example.com/v1/sms/send" required>
        @error('url')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="body_type">نوع بدنه</label>
        <select id="body_type" name="body_type">
          @foreach(['none' => 'بدون بدنه', 'form' => 'فرم (key=value در هر خط؛ در GET به‌صورت کوئری)', 'json' => 'JSON', 'raw' => 'متن خام'] as $k => $label)
            <option value="{{ $k }}" @selected($v('body_type', $s['body_type']) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('body_type')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="timeout">مهلت پاسخ (ثانیه)</label>
        <input id="timeout" name="timeout" type="number" min="1" max="60" dir="ltr" value="{{ $v('timeout', $s['timeout']) }}">
        @error('timeout')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld full">
        <label for="body">قالب بدنه</label>
        <textarea id="body" name="body" rows="6" dir="ltr" style="font-family:monospace;font-size:13px;text-align:left" placeholder="receptor=@{{mobile}}&#10;message=@{{message}}">{{ $v('body', $s['body']) }}</textarea>
        <div class="h">برای «فرم»: هر خط <code dir="ltr">key=value</code>. برای «JSON»: متغیرهای متنی را داخل "..." بگذارید؛ نویسه‌های خاص خودکار escape می‌شوند.</div>
        @error('body')<div class="e">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="page-h"><h2>هدرها</h2><button class="btn sec sm" type="button" data-add="headers">+ هدر</button></div>
    <p class="hdr-sub">مثلاً <code dir="ltr">X-API-KEY</code> با مقدار <code dir="ltr">@{{api_key}}</code> یا <code dir="ltr">Authorization</code> با <code dir="ltr">Bearer @{{token}}</code>.</p>
    <div id="rows-headers" data-next="{{ count($headers) + 100 }}">
      @foreach($headers as $i => $h)
        <div class="row-pair">
          <input name="headers[{{ $i }}][name]" dir="ltr" placeholder="نام هدر" value="{{ $h['name'] ?? '' }}">
          <input name="headers[{{ $i }}][value]" dir="ltr" placeholder="مقدار" value="{{ $h['value'] ?? '' }}">
          <button class="btn dng sm" type="button" data-remove>حذف</button>
        </div>
        @error("headers.$i.name")<div class="e" style="color:#e0453a;font-size:12px">{{ $message }}</div>@enderror
      @endforeach
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="page-h"><h2>پارامترها (کلید، نام کاربری، رمز، شمارهٔ خط ...)</h2><button class="btn sec sm" type="button" data-add="params">+ پارامتر</button></div>
    <p class="hdr-sub">نام پارامتر فقط حروف کوچک انگلیسی، عدد و _ . مقدار «محرمانه» رمزنگاری ذخیره می‌شود و دیگر نمایش داده نمی‌شود؛ هنگام ویرایش، خالی گذاشتن آن یعنی «بدون تغییر».</p>
    <div id="rows-params" data-next="{{ count($params) + 100 }}">
      @foreach($params as $i => $p)
        @php $secret = (bool) ($p['secret'] ?? false); @endphp
        <div class="row-pair row-param">
          <input name="params[{{ $i }}][name]" dir="ltr" placeholder="نام (مثلاً api_key)" value="{{ $p['name'] ?? '' }}">
          <input name="params[{{ $i }}][value]" dir="ltr" data-value type="{{ $secret ? 'password' : 'text' }}" autocomplete="new-password"
            placeholder="{{ $secret && $editing ? '•••••• (بدون تغییر)' : 'مقدار' }}" value="{{ $secret ? '' : ($p['value'] ?? '') }}">
          <label class="chk"><input type="checkbox" name="params[{{ $i }}][secret]" value="1" data-secret @checked($secret)> محرمانه</label>
          <button class="btn dng sm" type="button" data-remove>حذف</button>
        </div>
      @endforeach
    </div>
    @error('params')<div class="e" style="color:#e0453a;font-size:12.5px;margin-top:8px">{{ $message }}</div>@enderror
  </div>

  <div class="card" style="margin-top:16px">
    <div class="page-h"><h2>تشخیص موفقیت و شناسهٔ پیگیری (اختیاری)</h2></div>
    <p class="hdr-sub">به‌طور پیش‌فرض هر پاسخ HTTP موفق (۲xx) «ارسال موفق» حساب می‌شود. بسیاری از اپراتورها وضعیت واقعی را داخل پاسخ می‌گویند؛ برای دقیق‌ترشدن، بعد از «تست ارسال» نمونهٔ پاسخ را ببینید و این بخش را پر کنید.</p>
    <div class="fgrid">
      <div class="fld">
        <label for="success_json_path">مسیر فیلد در پاسخ JSON</label>
        <input id="success_json_path" name="success_json_path" dir="ltr" placeholder="return.status" value="{{ $v('success_json_path', $s['success']['json_path'] ?? '') }}">
        @error('success_json_path')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="success_expected">مقدار مورد انتظار</label>
        <input id="success_expected" name="success_expected" dir="ltr" placeholder="200 (خالی = فقط وجود داشته باشد)" value="{{ $v('success_expected', $s['success']['expected'] ?? '') }}">
      </div>
      <div class="fld">
        <label for="success_contains">یا متنی که باید در پاسخ باشد</label>
        <input id="success_contains" name="success_contains" dir="ltr" value="{{ $v('success_contains', $s['success']['contains'] ?? '') }}">
      </div>
      <div class="fld">
        <label for="ref_path">مسیر شناسهٔ پیگیری در پاسخ JSON</label>
        <input id="ref_path" name="ref_path" dir="ltr" placeholder="entries.0.messageid" value="{{ $v('ref_path', $s['ref_path'] ?? '') }}">
        @error('ref_path')<div class="e">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="fbar">
      <button class="btn" type="submit">{{ $provider ? 'ذخیره تغییرات' : 'ذخیره اتصال' }}</button>
      <a class="btn sec" href="{{ route('admin.sms.providers.index') }}">انصراف</a>
    </div>
  </div>
</form>

<template id="tpl-headers">
  <div class="row-pair">
    <input name="headers[__i__][name]" dir="ltr" placeholder="نام هدر">
    <input name="headers[__i__][value]" dir="ltr" placeholder="مقدار">
    <button class="btn dng sm" type="button" data-remove>حذف</button>
  </div>
</template>
<template id="tpl-params">
  <div class="row-pair row-param">
    <input name="params[__i__][name]" dir="ltr" placeholder="نام (مثلاً api_key)">
    <input name="params[__i__][value]" dir="ltr" data-value type="text" autocomplete="new-password" placeholder="مقدار">
    <label class="chk"><input type="checkbox" name="params[__i__][secret]" value="1" data-secret> محرمانه</label>
    <button class="btn dng sm" type="button" data-remove>حذف</button>
  </div>
</template>
@if($presets)<script type="application/json" id="presets-data">@json($presets)</script>@endif
@push('scripts')<script src="{{ asset('js/admin-sms.js') }}"></script>@endpush
@endsection
