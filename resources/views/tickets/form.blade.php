@extends('layouts.app')
@section('title', $ticket ? 'ویرایش تسک' : 'ایجاد تسک')
@section('content')
<style>
.task-form-page{max-width:980px;margin:auto;display:grid;gap:18px}.task-form-hero{padding:22px;border-radius:20px;background:linear-gradient(115deg,#174d49,#217f74 60%,#35a99a);color:#fff}.task-form-hero h2{font-size:21px;margin:0 0 7px}.task-form-hero p{font-size:12px;opacity:.86;line-height:1.9}.task-form-body{padding:22px}.task-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.task-form-body label{font-size:12px;font-weight:800}.task-form-body input,.task-form-body select,.task-form-body textarea{border-radius:11px}.task-form-body .fld{min-width:0}.task-form-help{font-size:10px;color:var(--muted);line-height:1.8;margin-top:6px}.task-form-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:20px;padding-top:18px;border-top:1px solid var(--line)}.task-form-foot a{color:var(--muted);text-decoration:none;font-size:12px}@media(max-width:650px){.task-form-grid{grid-template-columns:1fr}.task-form-body{padding:16px}}
</style>
<div class="task-form-page">
  <section class="task-form-hero"><h2>{{ $ticket ? 'ویرایش تسک · '.$ticket->number : 'ایجاد تسک جدید' }}</h2><p>عنوان، جزئیات، مهلت و مسئولان را مشخص کنید. انتخاب نقش، تسک را به تمام پرسنل فعال آن نقش ارجاع می‌دهد؛ می‌توانید افراد مشخصی را هم اضافه کنید.</p></section>
  <section class="card task-form-body">
    <form method="POST" action="{{ $ticket ? route('tickets.update', $ticket->id) : route('tickets.store') }}" enctype="multipart/form-data">@csrf @if($ticket) @method('PUT') @endif
      <div class="task-form-grid">
        <div class="fld"><label for="title">عنوان تسک *</label><input id="title" name="title" maxlength="200" value="{{ old('title', $ticket?->title) }}" required placeholder="مثلاً تعمیر چراغ راهروی طبقه دوم">@error('title')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="category">دسته‌بندی *</label><select id="category" name="category" required>@foreach($categories as $key=>$label)<option value="{{ $key }}" @selected(old('category', $ticket?->category ?? 'maintenance')===$key)>{{ $label }}</option>@endforeach</select>@error('category')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="priority">اولویت *</label><select id="priority" name="priority" required>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected(old('priority', $ticket?->priority?->value ?? 'medium')===$key)>{{ $label }}</option>@endforeach</select>@error('priority')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="deadline-date">مهلت انجام</label><div style="display:grid;grid-template-columns:minmax(0,1fr) 115px;gap:8px"><input id="deadline-date" class="jdate" type="text" name="deadline_date" value="{{ old('deadline_date', \App\Support\JalaliDate::format($ticket?->deadline)) }}" placeholder="تاریخ شمسی" autocomplete="off"><input id="deadline-time" type="time" name="deadline_time" value="{{ old('deadline_time', $ticket?->deadline?->format('H:i')) }}" aria-label="ساعت مهلت"></div><input id="deadline" type="hidden" name="deadline" value="{{ old('deadline') }}">@error('deadline')<div class="e">{{ $message }}</div>@enderror</div>
<script>
    (() => {
        const form = document.querySelector('form[method="POST"]');
        const date = document.getElementById('deadline-date');
        const time = document.getElementById('deadline-time');
        const output = document.getElementById('deadline');
        if (!form || !date || !time || !output) return;
        form.addEventListener('submit', () => {
            const parsed = window.JDate.parse(date.value);
            if (!parsed) { output.value = ''; return; }
            const gregorian = window.JDate.toGregorian(parsed.y, parsed.m, parsed.d);
            const pad = value => String(value).padStart(2, '0');
            output.value = gregorian.gy + '-' + pad(gregorian.gm) + '-' + pad(gregorian.gd) + 'T' + (time.value || '23:59');
        });
    })();
</script>
        <div class="fld"><label for="target_role_id">ارجاع به یک نقش</label><select id="target_role_id" name="target_role_id"><option value="">انتخاب نشده</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string)old('target_role_id', $targetRoleId ?? '')===(string)$role->id)>{{ $role->name_fa }}</option>@endforeach</select><p class="task-form-help">در صورت انتخاب نقش، تسک برای تمام پرسنل فعال آن نقش ارسال می‌شود.</p>@error('target_role_id')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="user_ids">ارجاع به افراد مشخص</label><select id="user_ids" name="user_ids[]" multiple size="4">@foreach($staff as $person)<option value="{{ $person->id }}" @selected(collect(old('user_ids', $selectedUserIds ?? []))->contains((string)$person->id)||collect(old('user_ids', $selectedUserIds ?? []))->contains($person->id))>{{ $person->display_name ?: $person->mobile }} — {{ $person->mobile }}</option>@endforeach</select><p class="task-form-help">برای انتخاب چند نفر، در رایانه کلید Ctrl یا Command را نگه دارید.</p>@error('user_ids.*')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="unit_id">واحد مرتبط (اختیاری)</label><select id="unit_id" name="unit_id"><option value="">بدون واحد مشخص</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string)old('unit_id', $ticket?->unit_id)===(string)$unit->id)>{{ $unit->block?->name ? $unit->block->name.' - ' : '' }}واحد {{ $unit->number }}</option>@endforeach</select>@error('unit_id')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld"><label for="location">محل انجام کار</label><input id="location" name="location" maxlength="150" value="{{ old('location', $ticket?->location) }}" placeholder="مثلاً موتورخانه یا ورودی غربی">@error('location')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld" style="grid-column:1/-1"><label for="description">شرح کامل کار *</label><textarea id="description" name="description" rows="5" maxlength="10000" required placeholder="شرح کار، مشکل، ابزار یا نکات ایمنی موردنیاز را بنویسید.">{{ old('description', $ticket?->description) }}</textarea>@error('description')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="fld" style="grid-column:1/-1"><label for="attachments">پیوست فایل یا عکس (حداکثر ۵ فایل، هرکدام ۱۰ مگابایت)</label><input id="attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"><p class="task-form-help">فرمت‌های مجاز: JPG، PNG، WEBP و PDF. فایل‌ها خصوصی ذخیره می‌شوند و فقط افراد مجاز به تسک می‌توانند دریافتشان کنند.</p>@error('attachments.*')<div class="e">{{ $message }}</div>@enderror</div>
      </div>
      <div class="task-form-foot"><a href="{{ $ticket ? route('tickets.show', $ticket->id) : route('tickets.index') }}">← بازگشت</a><button class="btn" type="submit">{{ $ticket ? 'ذخیره تغییرات' : 'ثبت تسک و ارجاع' }}</button></div>
    </form>
  </section>
</div>
@endsection
