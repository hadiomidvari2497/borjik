@php $p = $cfg['prefix']; $v = fn ($k, $d = null) => old($k, $item?->{$k} ?? $d); @endphp
@extends('layouts.app')
@section('title', ($item ? 'ویرایش ' : '').$cfg['title'].($item ? '' : ' جدید'))
@section('content')
<div class="card">
  <div class="page-h"><h2>{{ $item ? 'ویرایش' : 'ثبت' }} {{ $cfg['title'] }}</h2></div>
  <form method="POST" action="{{ $item ? route("{$p}.update", $item->id) : route("{$p}.store") }}" style="margin-top:12px">
    @csrf @if($item) @method('PUT') @endif
    <div class="fgrid">
      <div class="fld"><label for="number">شماره‌ی {{ $cfg['title'] }} *</label><input id="number" name="number" value="{{ $v('number') }}" required>@error('number')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="block_id">بلوک</label>
        <select id="block_id" name="block_id"><option value="">بدون بلوک (مشترک)</option>@foreach($blocks as $b)<option value="{{ $b->id }}" @selected((int) $v('block_id') === $b->id)>{{ $b->name }}</option>@endforeach</select>@error('block_id')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="floor">طبقه</label><input id="floor" type="number" name="floor" value="{{ $v('floor') }}"><div class="h">۰ = همکف (عدد منفی = زیرزمین)</div>@error('floor')<div class="e">{{ $message }}</div>@enderror</div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ذخیره</button><a class="btn sec" href="{{ route("{$p}.index") }}">انصراف</a></div>
  </form>
</div>
@endsection
