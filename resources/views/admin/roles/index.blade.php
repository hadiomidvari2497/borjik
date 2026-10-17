@extends('admin.layout')
@section('title', 'نقش‌ها و دسترسی‌ها')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<div class="card">
  <div class="page-h">
    <h2>نقش‌های پنل مدیریت</h2>
    <a class="btn" href="{{ route('admin.roles.create') }}">+ نقش جدید</a>
  </div>
  <p style="color:var(--muted);font-size:13px;margin:0 0 12px;line-height:1.7">
    هر نقش مجموعه‌ای از مجوزهاست. سوپرادمین به نقش نیاز ندارد و همهٔ مجوزها را دارد.
  </p>
  @if($roles->isEmpty())
    <div class="empty">هنوز نقشی تعریف نشده است.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>نام نقش</th><th>تعداد مجوز</th><th>تعداد کاربران</th><th></th></tr></thead>
    <tbody>
    @foreach($roles as $role)
      <tr>
        <td><b>{{ $role->name }}</b></td>
        <td>{{ $F::num($permCounts[$role->id] ?? 0) }}</td>
        <td>{{ $F::num($role->users_count) }}</td>
        <td class="act">
          <a class="btn sec sm" href="{{ route('admin.roles.edit', $role->id) }}">ویرایش</a>
          <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" data-confirm="این نقش حذف شود؟" data-confirm-title="حذف نقش" data-confirm-icon="warning">
            @csrf @method('DELETE')
            <button class="btn dng sm" type="submit">حذف</button>
          </form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>
@endsection
