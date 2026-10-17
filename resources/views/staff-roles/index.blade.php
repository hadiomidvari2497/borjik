@extends('layouts.app')
@section('title', 'نقش‌های پرسنلی')
@section('content')
<div class="card">
  <div class="page-h"><div><h2>نقش‌های پرسنلی ساختمان</h2><p>نقش‌ها و دسترسی‌های عملیاتی هر ساختمان مستقل هستند. نقش‌های مالک و مدیر از این بخش قابل حذف نیستند.</p></div></div>
  @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="notice error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
  <div style="padding:18px;border-bottom:1px solid var(--line)">
    <h3 style="margin:0 0 14px">افزودن نقش جدید</h3>
    <form method="POST" action="{{ route('staff-roles.store') }}">@csrf
      <div class="fgrid">
        <div class="fld"><label for="new-name">نام نقش</label><input id="new-name" name="name_fa" maxlength="60" required placeholder="مثلاً مسئول تأسیسات"></div>
        <div class="fld"><label>دسترسی‌های نقش</label><div style="display:grid;gap:8px">
          @foreach($permissions as $permission)<label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="checkbox" name="permissions[]" value="{{ $permission->key }}"> {{ $permission->key }}</label>@endforeach
        </div></div>
      </div>
      <button class="btn" type="submit" style="margin-top:14px">ایجاد نقش</button>
    </form>
  </div>
  <div style="padding:18px">
    <h3 style="margin:0 0 14px">نقش‌های این ساختمان</h3>
    @forelse($roles as $role)
      <div style="border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:14px">
        <form method="POST" action="{{ route('staff-roles.update', $role->id) }}">@csrf @method('PUT')
          <div class="fgrid">
            <div class="fld"><label for="role-{{ $role->id }}">نام نقش</label><input id="role-{{ $role->id }}" name="name_fa" maxlength="60" required value="{{ $role->name_fa }}"></div>
            <div class="fld"><label>دسترسی‌ها</label><div style="display:grid;gap:8px">
              @foreach($permissions as $permission)<label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="checkbox" name="permissions[]" value="{{ $permission->key }}" @checked($role->permissions->contains('key', $permission->key))> {{ $permission->key }}</label>@endforeach
            </div></div>
          </div>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:14px">
            <button class="btn" type="submit">ذخیره تغییرات</button>
            @if($role->is_default)<span class="muted" style="font-size:12px">نقش پیش‌فرض</span>@endif
          </div>
        </form>
        <form method="POST" action="{{ route('staff-roles.destroy', $role->id) }}" data-confirm="از حذف این نقش مطمئن هستید؟" style="margin-top:10px">@csrf @method('DELETE')<button class="btn danger" type="submit">حذف نقش</button></form>
      </div>
    @empty
      <p class="muted">هنوز نقش پرسنلی برای این ساختمان تعریف نشده است.</p>
    @endforelse
  </div>
</div>
@endsection
