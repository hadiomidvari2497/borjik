@extends('admin.layout')
@section('title', 'اتصال پیامک')
@section('content')
@php $F = \App\Support\Fa::class; $test = session('sms_test'); @endphp

<div class="card" style="display:flex;gap:14px;align-items:center;justify-content:space-between;flex-wrap:wrap">
  <div style="display:flex;gap:12px;align-items:center">
    <div style="width:42px;height:42px;border-radius:13px;display:grid;place-items:center;color:#fff;background:{{ $current ? '#2a9b8f' : '#e8a33d' }}"><svg class="i"><use href="#msg"/></svg></div>
    <div>
      <b style="display:block;font-size:14px">
        @if($current) پیامک‌ها از طریق «{{ $current->name }}» ارسال می‌شوند.
        @else هیچ اتصال فعالی انتخاب نشده است.
        @endif
      </b>
      <small style="color:var(--muted);font-size:12.5px;line-height:1.8">
        @if($current) برای تغییر اپراتور، اتصال دیگری را «پیش‌فرض» کنید.
        @else پیامک‌ها فعلاً با درایور «{{ $driver }}» فقط در لاگ سیستم ثبت می‌شوند و به گوشی کسی نمی‌رسند.
        @endif
      </small>
    </div>
  </div>
  <a class="btn" href="{{ route('admin.sms.providers.create') }}">+ اتصال جدید</a>
</div>

@if($test)
<div class="card" style="border-color:{{ $test['ok'] ? 'var(--brand)' : 'rgba(224,69,58,.5)' }}">
  <div class="page-h"><h2>نتیجهٔ آخرین تست ارسال — {{ $test['provider'] }}</h2><span class="pill {{ $test['ok'] ? '' : 'u' }}">{{ $test['ok'] ? 'موفق' : 'ناموفق' }}</span></div>
  <div class="kv"><span>شماره</span><b dir="ltr">{{ $test['mobile'] }}</b></div>
  @if($test['ok'])
    <div class="kv"><span>وضعیت HTTP</span><b dir="ltr">{{ $test['http'] }}</b></div>
    <div class="kv"><span>شناسهٔ پیگیری</span><b dir="ltr">{{ $test['ref'] }}</b></div>
    <p style="font-size:12.5px;color:var(--muted);margin:10px 0 4px">نمونهٔ پاسخ اپراتور (برای تنظیم «شرط موفقیت» و «مسیر شناسهٔ پیگیری» در فرم اتصال):</p>
    <pre dir="ltr" style="white-space:pre-wrap;word-break:break-all;background:var(--track);padding:10px 12px;border-radius:12px;font-size:12px;text-align:left;margin:0">{{ $test['body'] !== '' ? $test['body'] : '(پاسخ خالی)' }}</pre>
  @else
    <p style="line-height:1.9;margin:10px 0 0">{{ $test['message'] }}</p>
  @endif
</div>
@endif

<div class="card">
  <div class="page-h"><h2>اتصال‌های پیامکی</h2></div>
  @if($providers->isEmpty())
    <div class="empty">هنوز اتصالی تعریف نشده است. با «اتصال جدید» می‌توانید یکی از اپراتورهای آماده را انتخاب کنید یا مشخصات وب‌سرویس هر اپراتور دیگری را وارد کنید.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>نام</th><th>وضعیت</th><th>آخرین تست</th><th></th></tr></thead>
    <tbody>
    @foreach($providers as $p)
      <tr>
        <td><b>{{ $p->name }}</b>
          <div dir="ltr" style="text-align:right;font-size:11.5px;color:var(--muted)">{{ parse_url(preg_replace('/\{\{[^}]*\}\}/', 'x', $p->settings()['url']), PHP_URL_HOST) }}</div>
        </td>
        <td>
          @if($p->is_default)<span class="pill">پیش‌فرض</span>@endif
          @if($p->is_active)<span class="pill">فعال</span>@else<span class="pill u">غیرفعال</span>@endif
        </td>
        <td>
          @if($p->last_test_at)
            <span class="pill {{ $p->last_test_ok ? '' : 'u' }}">{{ $p->last_test_ok ? 'موفق' : 'ناموفق' }}</span>
            <span style="font-size:12px;color:var(--muted)">{{ $F::date($p->last_test_at, 'j F - H:i') }}</span>
          @else
            <span style="color:var(--muted);font-size:12.5px">تست نشده</span>
          @endif
        </td>
        <td class="act">
          @if($p->is_active && ! $p->is_default)
            <form method="POST" action="{{ route('admin.sms.providers.default', $p->id) }}"
              @if($p->last_test_ok !== true) data-confirm="این اتصال هنوز تست موفق ندارد. با این حال پیش‌فرض شود؟" data-confirm-title="پیش‌فرض‌کردن" data-confirm-icon="warning" @endif>
              @csrf<button class="btn sm" type="submit">پیش‌فرض کن</button>
            </form>
          @endif
          <form method="POST" action="{{ route('admin.sms.providers.toggle', $p->id) }}">@csrf<button class="btn sec sm" type="submit">{{ $p->is_active ? 'غیرفعال' : 'فعال' }}</button></form>
          <a class="btn sec sm" href="{{ route('admin.sms.providers.edit', $p->id) }}">ویرایش</a>
          <form method="POST" action="{{ route('admin.sms.providers.destroy', $p->id) }}" data-confirm="این اتصال حذف شود؟" data-confirm-title="حذف اتصال" data-confirm-icon="warning">
            @csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button>
          </form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>

@if($providers->isNotEmpty())
<div class="card">
  <div class="page-h"><h2>تست ارسال</h2></div>
  <p class="hdr-sub">یک پیامک واقعی به شمارهٔ شما ارسال می‌شود (هزینهٔ آن از اعتبار پنل اپراتور کم می‌شود) و پاسخ اپراتور اینجا نمایش داده می‌شود.</p>
  <form method="POST" id="test-form" action="{{ route('admin.sms.providers.test', $providers->first()->id) }}" data-base="{{ url('/admin/sms/providers') }}">
    @csrf
    <div class="fgrid">
      <div class="fld">
        <label for="t_provider">اتصال</label>
        <select id="t_provider">
          @foreach($providers as $p)<option value="{{ $p->id }}">{{ $p->name }}{{ $p->is_active ? '' : ' (غیرفعال)' }}</option>@endforeach
        </select>
      </div>
      <div class="fld">
        <label for="t_mobile">شماره‌ی موبایل *</label>
        <input id="t_mobile" name="mobile" dir="ltr" inputmode="tel" placeholder="09123456789" value="{{ old('mobile') }}" required>
        @error('mobile')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld full">
        <label for="t_message">متن پیام</label>
        <input id="t_message" name="message" value="{{ old('message', $defaultText) }}" maxlength="300">
      </div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ارسال پیامک تستی</button></div>
  </form>
</div>
@push('scripts')
<script>
  (function () {
    var f = document.getElementById('test-form'), s = document.getElementById('t_provider');
    function sync() { f.action = f.dataset.base + '/' + s.value + '/test'; }
    s.addEventListener('change', sync); sync();
  })();
</script>
@endpush
@endif
@endsection
