@extends('admin.layout')
@section('title', $user ? 'ویرایش کاربر ادمین' : 'کاربر ادمین جدید')
@section('content')
@php
  $v = fn ($key, $default = null) => old($key, $user?->{$key} ?? $default);
  $isSelf = $user && $user->id === $me->id;
  $selectedRoles = old('roles', $user ? $user->roles->pluck('id')->all() : []);
@endphp
<div class="card">
  <div class="page-h"><h2>{{ $user ? 'ویرایش کاربر ادمین' : 'ایجاد کاربر ادمین' }}</h2>
    <a class="btn sec sm" href="{{ route('admin.users.index') }}">بازگشت</a>
  </div>
  <form method="POST" action="{{ $user ? route('admin.users.update', $user->id) : route('admin.users.store') }}" style="margin-top:12px" autocomplete="off">
    @csrf
    @if($user) @method('PUT') @endif
    <div class="fgrid">
      <div class="fld">
        <label for="username">نام کاربری *</label>
        <input id="username" name="username" dir="ltr" value="{{ $v('username') }}" required>
        @error('username')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="name">نام نمایشی</label>
        <input id="name" name="name" value="{{ $v('name') }}">
        @error('name')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="password">رمز عبور {{ $user ? '(خالی = بدون تغییر)' : '*' }}</label>
        <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password" @required(! $user)>
        @error('password')<div class="e">{{ $message }}</div>@enderror
      </div>
      <div class="fld">
        <label for="password_confirmation">تکرار رمز عبور</label>
        <input id="password_confirmation" name="password_confirmation" type="password" dir="ltr" autocomplete="new-password">
      </div>
      <div class="fld">
        <label for="status">وضعیت</label>
        <select id="status" name="status" @disabled($isSelf)>
          <option value="active" @selected($v('status', 'active') === 'active')>فعال</option>
          <option value="disabled" @selected($v('status', 'active') === 'disabled')>غیرفعال</option>
        </select>
        @if($isSelf)<input type="hidden" name="status" value="{{ $user->status }}"><div style="font-size:12px;color:var(--muted);margin-top:4px">وضعیت حساب خودتان قابل تغییر نیست.</div>@endif
        @error('status')<div class="e">{{ $message }}</div>@enderror
      </div>
      @if($me->is_super && ! $isSelf)
      <div class="fld">
        <label style="display:flex;gap:8px;align-items:center;margin-top:28px">
          <input type="checkbox" name="is_super" value="1" style="width:auto" @checked(old('is_super', $user?->is_super ?? false))>
          سوپرادمین (دسترسی کامل به همه بخش‌ها)
        </label>
      </div>
      @endif
      @if(! $isSelf)
      <div class="fld full">
        <label>نقش‌ها</label>
        @if($roles->isEmpty())
          <div style="color:var(--muted);font-size:13px">نقشی برای اختصاص وجود ندارد. ابتدا از بخش «نقش‌ها و دسترسی‌ها» نقش بسازید.</div>
        @else
          <div style="display:flex;flex-wrap:wrap;gap:14px">
            @foreach($roles as $r)
              <label style="display:flex;gap:6px;align-items:center">
                <input type="checkbox" name="roles[]" value="{{ $r->id }}" style="width:auto" @checked(in_array($r->id, array_map('intval', $selectedRoles), true))>
                {{ $r->name }}
              </label>
            @endforeach
          </div>
        @endif
        @error('roles')<div class="e">{{ $message }}</div>@enderror
      </div>
      @endif
    </div>
    <div class="fbar">
      <button class="btn" type="submit">{{ $user ? 'ذخیره تغییرات' : 'ایجاد کاربر' }}</button>
      <a class="btn sec" href="{{ route('admin.users.index') }}">انصراف</a>
    </div>
  </form>
</div>
@if($user && ! $isSelf && $user->two_factor_enabled)
<div class="card" style="margin-top:16px">
  <p style="margin:0 0 10px;line-height:1.8">تأیید دومرحله‌ای این کاربر فعال است. اگر دسترسی او به برنامه‌ی احراز هویت را از دست داده، می‌توانید آن را ریست کنید.</p>
  <form method="POST" action="{{ route('admin.users.reset-two-factor', $user->id) }}" data-confirm="تأیید دومرحله‌ای این کاربر غیرفعال شود؟" data-confirm-title="ریست ۲FA" data-confirm-icon="warning">
    @csrf
    <button class="btn dng sm" type="submit">ریست تأیید دومرحله‌ای</button>
  </form>
</div>
@endif
@endsection
