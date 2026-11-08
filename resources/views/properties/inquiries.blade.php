@extends('layouts.marketplace')
@section('title', 'پیام‌ها و گفتگوهای آگهی')
@push('head')<link rel="stylesheet" href="{{ asset('css/chat.css') }}?v={{ @filemtime(public_path('css/chat.css')) }}">@endpush
@section('content')
@php
    use App\Support\Fa;

    $me = (int) auth()->id();
    $statusLabels = ['new' => 'جدید', 'contacted' => 'در حال گفتگو', 'closed' => 'بسته‌شده'];
@endphp
@include('properties.partials.chat-icons')

<header class="inbox-head">
    <div>
        <h1>پیام‌ها</h1>
        <p>@if($inquiries->total() > 0){{ Fa::num($inquiries->total()) }} گفتگوی خصوصی با خریداران و فروشندگان@else گفتگوهای شما با خریداران و فروشندگان اینجا نمایش داده می‌شود@endif</p>
    </div>
    <a class="btn light" href="{{ route('properties.mine') }}">آگهی‌های من</a>
</header>

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
        </div>
    @endforelse
</section>
<div class="pagination">{{ $inquiries->links() }}</div>
@endsection
