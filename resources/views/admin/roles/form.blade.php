@extends('admin.layout')
@section('title', $role ? 'ویرایش نقش' : 'نقش جدید')
@section('content')
@php $checked = old('permissions', $selected); @endphp
<div class="card">
  <div class="page-h"><h2>{{ $role ? 'ویرایش نقش' : 'ایجاد نقش' }}</h2>
    <a class="btn sec sm" href="{{ route('admin.roles.index') }}">بازگشت</a>
  </div>
  <form method="POST" action="{{ $role ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}" style="margin-top:12px">
    @csrf
    @if($role) @method('PUT') @endif
    <div class="fgrid">
      <div class="fld full">
        <label for="name">نام نقش *</label>
        <input id="name" name="name" value="{{ old('name', $role?->name) }}" placeholder="مثلاً: پشتیبان مالی" required>
        @error('name')<div class="e">{{ $message }}</div>@enderror
      </div>
    </div>

    <h3 style="margin:18px 0 8px;font-size:1rem">مجوزها</h3>
    @error('permissions')<div class="e">{{ $message }}</div>@enderror
    @foreach($groups as $group)
      <fieldset style="border:1px solid var(--line,#dbe6e3);border-radius:12px;padding:10px 14px;margin:0 0 10px">
        <legend style="padding:0 6px;font-weight:700;font-size:.9rem">{{ $group['title'] }}</legend>
        @foreach($group['items'] as $key => $label)
          @php $can = in_array($key, $grantable, true); @endphp
          <label style="display:flex;gap:8px;align-items:center;margin:6px 0;{{ $can ? '' : 'opacity:.5' }}">
            <input type="checkbox" name="permissions[]" value="{{ $key }}" style="width:auto"
              @checked(in_array($key, $checked, true)) @disabled(! $can)>
            {{ $label }}
            @unless($can)<span style="font-size:12px;color:var(--muted)">(شما این مجوز را ندارید)</span>@endunless
          </label>
          {{-- مجوزِ غیرقابل‌دسترس که از قبل در نقش است باید هنگام ذخیره حفظ شود --}}
          @if(! $can && in_array($key, $checked, true))
            <input type="hidden" name="permissions[]" value="{{ $key }}">
          @endif
        @endforeach
      </fieldset>
    @endforeach

    <div class="fbar">
      <button class="btn" type="submit">{{ $role ? 'ذخیره تغییرات' : 'ایجاد نقش' }}</button>
      <a class="btn sec" href="{{ route('admin.roles.index') }}">انصراف</a>
    </div>
  </form>
</div>
@endsection
