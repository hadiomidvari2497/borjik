@extends('layouts.app')
@section('title', 'درخواست‌های من')
@section('content')
@php
  $priorityLabels = ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد', 'urgent' => 'فوری'];
  $statusClasses = ['new' => 'new', 'review' => 'review', 'doing' => 'doing', 'waiting' => 'waiting', 'done' => 'done', 'closed' => 'closed'];
@endphp
<style>
.myreq-page{display:grid;gap:18px}.myreq-hero{position:relative;overflow:hidden;border-radius:24px;padding:25px;background:linear-gradient(115deg,#174d49 0%,#217f74 58%,#35a99a 100%);color:#fff;box-shadow:0 12px 28px rgba(24,103,94,.2)}.myreq-hero:after{content:"";position:absolute;width:230px;height:230px;border:1px solid rgba(255,255,255,.15);border-radius:50%;left:-55px;top:-130px;box-shadow:0 0 0 25px rgba(255,255,255,.04),0 0 0 52px rgba(255,255,255,.035)}.myreq-hero-inner{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;gap:18px}.myreq-kicker{font-size:11px;opacity:.78;margin-bottom:7px}.myreq-hero h2{font-size:22px;margin:0 0 8px;font-weight:800}.myreq-hero p{font-size:13px;line-height:1.9;opacity:.86;max-width:620px}.myreq-hero-stat{flex:none;min-width:105px;text-align:center;border:1px solid rgba(255,255,255,.24);background:rgba(255,255,255,.12);border-radius:17px;padding:13px 16px;backdrop-filter:blur(8px)}.myreq-hero-stat b{display:block;font-size:27px;line-height:1.3}.myreq-hero-stat span{font-size:11px;opacity:.82}.myreq-list{padding:0;overflow:hidden}.myreq-list-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:19px 22px;border-bottom:1px solid var(--line)}.myreq-list-head h3{font-size:15px;margin:0 0 4px}.myreq-list-head p{font-size:12px;color:var(--muted)}.myreq-table-wrap{overflow-x:auto}.myreq-table{width:100%;border-collapse:separate;border-spacing:0;min-width:570px}.myreq-table th{padding:13px 18px;text-align:right;font-size:11px;color:var(--muted);background:color-mix(in srgb,var(--track) 38%,transparent)}.myreq-table td{padding:15px 18px;border-top:1px solid var(--line);vertical-align:middle;font-size:12px}.myreq-table tbody tr:hover{background:var(--brand-soft)}.myreq-title{font-size:13px;font-weight:800;line-height:1.8;text-decoration:none;color:var(--text)}.myreq-title:hover{color:var(--brand-d);text-decoration:underline;text-underline-offset:4px}.myreq-meta{display:flex;gap:7px;flex-wrap:wrap;color:var(--muted);font-size:10px;margin-top:4px}.myreq-pill{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:9px;font-size:11px;font-weight:700;white-space:nowrap}.myreq-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.myreq-status-new{background:rgba(59,130,196,.12);color:#4383bb}.myreq-status-review{background:rgba(232,163,61,.15);color:#b57b20}.myreq-status-doing,.myreq-status-done{background:rgba(42,155,143,.15);color:#21877c}.myreq-status-waiting{background:rgba(145,120,190,.15);color:#9275bd}.myreq-status-closed{background:var(--track);color:var(--muted)}.myreq-empty{padding:30px 20px;text-align:center;color:var(--muted);font-size:12px}.myreq-form-card{padding:0;overflow:hidden}.myreq-form-head{padding:22px 24px;background:linear-gradient(135deg,var(--brand-soft),transparent);border-bottom:1px solid var(--line);display:flex;align-items:center;gap:13px}.myreq-form-icon{width:46px;height:46px;display:grid;place-items:center;border-radius:15px;background:var(--brand);color:#fff;font-size:23px;box-shadow:0 6px 15px rgba(42,155,143,.22)}.myreq-form-head h3{font-size:16px;margin:0 0 4px}.myreq-form-head p{font-size:12px;color:var(--muted)}.myreq-form-body{padding:22px 24px}.myreq-form-body .fld label{font-weight:700;font-size:12px}.myreq-form-body input,.myreq-form-body select,.myreq-form-body textarea{border-radius:12px}.myreq-form-body textarea{min-height:110px}.myreq-form-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px solid var(--line)}.myreq-form-foot small{color:var(--muted);font-size:11px}.myreq-submit{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:12px;padding:11px 18px;font-weight:800;box-shadow:0 5px 12px rgba(42,155,143,.2)}@media(max-width:680px){.myreq-hero{padding:20px}.myreq-hero-inner{align-items:flex-start}.myreq-hero h2{font-size:19px}.myreq-hero-stat{min-width:82px;padding:10px}.myreq-hero-stat b{font-size:23px}.myreq-list-head{padding:16px}.myreq-form-head,.myreq-form-body{padding:17px}.myreq-table{min-width:550px}}
</style>
<div class="myreq-page">
  <section class="myreq-hero">
    <div class="myreq-hero-inner">
      <div><div class="myreq-kicker">BORJIK · MY REQUESTS</div><h2>پیگیری تا رسیدن به نتیجه</h2><p>درخواست جدیدت را ثبت کن و وضعیت رسیدگی، مسئول پیگیری، گفت‌وگوها و تاریخچه را از همین‌جا دنبال کن.</p></div>
      <div class="myreq-hero-stat"><b>{{ \App\Support\Fa::num($tickets->total()) }}</b><span>درخواست ثبت‌شده</span></div>
    </div>
  </section>
  <section class="card myreq-list">
    <div class="myreq-list-head"><div><h3>درخواست‌های من</h3><p>برای باز کردن پرونده و دیدن تاریخچه، روی عنوان درخواست بزنید.</p></div></div>
    @if($tickets->isEmpty())
      <div class="myreq-empty">هنوز درخواستی ثبت نکرده‌اید. از فرم پایین اولین درخواستتان را ثبت کنید.</div>
    @else
      <div class="myreq-table-wrap"><table class="myreq-table"><thead><tr><th>عنوان درخواست</th><th>وضعیت</th><th>مسئول رسیدگی</th></tr></thead><tbody>
      @foreach($tickets as $ticket)
        @php $status = $ticket->status->value; $owner = $ticket->acceptedBy; $ownerName = $owner?->display_name ?: $owner?->mobile; @endphp
        <tr>
          <td><a class="myreq-title" href="{{ route('my.requests.show', $ticket->id) }}">{{ $ticket->title }}</a><div class="myreq-meta"><span>{{ $ticket->number }}</span><span>·</span><span>{{ $ticket->created_at?->format('Y/m/d H:i') }}</span></div></td>
          <td><span class="myreq-pill myreq-status-{{ $statusClasses[$status] ?? 'new' }}">{{ $statuses[$status] ?? $status }}</span></td>
          <td>{{ $ownerName ?: 'در انتظار قبول مسئولیت' }}</td>
        </tr>
      @endforeach
      </tbody></table></div><div style="padding:16px 20px;border-top:1px solid var(--line)">{{ $tickets->links() }}</div>
    @endif
  </section>
  <section class="card myreq-form-card">
    <div class="myreq-form-head"><div class="myreq-form-icon">＋</div><div><h3>ثبت درخواست جدید</h3><p>هرچه شرح دقیق‌تری بنویسی، رسیدگی سریع‌تر و دقیق‌تر انجام می‌شود.</p></div></div>
    <div class="myreq-form-body">
      <form method="POST" action="{{ route('my.requests.store') }}">@csrf
        <div class="fgrid">
          <div class="fld"><label for="title">عنوان درخواست *</label><input id="title" name="title" maxlength="200" value="{{ old('title') }}" required placeholder="مثلاً خرابی چراغ راهرو">@error('title')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld"><label for="category">موضوع *</label><select id="category" name="category" required>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('category', 'general') === $key)>{{ $label }}</option>@endforeach</select>@error('category')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld"><label for="priority">اولویت *</label><select id="priority" name="priority" required>@foreach($priorityLabels as $key => $label)<option value="{{ $key }}" @selected(old('priority', 'medium') === $key)>{{ $label }}</option>@endforeach</select>@error('priority')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld"><label for="unit_id">واحد مرتبط (اختیاری)</label><select id="unit_id" name="unit_id"><option value="">بدون انتخاب واحد</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string) old('unit_id') === (string) $unit->id)>{{ $unit->block?->name ? $unit->block->name.' - ' : '' }}واحد {{ $unit->number }}</option>@endforeach</select>@error('unit_id')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld"><label for="location">محل مشکل (اختیاری)</label><input id="location" name="location" maxlength="150" value="{{ old('location') }}" placeholder="مثلاً طبقه سوم، کنار آسانسور">@error('location')<div class="e">{{ $message }}</div>@enderror</div>
        </div>
        <div class="fld" style="margin-top:16px"><label for="description">شرح درخواست *</label><textarea id="description" name="description" rows="4" maxlength="10000" required placeholder="مشکل را با جزئیات توضیح دهید">{{ old('description') }}</textarea>@error('description')<div class="e">{{ $message }}</div>@enderror</div>
        <div class="myreq-form-foot"><small>درخواست برای مدیر ساختمان و پرسنل مرتبط قابل مشاهده خواهد بود.</small><button class="btn myreq-submit" type="submit"><span aria-hidden="true">＋</span> ثبت درخواست</button></div>
      </form>
    </div>
  </section>
</div>
@endsection
