@extends('admin.layout')
@section('title', 'فعال‌سازی تأیید دومرحله‌ای')
@section('content')
<div class="card">
  <p style="line-height:1.9;margin:0 0 12px">این کلید را در Google Authenticator یا برنامه‌ی مشابه اضافه کنید (افزودن دستی)، سپس کد ۶ رقمی نمایش‌داده‌شده را وارد کنید.</p>
  <div class="code-box" dir="ltr" style="font-family:monospace;padding:10px;border:1px dashed var(--line);border-radius:10px;word-break:break-all;margin-bottom:8px">{{ $secret }}</div>
  <div class="code-box" dir="ltr" style="font-family:monospace;font-size:12px;padding:10px;border:1px dashed var(--line);border-radius:10px;word-break:break-all;color:var(--muted)">{{ $uri }}</div>
  <form method="POST" action="{{ route('admin.account.two-factor.confirm') }}" style="margin-top:14px">
    @csrf
    <div class="fld" style="max-width:240px">
      <label for="code">کد تأیید</label>
      <input id="code" name="code" inputmode="numeric" maxlength="6" dir="ltr" autocomplete="one-time-code" placeholder="••••••" required>
      @error('code')<div class="e">{{ $message }}</div>@enderror
    </div>
    <div class="fbar">
      <button class="btn" type="submit">فعال‌سازی</button>
      <a class="btn sec" href="{{ route('admin.account') }}">انصراف</a>
    </div>
  </form>
</div>
@endsection
