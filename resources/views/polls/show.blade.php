@php
    $F = \App\Support\Fa::class;
    $ended = $poll->status !== 'open' || now()->gte($poll->ends_at);
@endphp
@extends('layouts.app')
@section('title', 'جزئیات نظرسنجی')
@section('content')
<style>
.poll-detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr));gap:12px}
.poll-detail-stat{padding:14px;border:1px solid var(--line,#e5e7eb);border-radius:12px}
.poll-detail-stat strong{display:block;font-size:20px;margin-top:4px}
.poll-detail-table{width:100%;border-collapse:collapse;margin-top:12px}
.poll-detail-table th,.poll-detail-table td{padding:11px 9px;text-align:right;border-bottom:1px solid var(--line,#e5e7eb);vertical-align:top}
.poll-detail-table th{font-size:12px;color:var(--muted,#667085)}
.poll-detail-answer{white-space:pre-line;overflow-wrap:anywhere}
@media(max-width:640px){.poll-detail-table{font-size:13px}.poll-detail-table th,.poll-detail-table td{padding:8px 5px}}
</style>
<div class="card">
    <div class="page-h"><h2>{{ $poll->title }}</h2><a class="btn sm" href="{{ route('polls.index') }}">بازگشت به نظرسنجی‌ها</a></div>
    @if($poll->description)<p class="poll-description" style="white-space:pre-line">{{ $poll->description }}</p>@endif
    <div class="poll-detail-grid" style="margin-top:16px">
        <div class="poll-detail-stat"><span>نوع نظرسنجی</span><strong>{{ $poll->response_type === 'text' ? 'تشریحی' : 'گزینه‌ای' }}</strong></div>
        <div class="poll-detail-stat"><span>مجموع {{ $poll->response_type === 'text' ? 'پاسخ‌ها' : 'آرا' }}</span><strong>{{ $F::digits($poll->votes_count) }}</strong></div>
        <div class="poll-detail-stat"><span>مخاطبان</span><strong>{{ $poll->audience === 'residents' ? 'مالک و مستأجر' : 'تمام اعضای مجاز' }}</strong></div>
        <div class="poll-detail-stat"><span>وضعیت</span><strong>{{ $ended ? 'پایان‌یافته' : (now()->lt($poll->starts_at) ? 'شروع‌نشده' : 'در حال رأی‌گیری') }}</strong></div>
        <div class="poll-detail-stat"><span>شروع</span><strong>{{ \\App\\Support\\Fa::date($poll->starts_at, 'Y/m/d H:i') }}</strong></div>
        <div class="poll-detail-stat"><span>پایان</span><strong>{{ \\App\\Support\\Fa::date($poll->ends_at, 'Y/m/d H:i') }}</strong></div>
        <div class="poll-detail-stat"><span>حریم خصوصی</span><strong>{{ $poll->is_anonymous ? 'مخفی' : 'غیرمخفی' }}</strong></div>
        <div class="poll-detail-stat"><span>ایجادکننده</span><strong>{{ $poll->createdBy?->display_name ?: '—' }}</strong></div>
    </div>
</div>
<div class="card">
    <div class="page-h"><h2>{{ $poll->is_anonymous ? 'خلاصه نتایج' : 'نتایج و پاسخ‌دهندگان' }}</h2></div>
    @if($poll->response_type === 'choice')
        @foreach($poll->options as $option)
            @php $percent = $poll->votes_count > 0 ? (int) round($option->votes_count * 100 / $poll->votes_count) : 0; @endphp
            <div style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;gap:8px"><strong>{{ $option->text }}</strong><span>{{ $F::digits($option->votes_count) }} رأی ({{ $F::digits($percent) }}٪)</span></div>
                <div style="height:8px;background:var(--soft,#edf2f7);border-radius:99px;overflow:hidden;margin-top:6px"><div style="height:100%;width:{{ $percent }}%;background:var(--primary,#1f6a63)"></div></div>
            </div>
        @endforeach
    @endif
    @if($poll->is_anonymous)
        <p class="poll-description" style="margin-top:16px">این نظرسنجی مخفی است؛ هویت و پاسخ منتسب به هر فرد نمایش داده نمی‌شود.</p>
    @elseif($poll->votes->isEmpty())
        <div class="empty" style="margin-top:12px">هنوز کسی پاسخ نداده است.</div>
    @else
        <div style="overflow-x:auto"><table class="poll-detail-table">
            <thead><tr><th>شرکت‌کننده</th><th>{{ $poll->response_type === 'text' ? 'پاسخ تشریحی' : 'گزینه انتخاب‌شده' }}</th><th>زمان ثبت</th></tr></thead>
            <tbody>
            @foreach($poll->votes->sortBy('created_at') as $vote)
                <tr><td>{{ $vote->user?->display_name ?: 'کاربر حذف‌شده' }}<div class="poll-description">{{ $vote->user?->mobile }}</div></td><td class="poll-detail-answer">{{ $poll->response_type === 'text' ? $vote->answer : ($vote->option?->text ?: '—') }}</td><td>{{ $vote->created_at ? \\App\\Support\\Fa::date($vote->created_at, 'Y/m/d H:i') : '—' }}</td></tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
    @if(!$ended)
        <div style="margin-top:18px"><form method="POST" action="{{ route('polls.close', $poll->id) }}" data-confirm="این رأی‌گیری بسته شود؟" data-confirm-title="بستن رأی‌گیری" data-confirm-icon="warning">@csrf<button class="btn dng" type="submit">بستن رأی‌گیری</button></form></div>
    @endif
</div>
@endsection
