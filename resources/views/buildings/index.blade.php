@extends('layouts.app')
@section('title', 'ساختمان‌های من')
@section('content')
<div class="card">
  <div class="page-h">
    <h2>ساختمان‌های من</h2>
    <a class="btn" href="{{ route('buildings.create') }}">+ ساختمان جدید</a>
  </div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>نام ساختمان</th><th>کد</th><th>نقش من</th><th>واحدها</th><th></th></tr></thead>
    <tbody>
    @foreach($buildings as $b)
      <tr>
        <td><b>{{ $b['name'] }}</b> @if($b['id'] === $currentId)<span class="pill">فعال</span>@endif</td>
        <td>{{ \App\Support\Fa::digits($b['code']) }}</td>
        <td>{{ $b['roles'] }}</td>
        <td>{{ \App\Support\Fa::num($b['units']) }}</td>
        <td class="act">
          @if($b['id'] !== $currentId)
            <form method="POST" action="{{ route('building.switch', $b['id']) }}">@csrf<button class="btn sec sm" type="submit">ورود به این ساختمان</button></form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@endsection
