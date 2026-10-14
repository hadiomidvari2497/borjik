@php $F = \App\Support\Fa::class; $monthName = \App\Support\Fa::MONTHS[$month - 1]; @endphp
@extends('layouts.app')
@section('title', 'هزینه‌های ماهانه')
@section('content')
<div class="card">
  <div class="page-h">
    <h2>هزینه‌های {{ $monthName }} {{ $F::digits($year) }}</h2>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <a class="btn sec sm" href="{{ route('expenses.index', ['year' => $prev[0], 'month' => $prev[1]]) }}">‹ ماه قبل</a>
      <a class="btn sec sm" href="{{ route('expenses.index', ['year' => $next[0], 'month' => $next[1]]) }}">ماه بعد ›</a>
      @if($period)<a class="btn sm" href="{{ route('charges.show', $period->id) }}">رفتن به محاسبه‌ی شارژ</a>@endif
    </div>
  </div>
  @if($locked)<div class="warnbox" style="margin:10px 0">شارژ این ماه منتشر شده است و هزینه‌ها قابل تغییر نیست.</div>@endif
  @if($period?->status?->value === 'calculated')<div class="warnbox" style="margin:10px 0">این ماه محاسبه شده است؛ با تغییر هزینه‌ها باید شارژ را دوباره محاسبه کنید.</div>@endif

  @if($expenses->isEmpty())<div class="empty">برای این ماه هزینه‌ای ثبت نشده است.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>عنوان</th><th>دسته</th><th>مبلغ</th><th></th></tr></thead>
    <tbody>
    @foreach($expenses as $e)
      <tr><td><b>{{ $e->title }}</b></td><td>{{ $e->category?->name ?? '—' }}</td><td>{{ $F::money($e->amount) }}</td>
        <td class="act">@unless($locked)<form method="POST" action="{{ route('expenses.destroy', $e->id) }}" onsubmit="return confirm('این هزینه حذف شود؟')">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف</button></form>@endunless</td></tr>
    @endforeach
    <tr><td colspan="2"><b>جمع هزینه‌ها</b></td><td colspan="2"><b>{{ $F::money($total) }}</b></td></tr>
    </tbody>
  </table></div>
  @endif
</div>

@unless($locked)
<div class="card">
  <div class="page-h"><h2>ثبت هزینه‌ی جدید</h2></div>
  <form method="POST" action="{{ route('expenses.store') }}" style="margin-top:10px">@csrf
    <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
    <div class="fgrid">
      <div class="fld"><label for="category_id">دسته</label>
        <select id="category_id" name="category_id"><option value="">بدون دسته (تقسیم مساوی)</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected((int) old('category_id') === $c->id)>{{ $c->name }}</option>@endforeach</select>@error('category_id')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="title">عنوان *</label><input id="title" name="title" value="{{ old('title') }}" placeholder="مثلاً قبض آب مهر" required>@error('title')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="amount">مبلغ (تومان) *</label><input id="amount" name="amount" inputmode="numeric" value="{{ old('amount') }}" required>@error('amount')<div class="e">{{ $message }}</div>@enderror</div>
    </div>
    <div class="fbar"><button class="btn" type="submit">ثبت هزینه</button></div>
  </form>
</div>
@endunless
@endsection
