@php
    $cardPhotos = $listing->photoUrls();
    $cardBadge = match($listing->listing_type) { 'rent' => 'rent', 'buy_request', 'rent_request' => 'req', default => '' };
    $cardFa = \App\Support\Fa::class;
@endphp
<a class="card" href="{{ route('properties.show', $listing) }}">
    <div class="photo @if($cardPhotos) has-img @endif">
        <span class="badge {{ $cardBadge }}">{{ $listing->listingTypeLabel() }}</span>
        @if($cardPhotos)
            <img src="{{ $cardPhotos[0] }}" alt="{{ $listing->title }}" loading="lazy" decoding="async">
            @if(count($cardPhotos) > 1)<span class="count"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>{{ $cardFa::num(count($cardPhotos)) }}</span>@endif
        @else
            <svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>
        @endif
    </div>
    <div class="body">
        <h3>{{ $listing->title }}</h3>
        <div class="loc"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg>{{ $listing->city }}@if($listing->neighborhood)، {{ $listing->neighborhood }}@endif</div>
        @if($listing->area || $listing->bedrooms !== null || $listing->floor !== null)
        <div class="specs">
            @if($listing->area)<span><svg class="icon" aria-hidden="true"><use href="#i-area"/></svg>{{ $cardFa::num((float) $listing->area) }} متر</span>@endif
            @if($listing->bedrooms !== null)<span><svg class="icon" aria-hidden="true"><use href="#i-bed"/></svg>{{ $cardFa::num($listing->bedrooms) }} خواب</span>@endif
            @if($listing->floor !== null)<span><svg class="icon" aria-hidden="true"><use href="#i-floor"/></svg>طبقه {{ $cardFa::num($listing->floor) }}</span>@endif
        </div>
        @endif
        <div class="price">
            @foreach($listing->priceRows(true) as $row)
                <div>@if($row['label'])<small>{{ $row['label'] }}</small>@endif<span>{{ $row['value'] }}</span></div>
            @endforeach
        </div>
    </div>
</a>
