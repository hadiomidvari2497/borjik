@php($F = \App\Support\Fa::class)
@extends('layouts.app')
@section('title', 'موجودی ملزومات')
@section('content')
<div class="card">
  <div class="page-h">
    <div><h2>موجودی ملزومات</h2><p class="muted">ثبت اقلام، کنترل موجودی و پیگیری ورود و مصرف</p></div>
  </div>
  <div class="fgrid" style="margin:16px 0">
    <div class="stat"><span>اقلام ثبت‌شده</span><strong>{{ $F::num($totalItems) }}</strong></div>
    <div class="stat"><span>اقلام در حد هشدار</span><strong>{{ $F::num($lowCount) }}</strong></div>
    <div class="stat"><span>گردش‌های اخیر</span><strong>{{ $F::num($movements->count()) }}</strong></div>
  </div>
  @if($supplies->isEmpty())
    <div class="empty">هنوز قلمی ثبت نشده است. از فرم زیر اولین قلم ملزومات را اضافه کن.</div>
  @else
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>نام قلم</th><th>موجودی</th><th>حد هشدار</th><th>وضعیت</th><th>ویرایش مشخصات</th><th>ثبت ورود / مصرف</th></tr></thead>
      <tbody>
      @foreach($supplies as $supply)
        <tr>
          <td><b>{{ $supply->name }}</b><div class="muted">{{ $supply->unit ?: 'بدون واحد' }}</div></td>
          <td><strong>{{ $F::num(number_format((float) $supply->quantity, 2, '.', '')) }}</strong> {{ $supply->unit }}</td>
          <td>{{ $F::num(number_format((float) $supply->min_quantity, 2, '.', '')) }} {{ $supply->unit }}</td>
          <td>@if((float) $supply->quantity <= (float) $supply->min_quantity)<span class="badge warn">نیاز به تأمین</span>@else<span class="badge">موجود</span>@endif</td>
          <td style="min-width:210px">
            <form method="POST" action="{{ route('supplies.update', $supply) }}">@csrf @method('PUT')
              <input name="name" aria-label="نام قلم" value="{{ $supply->name }}" required>
              <div style="display:flex;gap:6px;margin-top:6px"><input name="unit" aria-label="واحد" value="{{ $supply->unit }}" placeholder="واحد"><input name="min_quantity" aria-label="حد هشدار" type="number" min="0" step="0.01" value="{{ $supply->min_quantity }}" required></div>
              <button class="btn sec sm" style="margin-top:6px" type="submit">ذخیره مشخصات</button>
            </form>
            @if((int) $supply->movements_count === 0)
              <form method="POST" action="{{ route('supplies.destroy', $supply) }}" data-confirm="این قلم حذف شود؟" data-confirm-title="حذف ملزومات" data-confirm-icon="warning" style="margin-top:6px">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف قلم</button></form>
            @endif
          </td>
          <td style="min-width:210px">
            <form method="POST" action="{{ route('supplies.move', $supply) }}">@csrf
              <select name="direction" aria-label="نوع گردش"><option value="in">ورود به انبار (+)</option><option value="out">مصرف / خروج (−)</option></select>
              <input name="quantity" type="number" min="0.01" step="0.01" placeholder="مقدار" required style="margin-top:6px">
              <input name="reason" maxlength="150" placeholder="علت / توضیح" required style="margin-top:6px">
              <button class="btn sm" type="submit" style="margin-top:6px">ثبت گردش</button>
            </form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  @endif
</div>

<div class="card">
  <div class="page-h"><h2>افزودن قلم ملزومات</h2></div>
  <form method="POST" action="{{ route('supplies.store') }}" style="margin-top:10px">@csrf
    <div class="fgrid">
      <div class="fld"><label for="supply-name">نام قلم *</label><input id="supply-name" name="name" value="{{ old('name') }}" placeholder="مثلاً لامپ LED" required>@error('name')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="supply-unit">واحد شمارش</label><input id="supply-unit" name="unit" value="{{ old('unit') }}" placeholder="عدد، بسته، لیتر">@error('unit')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="supply-min">حد هشدار موجودی *</label><input id="supply-min" name="min_quantity" type="number" min="0" step="0.01" value="{{ old('min_quantity', 0) }}" required>@error('min_quantity')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="supply-initial">موجودی اولیه</label><input id="supply-initial" name="initial_quantity" type="number" min="0" step="0.01" value="{{ old('initial_quantity', 0) }}">@error('initial_quantity')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="supply-reason">توضیح موجودی اولیه</label><input id="supply-reason" name="reason" value="{{ old('reason') }}" maxlength="150" placeholder="مثلاً خرید اولیه"></div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ثبت قلم</button></div>
  </form>
</div>

<div class="card">
  <div class="page-h"><h2>آخرین گردش‌های موجودی</h2></div>
  @if($movements->isEmpty())<div class="empty">هنوز گردش موجودی ثبت نشده است.</div>
  @else
    <div class="tbl-wrap"><table class="tbl"><thead><tr><th>قلم</th><th>نوع</th><th>مقدار</th><th>توضیح</th><th>ثبت‌کننده</th><th>زمان ثبت</th></tr></thead><tbody>
      @foreach($movements as $movement)
        <tr><td>{{ $movement->supply?->name ?? 'قلم حذف‌شده' }}</td><td>{{ (float) $movement->delta > 0 ? 'ورود' : 'مصرف / خروج' }}</td><td>{{ (float) $movement->delta > 0 ? '+' : '−' }}{{ $F::num(number_format(abs((float) $movement->delta), 2, '.', '')) }} {{ $movement->supply?->unit }}</td><td>{{ $movement->reason ?: '—' }}</td><td>{{ $movement->user?->display_name ?: $movement->user?->mobile ?: '—' }}</td><td>{{ $F::num($movement->created_at?->format('Y/m/d H:i')) }}</td></tr>
      @endforeach
    </tbody></table></div>
  @endif
</div>
@endsection
