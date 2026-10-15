@php
  $type = old('person_type', $user->person_type?->value ?? 'real');
  $birth = old('birth_date', \\App\\Support\\JalaliDate::format($user->birth_date));
@endphp
<form method="POST" action="{{ route('profile.update') }}">@csrf
  <div class="seg profile-seg" role="radiogroup" aria-label="نوع شخص">
    <label><input type="radio" name="person_type" value="real" @checked($type !== 'legal')><span>شخص حقیقی</span></label>
    <label><input type="radio" name="person_type" value="legal" @checked($type === 'legal')><span>شخص حقوقی</span></label>
  </div>

  <div class="grid2 profile-grid" data-for="real">
    <div class="field"><label for="first_name">نام</label><input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" autocomplete="given-name">@error('first_name')<div class="err">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="last_name">نام خانوادگی</label><input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" autocomplete="family-name">@error('last_name')<div class="err">{{ $message }}</div>@enderror</div>
  </div>
  <div class="field" data-for="legal" hidden><label for="company_name">نام شرکت یا سازمان</label><input id="company_name" name="company_name" value="{{ old('company_name', $user->company_name) }}">@error('company_name')<div class="err">{{ $message }}</div>@enderror</div>

  <div class="grid2 profile-grid">
    <div class="field">
      <label for="national_code"><span data-label-real="کد ملی" data-label-legal="شناسه‌ی ملی">کد ملی</span></label>
      <input id="national_code" name="national_code" inputmode="numeric" maxlength="11" data-digits value="{{ old('national_code', $user->national_code) }}" dir="ltr" style="text-align:right">
      @error('national_code')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="field">
      <label for="birth_date"><span data-label-real="تاریخ تولد" data-label-legal="تاریخ تأسیس">تاریخ تولد</span></label>
      <input id="birth_date" name="birth_date" class="jdate" data-max="today" data-min="1300/01/01" placeholder="انتخاب از تقویم" value="{{ $birth }}">
      @error('birth_date')<div class="err">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="field"><label for="address">آدرس <span class="opt">(اختیاری)</span></label><textarea id="address" name="address" rows="2">{{ old('address', $user->address) }}</textarea></div>

  <details class="more profile-more" @if($errors->has('password')) open @endif>
    <summary>تعیین رمز عبور (اختیاری)</summary>
    <p class="hint" style="margin-bottom:10px">با رمز عبور می‌توانید بدون پیامک وارد شوید. اگر نگذارید، همیشه با کد پیامکی وارد می‌شوید.</p>
    <div class="grid2 profile-grid">
      <div class="field"><label for="password">رمز عبور</label><div class="pw"><input id="password" type="password" name="password" autocomplete="new-password"><button type="button">نمایش</button></div><div class="hint">حداقل ۸ کاراکتر</div></div>
      <div class="field"><label for="password_confirmation">تکرار رمز عبور</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
    </div>
    @error('password')<div class="err profile-error">{{ $message }}</div>@enderror
  </details>

  <button class="btn profile-submit" type="submit">{{ $done ? 'ذخیره‌ی تغییرات' : 'ذخیره و ادامه' }}</button>
</form>
