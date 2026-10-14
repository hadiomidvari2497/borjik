@extends('layouts.app')
@section('title', 'ساخت گروهی واحد')
@section('content')
<div class="card">
  <div class="page-h"><h2>ساخت گروهی واحد</h2></div>
  <p class="sub" style="color:var(--muted);font-size:13px;margin:6px 0 12px">واحدها با شماره‌گذاری پشت‌سرهم ساخته می‌شوند و بعداً تک‌تک قابل ویرایش‌اند. یا همه ساخته می‌شوند یا هیچ‌کدام (اگر سقف اشتراک کم باشد).</p>
  @if($blocks->isEmpty())
    <div class="empty">ابتدا باید یک بلوک بسازید. <a href="{{ route('blocks.create') }}">ساخت بلوک</a></div>
  @else
  <form method="POST" action="{{ route('units.bulk.store') }}">@csrf
    <div class="fgrid">
      <div class="fld"><label for="block_id">بلوک *</label><select id="block_id" name="block_id">@foreach($blocks as $b)<option value="{{ $b->id }}" @selected((int) old('block_id') === $b->id)>{{ $b->name }}</option>@endforeach</select>@error('block_id')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="count">تعداد واحد *</label><input id="count" type="number" name="count" min="1" max="500" value="{{ old('count', 10) }}">@error('count')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="start_number">شماره‌ی اولین واحد *</label><input id="start_number" name="start_number" inputmode="numeric" value="{{ old('start_number', 1) }}">@error('start_number')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="units_per_floor">تعداد واحد در هر طبقه *</label><input id="units_per_floor" type="number" name="units_per_floor" min="1" value="{{ old('units_per_floor', 2) }}">@error('units_per_floor')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="start_floor">طبقه‌ی اولین واحد *</label><input id="start_floor" type="number" name="start_floor" value="{{ old('start_floor', 1) }}"><div class="h">۰ = همکف (عدد منفی = زیرزمین)</div>@error('start_floor')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="area_m2">متراژ هر واحد *</label><input id="area_m2" type="number" step="0.01" name="area_m2" value="{{ old('area_m2') }}">@error('area_m2')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="bedrooms">تعداد خواب</label><input id="bedrooms" type="number" min="0" name="bedrooms" value="{{ old('bedrooms') }}"></div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ساخت واحدها</button><a class="btn sec" href="{{ route('units.index') }}">انصراف</a></div>
  </form>
  @endif
</div>
@endsection
