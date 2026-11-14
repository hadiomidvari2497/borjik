@extends('layouts.marketplace')
@section('title', 'پیام‌ها و گفتگوهای آگهی')
@push('head')<link rel="stylesheet" href="{{ asset('css/chat.css') }}?v={{ @filemtime(public_path('css/chat.css')) }}">@endpush
@section('content')
@php
    use App\Support\Fa;

    $me = (int) auth()->id();
    $statusLabels = $statusLabels ?? ['new' => 'جدید', 'contacted' => 'در حال پیگیری', 'agreed' => 'توافق‌شده', 'closed' => 'بسته‌شده'];
@endphp
@include('properties.partials.chat-icons')

<header class="inbox-head">
    <div>
        <h1>مدیریت درخواست‌ها و گفتگوها</h1>
        <p>@if($inquiries->total() > 0){{ Fa::num($inquiries->total()) }} نتیجه از گفتگوهای خریداران و فروشندگان@else درخواست‌های مشتریان و گفتگوهای شما اینجا نمایش داده می‌شود@endif</p>
    </div>
    <a class="btn light" href="{{ route('properties.mine') }}">آگهی‌های من</a>
</header>

<form class="panel lead-filters" method="GET" action="{{ route('properties.inquiries.index') }}" aria-label="فیلتر درخواست‌ها">
    <label class="field">جست‌وجو
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="نام مشتری یا عنوان آگهی">
    </label>
    <label class="field">آگهی
        <select name="listing_id">
            <option value="">همه آگهی‌ها</option>
            @foreach($ownedListings as $ownedListing)
                <option value="{{ $ownedListing->id }}" @selected((string) ($filters['listing_id'] ?? '') === (string) $ownedListing->id)>{{ $ownedListing->title }}</option>
            @endforeach
        </select>
    </label>
    <label class="field">وضعیت پیگیری
        <select name="status">
            <option value="">همه وضعیت‌ها</option>
            @foreach($statusLabels as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}" @selected(($filters['status'] ?? '') === $statusKey)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </label>
    <div class="lead-filter-actions">
        <button class="btn" type="submit">اعمال فیلتر</button>
        <a class="btn light" href="{{ route('properties.inquiries.index') }}">پاک‌کردن</a>
    </div>
</form>
<style>
.lead-filters{display:grid;grid-template-columns:2fr 1.4fr 1fr auto;align-items:end;gap:12px;margin:0 0 18px}
.lead-filters .field{display:grid;gap:6px;font-size:12px;color:#60746a}
.lead-filters input,.lead-filters select{width:100%;min-height:42px;border:1px solid #dfe8e2;border-radius:10px;padding:8px 10px;background:#fff;color:inherit;font:inherit}
.lead-filter-actions{display:flex;gap:7px;flex-wrap:wrap}
.lead-status{position:relative;z-index:1;isolation:isolate;display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap}
.lead-status form{display:flex;gap:6px;align-items:center}
.lead-status select{max-width:145px;min-height:32px;border:1px solid #dfe8e2;border-radius:8px;padding:4px 7px;background:#fff;font:inherit;font-size:11px}
.lead-status button{border:0;border-radius:8px;padding:7px 10px;background:#eaf5ee;color:#246b4d;font:inherit;font-size:11px;cursor:pointer}
.lead-status .current{font-size:11px;color:#60746a}
@media(max-width:760px){.lead-filters{grid-template-columns:1fr 1fr}.lead-filter-actions{grid-column:1/-1}}
@media(max-width:420px){.lead-filters{grid-template-columns:1fr}}
</style>

<section class="inbox" aria-label="فهرست گفتگوها">
    @forelse($inquiries as $inquiry)
        @php
            $isRecipient = $me === (int) $inquiry->recipient_user_id;
            $partner = $inquiry->counterpartName($me);
            $listing = $inquiry->listing;
            $last = $inquiry->latestMessage;
            $previewBody = $last?->body ?? $inquiry->message;
            if ($last && trim((string) $last->body) === '' && $last->attachments->isNotEmpty()) {
                $previewBody = ($last->attachments->every->isImage() ? '🖼 تصویر' : '📎 فایل پیوست').($last->attachments->count() > 1 ? ' ('.Fa::num($last->attachments->count()).')' : '');
            }
            $previewMine = $last ? (int) $last->sender_user_id === $me : (int) $inquiry->sender_user_id === $me;
            $when = $last?->created_at ?? $inquiry->created_at;
            $unread = (int) $inquiry->unread_messages_count + ($isRecipient && ! $inquiry->read_at ? 1 : 0);
            $photo = $listing?->photoUrls()[0] ?? null;
            $callable = $isRecipient && $inquiry->sender_mobile ? $inquiry->sender_mobile : null;
        @endphp
        <article class="convo @if($unread > 0) is-unread @endif">
            <div class="convo-thumb" aria-hidden="true">
                <span class="ph">@if($photo)<img src="{{ $photo }}" alt="" loading="lazy" decoding="async">@else<svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>@endif</span>
                <span class="av">{{ \Illuminate\Support\Str::substr($partner ?: '؟', 0, 1) }}</span>
            </div>
            <div class="convo-main">
                <div class="convo-top">
                    <a class="convo-name convo-link" href="{{ route('properties.chat.show', $inquiry) }}">{{ $partner }}</a>
                    @if($isRecipient)<span class="tag req sm">آگهی شما</span>@endif
                    @if($inquiry->status === 'closed')<span class="tag plain sm">{{ $statusLabels['closed'] }}</span>@endif
                </div>
                <div class="convo-ad"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg><span>{{ $listing?->title ?? 'آگهی حذف‌شده' }}</span></div>
                <div class="convo-preview">@if($previewMine)<em>شما:</em> @endif{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', (string) $previewBody), 110) }}</div>
                <div class="lead-status">
                    <span class="current">وضعیت: {{ $statusLabels[$inquiry->status] ?? $inquiry->status }}</span>
                    @if($isRecipient)
                        <form method="POST" action="{{ route('properties.inquiries.update', $inquiry) }}">
                            @csrf
                            @method('PATCH')
                            <label class="sr-only" for="lead-status-{{ $inquiry->id }}">تغییر وضعیت درخواست {{ $partner }}</label>
                            <select id="lead-status-{{ $inquiry->id }}" name="status">
                                @foreach($statusLabels as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" @selected($inquiry->status === $statusKey)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                            <button type="submit">ذخیره</button>
                        </form>
                    @endif
                </div>
                @if($isRecipient)
                    <div class="lead-reminder">
                        @if($inquiry->follow_up_at)
                            <span class="reminder-state @if($inquiry->follow_up_at->isPast()) overdue @else upcoming @endif">
                                @if($inquiry->follow_up_at->isPast()) پیگیری عقب‌افتاده: @else یادآوری: @endif{{ Fa::stamp($inquiry->follow_up_at) }}
                            </span>
                        @endif
                        <details>
                            <summary>{{ $inquiry->follow_up_at ? 'ویرایش یادآوری' : 'تنظیم یادآوری پیگیری' }}</summary>
                            <form method="POST" action="{{ route('properties.inquiries.update', $inquiry) }}">
                                @csrf
                                @method('PATCH')
                                <label>زمان پیگیری
                                    <input type="datetime-local" name="follow_up_at" value="{{ $inquiry->follow_up_at?->format('Y-m-d\\TH:i') }}" step="60">
                                </label>
                                <label>یادداشت
                                    <input type="text" name="follow_up_note" value="{{ $inquiry->follow_up_note }}" maxlength="1000" placeholder="مثلاً تماس برای هماهنگی بازدید">
                                </label>
                                <button type="submit">ذخیره یادآوری</button>
                            </form>
                        </details>
                    </div>
                @endif
            </div>
            <div class="convo-side">
                <time class="convo-time" datetime="{{ $when->toIso8601String() }}">{{ Fa::stamp($when) }}</time>
                @if($unread > 0)<span class="convo-badge" aria-label="{{ Fa::num($unread) }} پیام خوانده‌نشده">{{ Fa::num($unread) }}</span>@endif
                @if($callable)<a class="convo-call" href="tel:{{ $callable }}" aria-label="تماس با {{ $partner }}"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg></a>@endif
            </div>
        </article>
    @empty
        <div class="inbox-empty">
            <div class="ico"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg></div>
            <h2>هنوز گفتگویی ندارید</h2>
            <p>از صفحه‌ی هر آگهی می‌توانید با «ارتباط با فروشنده» گفتگو را شروع کنید. پیام خریداران آگهی‌های شما هم همین‌جا می‌آید.</p>
            <a class="btn lg" href="{{ route('properties.index') }}"><svg class="icon" aria-hidden="true"><use href="#i-search"/></svg>مشاهده آگهی‌ها</a>
            <a class="btn light lg" href="{{ route('properties.create') }}"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>ثبت آگهی</a>
        </div>
    @endforelse
</section>
<div class="pagination">{{ $inquiries->links() }}</div>
@endsection
