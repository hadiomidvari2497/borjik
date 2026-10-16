@extends('layouts.app')
@section('title', 'درخواست‌های من')
@section('content')
@php $priorityLabels = ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد', 'urgent' => 'فوری']; @endphp
<div class="card">
  <div class="page-h"><h2>درخواست‌های من</h2><span class="muted">درخواست‌ها در پنل مدیر و پرسنل مرتبط ساختمان نیز نمایش داده می‌شوند.</span></div>
  @if($tickets->isEmpty())<div class="empty">هنوز درخواستی ثبت نکرده‌اید.</div>
  @else
    <div class="tbl-wrap"><table class="tbl"><thead><tr><th>شماره</th><th>عنوان</th><th>وضعیت</th><th>مسئول رسیدگی</th><th></th></tr></thead><tbody>
    @foreach($tickets as $ticket)
      <tr><td>{{ $ticket->number }}</td><td><b>{{ $ticket->title }}</b><small style="display:block;color:var(--muted)">{{ $ticket->created_at?->format('Y/m/d H:i') }}</small></td>
      <td>{{ $statuses[$ticket->status->value] ?? $ticket->status->value }}</td><td>{{ $ticket->acceptedBy ? ($ticket->acceptedBy->display_name ?: $ticket->acceptedBy->mobile) : 'در انتظار قبول مسئولیت' }}</td>
      <td><a class="btn sec sm" href="{{ route('my.requests.show', $ticket->id) }}">جزئیات</a></td></tr>
    @endforeach
    </tbody></table></div><div style="margin-top:12px">{{ $tickets->links() }}</div>
  @endif
</div>
<div class="card"><div class="page-h"><h2>ثبت درخواست جدید</h2></div>
<form method="POST" action="{{ route('my.requests.store') }}" style="margin-top:12px">@csrf
  <div class="fgrid">
    <div class="fld"><label for="title">عنوان درخواست *</label><input id="title" name="title" maxlength="200" value="{{ old('title') }}" required placeholder="مثلاً خرابی چراغ راهرو">@error('title')<div class="e">{{ $message }}</div>@enderror</div>
    <div class="fld"><label for="category">موضوع *</label><select id="category" name="category" required>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('category', 'general') === $key)>{{ $label }}</option>@endforeach</select>@error('category')<div class="e">{{ $message }}</div>@enderror</div>
    <div class="fld"><label for="priority">اولویت *</label><select id="priority" name="priority" required>@foreach($priorityLabels as $key => $label)<option value="{{ $key }}" @selected(old('priority', 'medium') === $key)>{{ $label }}</option>@endforeach</select>@error('priority')<div class="e">{{ $message }}</div>@enderror</div>
    <div class="fld"><label for="unit_id">واحد مرتبط (اختیاری)</label><select id="unit_id" name="unit_id"><option value="">بدون انتخاب واحد</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string) old('unit_id') === (string) $unit->id)>{{ $unit->block?->name ? $unit->block->name.' - ' : '' }}واحد {{ $unit->number }}</option>@endforeach</select>@error('unit_id')<div class="e">{{ $message }}</div>@enderror</div>
    <div class="fld"><label for="location">محل مشکل (اختیاری)</label><input id="location" name="location" maxlength="150" value="{{ old('location') }}" placeholder="مثلاً طبقه سوم، کنار آسانسور">@error('location')<div class="e">{{ $message }}</div>@enderror</div>
  </div>
  <div class="fld" style="margin-top:12px"><label for="description">شرح درخواست *</label><textarea id="description" name="description" rows="4" maxlength="10000" required placeholder="مشکل را با جزئیات توضیح دهید">{{ old('description') }}</textarea>@error('description')<div class="e">{{ $message }}</div>@enderror</div>
  <div class="fbar"><button class="btn" type="submit">ثبت درخواست</button></div>
</form></div>
@endsection
