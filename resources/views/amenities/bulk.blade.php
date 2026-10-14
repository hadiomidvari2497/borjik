@php $p = $cfg['prefix']; @endphp
@extends('layouts.app')
@section('title', 'ثبت گروهی '.$cfg['plural'])
@section('content')
<div class="card">
  <div class="page-h"><h2>ثبت گروهی {{ $cfg['plural'] }}</h2></div>
  <p style="color:var(--muted);font-size:13px;margin:6px 0 12px">شماره‌ها پشت‌سرهم ساخته می‌شوند و همه «آزاد» هستند؛ بعداً به واحدها اختصاص می‌دهید.</p>
  <form method="POST" action="{{ route("{$p}.bulk.store") }}">@csrf
    <div class="fgrid">
      <div class="fld"><label for="count">تعداد *</label><input id="count" type="number" name="count" min="1" max="500" value="{{ old('count', 10) }}">@error('count')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="start_number">شماره‌ی اولی *</label><input id="start_number" name="start_number" inputmode="numeric" value="{{ old('start_number', 1) }}">@error('start_number')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="block_id">بلوک</label>
        <select id="block_id" name="block_id"><option value="">بدون بلوک (مشترک)</option>@foreach($blocks as $b)<option value="{{ $b->id }}" @selected((int) old('block_id') === $b->id)>{{ $b->name }}</option>@endforeach</select></div>
      <div class="fld"><label for="floor">طبقه</label><input id="floor" type="number" name="floor" value="{{ old('floor') }}"><div class="h">۰ = همکف (عدد منفی = زیرزمین)</div></div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ثبت</button><a class="btn sec" href="{{ route("{$p}.index") }}">انصراف</a></div>
  </form>
</div>
@endsection
