@extends('layouts.app')
@section('title','مرکز عملیات ساختمان')
@section('content')
@php($money = fn($n) => $F::digits(number_format((int)$n)))
<style>
.ops-hero{padding:22px;border-radius:18px;background:linear-gradient(125deg,#164f49,#247d6c);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}.ops-hero h2{margin:0 0 6px;font-size:22px}.ops-hero p{margin:0;color:#d7eee8;line-height:1.9}.ops-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}.ops-stat,.ops-panel{border:1px solid var(--line);border-radius:15px;padding:16px;background:var(--surface)}.ops-stat span,.ops-muted{color:var(--muted);font-size:12px}.ops-stat strong{display:block;font-size:23px;margin:8px 0}.ops-panels{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.ops-panel h3{margin:0 0 12px;font-size:15px}.ops-list{list-style:none;padding:0;margin:0}.ops-list li{padding:10px 0;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:12px}.ops-list li:last-child{border-bottom:0}.ops-list a{color:var(--text);text-decoration:none;font-weight:700}.ops-badge{font-size:11px;border-radius:20px;padding:4px 8px;background:#fff0df;color:#925b00;white-space:nowrap}.ops-empty{padding:14px;color:var(--muted);font-size:13px}.ops-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.ops-actions a{font-size:12px;text-decoration:none;border:1px solid var(--line);padding:7px 10px;border-radius:9px;color:var(--text)}
@media(max-width:900px){.ops-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ops-panels{grid-template-columns:1fr}}@media(max-width:480px){.ops-grid{grid-template-columns:1fr 1fr}.ops-stat{padding:12px}.ops-stat strong{font-size:19px}}
</style>
<div class="ops-hero"><div><h2>نمای کلی عملیات امروز</h2><p>مواردی که نیاز به پیگیری دارند، کارهای هفته و وضعیت خدمات ساختمان را یکجا ببین.</p></div><div class="ops-actions"><a href="{{ route('maintenance.index') }}" style="color:#fff;border-color:#8ac5b8">برنامه تعمیرات</a><a href="{{ route('treasury.index') }}" style="color:#fff;border-color:#8ac5b8">دفتر خزانه</a></div></div>
<div class="ops-grid">
 <div class="ops-stat"><span>درخواست و تسک باز</span><strong>{{ $F::digits($openTicketsCount) }}</strong><span>موارد هنوز بسته‌نشده</span></div>
 <div class="ops-stat"><span>کارهای عقب‌افتاده</span><strong>{{ $F::digits($overdueTickets->count()) }}</strong><span>نیازمند پیگیری فوری</span></div>
 <div class="ops-stat"><span>بدهی سررسیدگذشته</span><strong>{{ $money($overdueDebt) }}</strong><span>{{ $F::digits($overdueInvoicesCount) }} صورت‌حساب</span></div>
 <div class="ops-stat"><span>فیش‌های در انتظار بررسی</span><strong>{{ $F::digits($pendingReceipts) }}</strong><span>نیازمند تأیید مدیر</span></div>
</div>
<div class="ops-panels">
 <section class="ops-panel"><h3>درخواست‌ها و کارهای عقب‌افتاده</h3>
  @forelse($overdueTickets as $ticket)<ul class="ops-list"><li><div><a href="{{ $ticket->kind->value === 'request' ? route('requests.show',$ticket) : route('tickets.show',$ticket) }}">{{ $ticket->title }}</a><div class="ops-muted">مهلت: {{ $F::date($ticket->deadline,'j F Y H:i') }}</div></div><span class="ops-badge">عقب‌افتاده</span></li></ul>@empty<div class="ops-empty">مورد عقب‌افتاده‌ای وجود ندارد. عالیه!</div>@endforelse
  <div class="ops-actions"><a href="{{ route('requests.index') }}">درخواست‌های ساکنین</a><a href="{{ route('tickets.index') }}">تسک‌ها</a></div>
 </section>
 <section class="ops-panel"><h3>کارهای هفت روز آینده</h3>
  @forelse($weekTasks as $task)<ul class="ops-list"><li><div><a href="{{ $task->kind->value === 'request' ? route('requests.show',$task) : route('tickets.show',$task) }}">{{ $task->title }}</a><div class="ops-muted">{{ $task->deadline ? $F::date($task->deadline,'j F Y H:i') : 'بدون مهلت' }}</div></div></li></ul>@empty<div class="ops-empty">کار زمان‌بندی‌شده‌ای برای هفت روز آینده نیست.</div>@endforelse
 </section>
 <section class="ops-panel"><h3>شیفت حراست امروز</h3>
  @forelse($shifts as $shift)<ul class="ops-list"><li><div><strong>{{ $shift->user?->display_name ?: $shift->user?->mobile ?: 'نگهبان' }}</strong><div class="ops-muted">{{ $shift->location ?: 'محل استقرار مشخص نشده' }}</div></div><span>{{ $F::digits(substr((string)$shift->start_time,0,5)) }} تا {{ $F::digits(substr((string)$shift->end_time,0,5)) }}</span></li></ul>@empty<div class="ops-empty">برای امروز شیفتی ثبت نشده است.</div>@endforelse
  <div class="ops-actions"><a href="{{ route('shifts.index') }}">مدیریت شیفت‌ها</a></div>
 </section>
 <section class="ops-panel"><h3>سرویس‌های پیشِ‌رو</h3>
  @forelse($maintenanceDue as $plan)<ul class="ops-list"><li><div><strong>{{ $plan->title }}</strong><div class="ops-muted">{{ $plan->asset?->name }} · {{ $F::date($plan->next_due_at,'j F Y') }}</div></div><span class="ops-badge">{{ $plan->next_due_at->lt(today()) ? 'معوق' : 'پیشِ‌رو' }}</span></li></ul>@empty<div class="ops-empty">سرویس دوره‌ای سررسیدشده‌ای در دو هفته آینده ثبت نشده.</div>@endforelse
  <div class="ops-actions"><a href="{{ route('maintenance.index') }}">برنامه نگهداری</a></div>
 </section>
 <section class="ops-panel"><h3>ملزومات رو به اتمام</h3>
  @forelse($lowSupplies as $supply)<ul class="ops-list"><li><div><strong>{{ $supply->name }}</strong><div class="ops-muted">حداقل موردنیاز: {{ $F::digits($supply->min_quantity) }} {{ $supply->unit }}</div></div><span class="ops-badge">موجودی {{ $F::digits($supply->quantity) }}</span></li></ul>@empty<div class="ops-empty">هیچ قلمی زیر حداقل موجودی نیست.</div>@endforelse
  <div class="ops-actions"><a href="{{ route('supplies.index') }}">مدیریت ملزومات</a></div>
 </section>
 <section class="ops-panel"><h3>دسترسی سریع مالی</h3><p class="ops-muted">فیش‌های پرداخت، بدهی سررسیدگذشته و گردش مالی را از بخش‌های مربوط پیگیری کن.</p><div class="ops-actions"><a href="{{ route('payments.index') }}">بررسی فیش‌ها</a><a href="{{ route('treasury.index') }}">درآمد و هزینه‌ها</a><a href="{{ route('charges.index') }}">صورت‌حساب‌ها</a></div></section>
</div>
@endsection