@php
    /** @var array $row  id, sender, body, at, day, starts, ends, attachments */
    $images = collect($row['attachments'])->where('is_image', true)->values();
    $files = collect($row['attachments'])->where('is_image', false)->values();
@endphp
<article class="msg {{ $row['sender'] === $me ? 'mine' : 'theirs' }}@if($row['starts']) first @endif @if($row['ends']) last @endif @if($images->isNotEmpty()) has-media @endif"
    data-message-id="{{ $row['id'] }}" data-sender="{{ $row['sender'] }}" data-day="{{ $row['day'] }}">
    @if(! empty($row['reply']))
        <button type="button" class="reply-quote" data-jump-to="{{ $row['reply']['id'] }}" aria-label="رفتن به پیام اصلی"><b>{{ $row['reply']['sender_name'] ?: 'کاربر' }}</b><span>{{ \Illuminate\Support\Str::limit($row['reply']['body'] ?: 'پیوست', 100) }}</span></button>
    @endif
    @if($images->isNotEmpty())
        <div class="msg-media n{{ min($images->count(), 5) }}">
            @foreach($images as $img)
                <a class="media" href="{{ $img['url'] }}" data-lightbox data-name="{{ $img['name'] }}"><img src="{{ $img['url'] }}" alt="{{ $img['name'] }}" loading="lazy" decoding="async"></a>
            @endforeach
        </div>
    @endif
    @foreach($files as $file)
        <a class="file" href="{{ $file['url'] }}" download="{{ $file['name'] }}">
            <span class="file-ico"><svg class="icon" aria-hidden="true"><use href="#i-file"/></svg></span>
            <span class="file-t"><b dir="auto">{{ $file['name'] }}</b><small>PDF · {{ $file['size'] }}</small></span>
            <svg class="icon file-dl" aria-hidden="true"><use href="#i-download"/></svg>
        </a>
    @endforeach
    @if($row['body'] !== '')<div class="msg-body">{{ $row['body'] }}</div>@endif
    @if($row['id'] === 0 && ! empty($callable))
        <a class="msg-call" href="tel:{{ $callable }}"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg>{{ $callable }}</a>
    @endif
    <div class="msg-foot">
        @if($row['id'] > 0)<button type="button" class="msg-reply" data-reply-id="{{ $row['id'] }}" data-reply-sender="{{ $row['sender'] === $me ? 'شما' : $partner }}" data-reply-body="{{ $row['body'] !== '' ? $row['body'] : 'پیوست' }}" aria-label="پاسخ به این پیام"><svg class="icon" aria-hidden="true"><use href="#i-reply"/></svg></button>@endif
        <time class="msg-time" datetime="{{ $row['at']->toIso8601String() }}">{{ \App\Support\Fa::digits($row['at']->format('H:i')) }}</time>
        @if($row['sender'] === $me)<span class="tick {{ ! empty($row['read']) ? 'is-read' : '' }}" @if($row['id'] === 0) id="initial-read-receipt" @else data-receipt-id="{{ $row['id'] }}" @endif title="{{ ! empty($row['read']) ? 'خوانده شده' : 'ارسال شده' }}">{{ ! empty($row['read']) ? '✓✓' : '✓' }}</span>@endif
    </div>
</article>
