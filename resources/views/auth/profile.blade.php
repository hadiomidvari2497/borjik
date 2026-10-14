@extends('layouts.guest')
@section('title', 'تکمیل پروفایل | برجیک')
@section('content')
  <h1>{{ $user->profile_completed_at ? 'پروفایل من' : 'تکمیل پروفایل' }}</h1>
  <p class="sub">برای ادامه، اطلاعات هویتی را کامل کنید. عکس پروفایل اختیاری است.</p>
  <form method="POST" action="{{ route('profile.update') }}">@csrf
    <label for="pt">نوع شخص</label>
    <select id="pt" name="person_type">
      <option value="real" @selected(old('person_type', $user->person_type?->value) !== 'legal')>حقیقی</option>
      <option value="legal" @selected(old('person_type', $user->person_type?->value) === 'legal')>حقوقی</option>
    </select>
    <div data-for="real">
      <label>نام</label><input name="first_name" value="{{ old('first_name', $user->first_name) }}">
      @error('first_name')<div class="err">{{ $message }}</div>@enderror
      <label>نام خانوادگی</label><input name="last_name" value="{{ old('last_name', $user->last_name) }}">
      @error('last_name')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div data-for="legal" hidden>
      <label>نام شرکت</label><input name="company_name" value="{{ old('company_name', $user->company_name) }}">
      @error('company_name')<div class="err">{{ $message }}</div>@enderror
    </div>
    <label>کد ملی (حقوقی: شناسه‌ی ملی)</label><input name="national_code" inputmode="numeric" value="{{ old('national_code', $user->national_code) }}">
    @error('national_code')<div class="err">{{ $message }}</div>@enderror
    <label>تاریخ تولد (حقوقی: تأسیس) — شمسی</label><input name="birth_date" placeholder="۱۳۷۰/۰۵/۱۲" value="{{ old('birth_date') }}">
    @error('birth_date')<div class="err">{{ $message }}</div>@enderror
    <label>آدرس (اختیاری)</label><textarea name="address" rows="2">{{ old('address', $user->address) }}</textarea>
    <label>رمز عبور (اختیاری، حداقل ۸ کاراکتر)</label><input type="password" name="password" autocomplete="new-password">
    <input type="password" name="password_confirmation" placeholder="تکرار رمز عبور" style="margin-top:8px">
    @error('password')<div class="err">{{ $message }}</div>@enderror
    <button>ذخیره</button>
  </form>
  @if($user->profile_completed_at)
    <p class="sub" style="margin-top:14px">تأیید دومرحله‌ای:
      @if($user->two_factor_enabled) فعال است @else <a href="{{ route('profile.two-factor') }}">فعال‌سازی</a> @endif
    </p>
  @endif
  <script>
    const s = document.getElementById('pt'), f = () => document.querySelectorAll('[data-for]').forEach(d => d.hidden = d.dataset.for !== s.value);
    s.onchange = f; f();
  </script>
@endsection
