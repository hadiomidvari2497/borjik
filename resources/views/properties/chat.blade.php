@extends('layouts.marketplace')
@section('title', 'گفتگو با '.$inquiry->counterpartName((int) auth()->id()))
@section('bare', '1')
@section('body_class', 'is-chat')
@section('viewport', 'width=device-width,initial-scale=1,viewport-fit=cover,interactive-widget=resizes-content')
@push('head')<link rel="stylesheet" href="{{ asset('css/chat.css') }}?v={{ @filemtime(public_path('css/chat.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/chat.js') }}?v={{ @filemtime(public_path('js/chat.js')) }}" defer></script>@endpush
@section('content')
@php
    use App\Support\Fa;

    $me = (int) auth()->id();
    $isRecipient = $me === (int) $inquiry->recipient_user_id;
    $listing = $inquiry->listing;
    $partner = $inquiry->counterpartName($me);
    $closed = $inquiry->status === 'closed';
    $photo = $listing?->photoUrls()[0] ?? null;
    $callable = $isRecipient && $inquiry->sender_mobile ? $inquiry->sender_mobile : null;

    // خط زمانی: پیام اولیه‌ی گفتگو + پیام‌های بعدی (با پیوست‌ها)
    $timeline = collect([[
        'id' => 0, 'sender' => (int) $inquiry->sender_user_id, 'body' => (string) $inquiry->message, 'at' => $inquiry->created_at, 'attachments' => [], 'reply' => null,
        'read' => (bool) $inquiry->read_at,
    ]])->concat($messages->map(fn ($m) => [
        'id' => $m->id, 'sender' => (int) $m->sender_user_id, 'body' => (string) $m->body, 'at' => $m->created_at,
        'attachments' => $m->attachments->map->toPayload()->all(),
        'reply' => $m->replyTo ? ['id' => $m->replyTo->id, 'body' => $m->replyTo->body, 'sender_name' => $m->replyTo->sender?->display_name ?? ''] : null,
        'read' => (bool) $m->read_at,
    ]))->map(fn ($row) => $row + ['day' => Fa::date($row['at'], 'Y/m/d')])->values();
@endphp
@include('properties.partials.chat-icons')

<section class="chat" id="chat"
    data-me="{{ $me }}"
    data-poll-url="{{ route('properties.chat.messages', $inquiry) }}"
    data-today="{{ Fa::date(now(), 'Y/m/d') }}"
    data-yesterday="{{ Fa::date(now()->subDay(), 'Y/m/d') }}">

    <header class="chat-top">
        <a class="chat-back" href="{{ route('properties.inquiries.index') }}" aria-label="بازگشت به پیام‌ها"><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></a>
        <div class="chat-who">
            <span class="avatar" aria-hidden="true">{{ \Illuminate\Support\Str::substr($partner ?: '؟', 0, 1) }}</span>
            <div class="chat-who-t">
                <b>{{ $partner }}@if($closed) <span class="tag plain sm">بسته‌شده</span>@endif</b>
                @if($listing)
                    <a class="chat-ad-link" href="{{ route('properties.show', $listing) }}"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg><span>{{ $listing->title }}</span></a>
                @else
                    <small>این آگهی دیگر در دسترس نیست</small>
                @endif
            </div>
        </div>
        <div class="chat-actions">
            @if($callable)<a class="chat-call" href="tel:{{ $callable }}" aria-label="تماس با {{ $partner }}"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg><span>تماس</span></a>@endif
            @if($listing)
                <a class="chat-ad-thumb" href="{{ route('properties.show', $listing) }}" aria-label="مشاهده آگهی" title="مشاهده آگهی">@if($photo)<img src="{{ $photo }}" alt="" decoding="async">@else<svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>@endif</a>
            @endif
        </div>
    </header>

    <div class="chat-body">
        <div id="chat-thread" class="chat-thread" role="log" aria-live="polite" aria-relevant="additions" aria-label="پیام‌های گفتگو">
            @foreach($timeline as $i => $row)
                @php
                    $prev = $timeline[$i - 1] ?? null;
                    $next = $timeline[$i + 1] ?? null;
                    $newDay = ! $prev || $prev['day'] !== $row['day'];
                    $row['starts'] = $newDay || $prev['sender'] !== $row['sender'];
                    $row['ends'] = ! $next || $next['day'] !== $row['day'] || $next['sender'] !== $row['sender'];
                @endphp
                @if($newDay)<div class="day">{{ Fa::dayLabel($row['at']) }}</div>@endif
                @include('properties.partials.chat-message', ['row' => $row, 'me' => $me, 'callable' => $callable])
            @endforeach
        </div>
        <button type="button" class="jump" id="chat-jump" aria-label="رفتن به آخرین پیام"><svg class="icon" aria-hidden="true"><use href="#i-down"/></svg><span class="jump-n" aria-hidden="true"></span></button>
        <div class="drop-hint" aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="#i-clip"/></svg>فایل را اینجا رها کنید</div>
        @if(session('status'))<div class="toast" id="chat-toast" role="status">{{ session('status') }}</div>@endif
    </div>

    <form id="chat-composer" class="composer" method="POST" action="{{ route('properties.chat.send', $inquiry) }}" enctype="multipart/form-data" @if($closed) hidden @endif>
        @csrf
        <div class="reply-bar" id="chat-reply-bar" hidden><svg class="icon" aria-hidden="true"><use href="#i-reply"/></svg><span id="chat-reply-label"></span><button type="button" id="chat-reply-cancel" aria-label="لغو پاسخ"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button></div>
        <input type="hidden" name="reply_to_message_id" value="">
        <div class="tray" id="chat-tray" hidden></div>
        <div class="composer-row">
            <input id="chat-file" class="sr-only" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" tabindex="-1">
            <button type="button" class="attach" id="chat-attach" aria-label="افزودن فایل یا تصویر" title="افزودن فایل یا تصویر"><svg class="icon" aria-hidden="true"><use href="#i-clip"/></svg></button>
            <label class="sr-only" for="chat-body">پیام جدید</label>
            <textarea id="chat-body" name="body" rows="1" maxlength="2000" placeholder="پیام خود را بنویسید…">{{ old('body') }}</textarea>
            <button class="send" type="submit" aria-label="ارسال پیام" disabled><svg class="icon" aria-hidden="true"><use href="#i-send"/></svg></button>
        </div>
        <div class="composer-foot">
            <span id="chat-error" class="err" role="alert">@error('body'){{ $message }}@enderror</span>
            <span class="composer-hint">Enter برای ارسال · Shift+Enter برای خط جدید</span>
            <span id="chat-count" class="chars"></span>
        </div>
    </form>
    <div id="chat-closed" class="chat-closed" @unless($closed) hidden @endunless>
        <svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>این گفتگو بسته شده و امکان ارسال پیام جدید وجود ندارد.
    </div>

    <div class="lightbox" id="chat-lightbox" role="dialog" aria-modal="true" aria-label="نمایش تصویر" hidden>
        <div class="lb-bar">
            <button type="button" class="lb-btn" data-lb="close" aria-label="بستن"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button>
            <span class="lb-name" id="chat-lb-name" dir="auto"></span>
            <a class="lb-btn" id="chat-lb-dl" href="#" download aria-label="دانلود تصویر"><svg class="icon" aria-hidden="true"><use href="#i-download"/></svg></a>
        </div>
        <button type="button" class="lb-nav prev" data-lb="prev" aria-label="تصویر قبلی"><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></button>
        <img id="chat-lb-img" alt="">
        <button type="button" class="lb-nav next" data-lb="next" aria-label="تصویر بعدی"><svg class="icon" aria-hidden="true"><use href="#i-chev-l"/></svg></button>
    </div>
</section>
@endsection
