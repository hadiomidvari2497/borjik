@php($done = true)
@extends('layouts.app')
@section('title', 'پروفایل من')
@section('content')
  <style>
    .profile-page .lead{color:var(--muted);font-size:13px;margin:-10px 0 8px}
    .profile-page .field{margin-bottom:16px;min-width:0}
    .profile-page .field label{display:block;font-size:13px;font-weight:600;margin-bottom:7px}
    .profile-page .field .opt,.profile-page .hint{color:var(--muted);font-size:12px;font-weight:400}
    .profile-page .field input,.profile-page .field textarea,.profile-page .field select{width:100%;padding:11px 13px;border:1px solid var(--line);border-radius:12px;background:var(--card);font:inherit;color:var(--text)}
    .profile-page .field textarea{resize:vertical;min-height:76px}
    .profile-page .field input:focus,.profile-page .field textarea:focus,.profile-page .field select:focus{outline:2px solid var(--brand-soft);border-color:var(--brand)}
    .profile-page .field .err,.profile-page .profile-error{color:var(--danger);font-size:12px;margin-top:6px}
    .profile-page .profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 16px}
    .profile-page .profile-seg{display:grid;grid-template-columns:1fr 1fr;gap:6px;height:auto;background:var(--track);padding:4px;border-radius:14px;margin:4px 0 18px}
    .profile-page .profile-seg label{margin:0;cursor:pointer;text-align:center}
    .profile-page .profile-seg input{position:absolute;opacity:0;pointer-events:none}
    .profile-page .profile-seg span{display:block;padding:9px;border-radius:10px}
    .profile-page .profile-seg input:checked+span{background:var(--card);color:var(--brand);font-weight:700;box-shadow:var(--shadow)}
    .profile-page .profile-more{border:1px dashed var(--line);border-radius:14px;padding:4px 14px;margin:8px 0 18px}
    .profile-page .profile-more summary{cursor:pointer;padding:10px 0;font-weight:600;color:var(--brand)}
    .profile-page .profile-more[open]{padding-bottom:12px}
    .profile-page .pw{position:relative}
    .profile-page .pw input{padding-inline-end:54px}
    .profile-page .pw button{position:absolute;inset-inline-end:6px;top:50%;transform:translateY(-50%);padding:6px;color:var(--muted);font-size:12px}
    .profile-page .profile-submit{margin-top:6px}
    .profile-page .profile-settings{margin-top:20px}
    .profile-page .radio-option{display:flex!important;align-items:center;gap:9px;cursor:pointer}
    .profile-page .radio-option input{width:16px!important;height:16px;accent-color:var(--brand)}
    @media(max-width:640px){.profile-page .profile-grid{grid-template-columns:1fr}}
  </style>
  <div class="profile-page">
    <h2 style="font-size:20px;font-weight:800">پروفایل من</h2>
    <p class="lead">اطلاعات هویتی خود را ویرایش کنید.</p>
    @include('auth.partials.profile-form')

    @if($loginDestinations->isNotEmpty())
      <div class="card profile-settings">
        <h2>تنظیمات ورود</h2>
        <p class="hint" style="margin-bottom:14px">مشخص کنید بعد از ورود به حساب، کدام ساختمان و نقش باز شود.</p>
        <form method="POST" action="{{ route('profile.login-destination') }}">@csrf
          <div class="field"><label class="radio-option"><input type="radio" name="login_destination_mode" value="fixed" @checked(old('login_destination_mode', $user->login_destination_mode ?? 'last') === 'fixed')><span>همیشه به مقصد مشخص‌شده بروم</span></label></div>
          <div class="field">
            <label for="preferred_membership_id">ساختمان و نقش پیش‌فرض</label>
            <select id="preferred_membership_id" name="preferred_membership_id">
              <option value="">انتخاب ساختمان و نقش</option>
              @foreach($loginDestinations as $destination)
                <option value="{{ $destination->membership_id }}" @selected((string) old('preferred_membership_id', $user->preferred_building_user_role_id) === (string) $destination->membership_id)>{{ $destination->building_name }} — {{ $destination->role_name }}</option>
              @endforeach
            </select>
            @error('preferred_membership_id')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div class="field"><label class="radio-option"><input type="radio" name="login_destination_mode" value="last" @checked(old('login_destination_mode', $user->login_destination_mode ?? 'last') === 'last')><span>بازگشت به آخرین ساختمان و نقشی که در آن بودم</span></label></div>
          @error('login_destination_mode')<div class="err">{{ $message }}</div>@enderror
          <button class="btn" type="submit">ذخیره تنظیمات ورود</button>
        </form>
      </div>
    @endif
    <div class="row" style="margin-top:18px">
      <span>تأیید دومرحله‌ای: {{ $user->two_factor_enabled ? 'فعال است' : 'غیرفعال' }}</span>
      @unless($user->two_factor_enabled)<a class="link" href="{{ route('profile.two-factor') }}">فعال‌سازی</a>@endunless
    </div>
  </div>
@endsection
