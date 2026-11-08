@php
    /** @var array $row  id, sender, body, at, day, starts, ends, attachments */
    $images = collect($row['attachments'])->where('is_image', true)->values();
    $files = collect($row['attachments'])->where('is_image', false)->values();
@endphp
<article class="msg {{ $row['sender'] === $me ? 'mine' : 'theirs' }}@if($row['starts']) first @endif @if($row['ends']) last @endif @if($images->isNotEmpty()) has-media @endif"
    data-message-id="{{ $row['id'] }}" data-sender="{{ $row['sender'] }}" data-day="{{ $row['day'] }}">
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
    <time class="msg-time" datetime="{{ $row['at']->toIso8601String() }}">{{ \App\Support\Fa::digits($row['at']->format('H:i')) }}</time>
</article>
