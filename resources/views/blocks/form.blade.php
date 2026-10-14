@php $v = fn ($k, $d = null) => old($k, $block?->{$k} ?? $d); @endphp
@extends('layouts.app')
@section('title', $block ? 'ویرایش بلوک' : 'بلوک جدید')
@section('content')
<div class="card">
  <div class="page-h"><h2>{{ $block ? 'ویرایش بلوک' : 'بلوک جدید' }}</h2></div>
  <form method="POST" action="{{ $block ? route('blocks.update', $block) : route('blocks.store') }}" style="margin-top:12px">
    @csrf @if($block) @method('PUT') @endif
    <div class="fgrid">
      <div class="fld"><label for="number">شماره‌ی بلوک *</label><input id="number" type="number" name="number" min="1" value="{{ $v('number') }}" required>@error('number')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="name">نام بلوک *</label><input id="name" name="name" value="{{ $v('name') }}" placeholder="مثلاً بلوک الف" required>@error('name')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="floors_count">تعداد طبقات *</label><input id="floors_count" type="number" name="floors_count" min="1" value="{{ $v('floors_count', 1) }}" required>@error('floors_count')<div class="e">{{ $message }}</div>@enderror</div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ذخیره</button><a class="btn sec" href="{{ route('blocks.index') }}">انصراف</a></div>
  </form>
</div>
@endsection
