@extends('admin.layout')
@section('title', 'تحلیل هوشمند درخواست‌های پشتیبانی')
@section('content')
@php($F = \App\Support\Fa::class)
<style>
.si-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:16px}.si-card{border:1px solid var(--line);border-radius:13px;padding:14px}.si-card small{display:block;color:var(--muted);font-size:11px}.si-card strong{display:block;font-size:23px;margin-top:5px}.si-table{width:100%;border-collapse:collapse}.si-table th,.si-table td{padding:11px 9px;border-bottom:1px solid var(--line);text-align:right;font-size:11px}.si-table th{color:var(--muted)}.si-table a{color:var(--text);text-decoration:none;font-weight:800}.si-empty{text-align:center;padding:28px;color:var(--muted)}
@media(max-width:650px){.si-grid{grid-template-columns:1fr 1fr}.si-table-wrap{overflow:auto}.si-table{min-width:580px}}
</style>
<div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:15px">
  <div><h2 style="font-size:17px;font-weight:900;margin:0 0 5px">تحلیل هوشمند درخواست‌ها</h2><p style="font-size:11px;color:var(--muted);margin:0">شناسایی نشانه‌های فوریت و گروه‌بندی درخواست‌های مشابه بر اساس متن تیکت‌ها</p></div>
  <a href="{{ route('admin.support.index') }}" style="font-size:11px;text-decoration:none">بازگشت به مرکز پشتیبانی ←</a>
</div>
<div class="si-grid">
  <div class="si-card"><small>تیکت‌های تحلیل‌شده</small><strong>{{ $F::num($analyzed) }}</strong></div>
  <div class="si-card"><small>تیکت‌های مشابه شناسایی‌شده</small><strong>{{ $F::num($duplicates) }}</strong></div>
  <div class="si-card"><small>گروه‌های پرتکرار</small><strong>{{ $F::num($clusters->count()) }}</strong></div>
</div>
<section class="card" style="padding:14px;margin-bottom:14px">
  <b style="display:block;font-size:12px;margin-bottom:12px">توزیع پیشنهادهای اولویت</b>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:9px">
    @foreach(['urgent'=>'فوری','high'=>'زیاد','normal'=>'عادی','low'=>'کم'] as $key=>$label)
      <div style="padding:10px;border:1px solid var(--line);border-radius:10px"><small style="display:block;color:var(--muted);font-size:10px">{{ $label }}</small><b style="font-size:18px">{{ $F::num((int)($suggestions[$key] ?? 0)) }}</b></div>
    @endforeach
  </div>
</section>
<section class="card si-table-wrap" style="padding:14px">
  <b style="display:block;font-size:12px;margin-bottom:10px">موضوعات پرتکرار و درخواست‌های مشابه</b>
  <table class="si-table"><thead><tr><th>درخواست مرجع</th><th>موضوع</th><th>تعداد موارد مشابه</th><th>آخرین تکرار</th></tr></thead><tbody>
  @forelse($clusters as $cluster)
    <tr><td><a href="{{ route('admin.support.show',$cluster['ticket']) }}">{{ $cluster['ticket']->number }}</a></td><td>{{ \Illuminate\Support\Str::limit($cluster['ticket']->subject,55) }}</td><td>{{ $F::num($cluster['total_count']) }}</td><td>{{ $cluster['latest'] ? $F::date($cluster['latest'],'j F Y · H:i') : '—' }}</td></tr>
  @empty
    <tr><td colspan="4" class="si-empty">هنوز مورد مشابهی شناسایی نشده است. با ثبت تیکت‌های جدید، گروه‌های پرتکرار در این صفحه نمایش داده می‌شوند.</td></tr>
  @endforelse
  </tbody></table>
</section>
@endsection
