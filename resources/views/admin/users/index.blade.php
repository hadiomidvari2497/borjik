@extends('admin.layout')
@section('title', 'کاربران ادمین')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<div class="card">
  <div class="page-h">
    <h2>کاربران پنل مدیریت</h2>
    <a class="btn" href="{{ route('admin.users.create') }}">+ کاربر ادمین جدید</a>
  </div>
  <p style="color:var(--muted);font-size:13px;margin:0 0 12px;line-height:1.7">
    این کاربران فقط برای مدیریت سایت هستند و از کاربران ساختمان‌ها کاملاً جدا می‌باشند.
  </p>
  <form method="GET" class="fbar" style="margin-bottom:12px">
    <input name="q" value="{{ $q }}" placeholder="جستجوی نام یا نام کاربری" style="max-width:260px">
    <button class="btn sec" type="submit">جستجو</button>
  </form>
  @if($users->isEmpty())
    <div class="empty">کاربری یافت نشد.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr>
      <th>نام کاربری</th><th>نام</th><th>نقش‌ها</th><th>وضعیت</th><th>آخرین ورود</th><th></th>
    </tr></thead>
    <tbody>
    @foreach($users as $u)
      <tr>
        <td dir="ltr" style="text-align:right"><b>{{ $u->username }}</b></td>
        <td>{{ $u->name ?: '—' }}</td>
        <td>
          @if($u->is_super)<span class="pill">سوپرادمین</span>@endif
          @foreach($u->roles as $r)<span class="pill u">{{ $r->name }}</span> @endforeach
          @if(! $u->is_super && $u->roles->isEmpty())<span style="color:var(--muted)">بدون نقش</span>@endif
        </td>
        <td>@if($u->status === 'active')<span class="pill">فعال</span>@else<span class="pill u">غیرفعال</span>@endif</td>
        <td>{{ $u->last_login_at ? $F::digits($u->last_login_at->diffForHumans()) : '—' }}</td>
        <td class="act">
          @if(! $u->is_super || $me->is_super)
            <a class="btn sec sm" href="{{ route('admin.users.edit', $u->id) }}">ویرایش</a>
            @if($u->id !== $me->id)
            <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" data-confirm="این کاربر ادمین حذف شود؟" data-confirm-title="حذف کاربر" data-confirm-icon="warning">
              @csrf @method('DELETE')
              <button class="btn dng sm" type="submit">حذف</button>
            </form>
            @endif
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $users->links() }}
  @endif
</div>
@endsection
