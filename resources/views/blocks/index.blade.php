@extends('layouts.app')
@section('title', 'بلوک‌ها')
@section('content')
<div class="card">
  <div class="page-h"><h2>بلوک‌های ساختمان</h2><a class="btn" href="{{ route('blocks.create') }}">+ بلوک جدید</a></div>
  @if($blocks->isEmpty())<div class="empty">هنوز بلوکی ثبت نشده است. اول یک بلوک بسازید، بعد واحدها را به آن اضافه کنید.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>شماره</th><th>نام</th><th>تعداد طبقه</th><th>واحدها</th><th></th></tr></thead>
    <tbody>
    @foreach($blocks as $b)
      <tr>
        <td>{{ \App\Support\Fa::digits($b->number) }}</td><td><b>{{ $b->name }}</b></td>
        <td>{{ \App\Support\Fa::digits($b->floors_count) }}</td><td>{{ \App\Support\Fa::num($b->units_count) }}</td>
        <td class="act">
          <a class="btn sec sm" href="{{ route('blocks.edit', $b) }}">ویرایش</a>
          <form method="POST" action="{{ route('blocks.destroy', $b) }}" onsubmit="return confirm('این بلوک حذف شود؟')">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button></form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>
@endsection
