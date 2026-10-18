@php
  $weekdays = [0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه'];
  $editingShift = $editingShift ?? null;
  $scheduleType = old('schedule_type', $editingShift?->shift_date ? 'date' : ($editingShift ? 'weekly' : 'date'));
  $shiftDateValue = old('shift_date', $editingShift?->shift_date ? \App\Support\JalaliDate::format($editingShift->shift_date) : '');
@endphp
@extends('layouts.app')
@section('title', 'شیفت حراست')
@section('content')
<link rel="stylesheet" href="{{ asset('css/jdate.css') }}">
<style>
.shift-page{color:var(--text);--s-line:var(--line,#e4e9ef);--s-muted:var(--muted,#718096)}
.shift-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;padding:clamp(22px,4vw,34px);margin-bottom:20px;border-radius:22px;color:#fff;background:linear-gradient(125deg,#172f4d,#285b78 58%,#398c91);box-shadow:0 16px 36px #183e5522}
.shift-hero h2{font-size:clamp(22px,3vw,30px);font-weight:850;color:#fff;margin:0 0 8px}.shift-hero p{font-size:13px;line-height:1.9;color:#e0f1f5;margin:0;max-width:680px}
.shift-stat{padding:15px 20px;border:1px solid #ffffff35;border-radius:16px;background:#ffffff12;text-align:center;min-width:110px}.shift-stat strong{display:block;font-size:28px;color:#fff}.shift-stat span{font-size:11px;color:#e0f1f5}
.shift-panel{border:1px solid var(--s-line);border-radius:18px;background:var(--card,#fff);overflow:hidden;margin-bottom:18px}.shift-panel-head{padding:18px 21px;border-bottom:1px solid var(--s-line);display:flex;justify-content:space-between;align-items:center;gap:12px}.shift-panel-head h3{font-size:15px;font-weight:800;margin:0 0 4px}.shift-panel-head p{font-size:11px;color:var(--s-muted);margin:0;line-height:1.8}.shift-body{padding:20px}
.shift-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.shift-field{min-width:0}.shift-field label{display:block;font-size:12px;font-weight:700;margin-bottom:7px}.shift-field input,.shift-field select{width:100%;min-width:0;border:1px solid var(--s-line);border-radius:11px;padding:11px 12px;background:var(--card,#fff);color:var(--text);font:inherit;font-size:12px}.shift-field input:focus,.shift-field select:focus{outline:2px solid #4a9dba55;border-color:#438aa8}.shift-help{font-size:11px;color:var(--s-muted);line-height:1.8;margin-top:6px}
.shift-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:17px}.shift-btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:11px;padding:10px 15px;background:#246c84;color:#fff;font-size:12px;font-weight:800;cursor:pointer;text-decoration:none}.shift-btn.secondary{background:var(--track,#f1f4f7);color:var(--text)}.shift-btn.danger{background:#fff0f0;color:#a23d43;border:1px solid #f1d1d3}
.shift-alert{padding:12px 14px;border-radius:12px;margin-bottom:14px;font-size:12px;line-height:1.9}.shift-alert.success{background:#eaf8ef;color:#17643b;border:1px solid #ccebd7}.shift-alert.error{background:#fff2f1;color:#a43c36;border:1px solid #f3d4d1}
.shift-table-wrap{overflow-x:auto}.shift-table{width:100%;border-collapse:collapse;min-width:680px}.shift-table th,.shift-table td{text-align:right;padding:13px 15px;border-bottom:1px solid var(--s-line);font-size:12px;vertical-align:middle}.shift-table th{font-size:10px;color:var(--s-muted);background:var(--track,#f7f9fb);white-space:nowrap}.shift-table tbody tr:last-child td{border-bottom:0}.shift-table td strong{font-weight:800}.shift-meta{font-size:10px;color:var(--s-muted);margin-top:4px}.shift-inline-actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.shift-inline-actions .shift-btn{padding:7px 10px;font-size:10px}.shift-empty{text-align:center;padding:32px 18px;color:var(--s-muted);font-size:12px;line-height:2}
@media(max-width:640px){.shift-grid{grid-template-columns:1fr}.shift-body,.shift-panel-head{padding:16px}.shift-hero{border-radius:18px}.shift-stat{min-width:90px}}
</style>
<div class="shift-page">
  <section class="shift-hero">
    <div><h2>برنامه‌ریزی شیفت حراست</h2><p>شیفت‌های تاریخ‌دار یا برنامه‌های تکرارشونده هفتگی را ثبت کنید، محل استقرار را مشخص کنید و برنامه هر نگهبان را یک‌جا ببینید.</p></div>
    <div class="shift-stat"><strong>{{ \App\Support\Fa::digits($shifts->count()) }}</strong><span>{{ $canManage ? 'شیفت ثبت‌شده' : 'شیفت من' }}</span></div>
  </section>

  @if(session('status'))<div class="shift-alert success" role="status">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="shift-alert error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

  @if($canManage)
    <section class="shift-panel">
      <div class="shift-panel-head"><div><h3>{{ $editingShift ? 'ویرایش شیفت' : 'ثبت شیفت جدید' }}</h3><p>برای یک روز مشخص یا یک روز ثابت در هفته برنامه بسازید.</p></div></div>
      <div class="shift-body">
        <form method="POST" action="{{ $editingShift ? route('shifts.update', $editingShift) : route('shifts.store') }}">
          @csrf
          @if($editingShift) @method('PUT') @endif
          <div class="shift-grid">
            <div class="shift-field"><label for="user_id">نگهبان</label><select id="user_id" name="user_id" required><option value="">انتخاب حراست</option>@foreach($guards as $guard)<option value="{{ $guard->id }}" @selected((string) old('user_id', $editingShift?->user_id) === (string) $guard->id)>{{ $guard->display_name ?: $guard->mobile }}</option>@endforeach</select></div>
            <div class="shift-field"><label for="schedule_type">نوع برنامه</label><select id="schedule_type" name="schedule_type"><option value="date" @selected($scheduleType === 'date')>شیفت در تاریخ مشخص</option><option value="weekly" @selected($scheduleType === 'weekly')>تکرار هفتگی</option></select></div>
            <div class="shift-field" id="shift-date-field"><label for="shift_date">تاریخ شیفت (شمسی)</label><input id="shift_date" name="shift_date" class="jdate" autocomplete="off" placeholder="انتخاب از تقویم شمسی" value="{{ $shiftDateValue }}"><div class="shift-help">تاریخ را از تقویم شمسی انتخاب کنید.</div></div>
            <div class="shift-field" id="shift-weekday-field"><label for="weekday">روز هفته</label><select id="weekday" name="weekday"><option value="">انتخاب روز</option>@foreach($weekdays as $day => $label)<option value="{{ $day }}" @selected((string) old('weekday', $editingShift?->weekday) === (string) $day)>{{ $label }}</option>@endforeach</select></div>
            <div class="shift-field"><label for="start_time">ساعت شروع</label><input id="start_time" name="start_time" type="time" required value="{{ old('start_time', $editingShift ? substr((string) $editingShift->start_time, 0, 5) : '08:00') }}"></div>
            <div class="shift-field"><label for="end_time">ساعت پایان</label><input id="end_time" name="end_time" type="time" required value="{{ old('end_time', $editingShift ? substr((string) $editingShift->end_time, 0, 5) : '16:00') }}"></div>
            <div class="shift-field"><label for="location">محل استقرار (اختیاری)</label><input id="location" name="location" maxlength="150" value="{{ old('location', $editingShift?->location) }}" placeholder="مثلاً لابی یا ورودی پارکینگ"></div>
          </div>
          <div class="shift-actions"><button class="shift-btn" type="submit">{{ $editingShift ? 'ذخیره تغییرات' : 'ثبت شیفت' }}</button>@if($editingShift)<a class="shift-btn secondary" href="{{ route('shifts.index') }}">انصراف</a>@endif</div>
        </form>
      </div>
    </section>
  @endif

  <section class="shift-panel">
    <div class="shift-panel-head"><div><h3>{{ $canManage ? 'همه شیفت‌های ساختمان' : 'برنامه شیفت‌های من' }}</h3><p>{{ $canManage ? 'برنامه‌ها به تفکیک نگهبان، تاریخ یا روز هفته نمایش داده می‌شوند.' : 'در این بخش شیفت‌هایی را می‌بینید که برای شما ثبت شده‌اند.' }}</p></div></div>
    @if($shifts->isEmpty())
      <div class="shift-empty">هنوز شیفتی ثبت نشده است.</div>
    @else
      <div class="shift-table-wrap"><table class="shift-table"><thead><tr><th>نگهبان</th><th>زمان‌بندی</th><th>ساعت</th><th>محل استقرار</th>@if($canManage)<th>عملیات</th>@endif</tr></thead><tbody>
      @foreach($shifts as $shift)
        <tr>
          <td><strong>{{ $shift->user?->display_name ?: $shift->user?->mobile ?: '—' }}</strong></td>
          <td><strong>{{ $shift->shift_date ? \App\Support\JalaliDate::format($shift->shift_date) : 'هر '.$weekdays[(int) $shift->weekday] }}</strong><div class="shift-meta">{{ $shift->shift_date ? 'شیفت تاریخ‌دار' : 'برنامه تکرارشونده هفتگی' }}</div></td>
          <td><strong>{{ \App\Support\Fa::digits(substr((string) $shift->start_time, 0, 5)) }} تا {{ \App\Support\Fa::digits(substr((string) $shift->end_time, 0, 5)) }}</strong></td>
          <td>{{ $shift->location ?: '—' }}</td>
          @if($canManage)<td><div class="shift-inline-actions"><a class="shift-btn" href="{{ route('shifts.edit', $shift) }}">ویرایش</a><form method="POST" action="{{ route('shifts.destroy', $shift) }}" data-confirm="این شیفت حذف شود؟" data-confirm-title="حذف شیفت" data-confirm-icon="warning">@csrf @method('DELETE')<button class="shift-btn danger" type="submit">حذف</button></form></div></td>@endif
        </tr>
      @endforeach
      </tbody></table></div>
    @endif
  </section>
</div>
<script src="{{ asset('js/jdate.js') }}"></script>
<script>
(() => {
  const type = document.getElementById('schedule_type');
  const dateField = document.getElementById('shift-date-field');
  const weekdayField = document.getElementById('shift-weekday-field');
  const dateInput = document.getElementById('shift_date');
  const weekdayInput = document.getElementById('weekday');
  if (!type) return;
  const sync = () => {
    const dateMode = type.value === 'date';
    dateField.hidden = !dateMode;
    weekdayField.hidden = dateMode;
    dateInput.required = dateMode;
    weekdayInput.required = !dateMode;
    dateInput.disabled = !dateMode;
    weekdayInput.disabled = dateMode;
  };
  type.addEventListener('change', sync);
  sync();
})();
</script>
@endsection
