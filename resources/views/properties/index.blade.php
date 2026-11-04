@extends('layouts.marketplace')
@section('title', 'خرید، فروش و اجاره ملک')
@section('content')
@php
    use App\Models\PropertyListing;
    use App\Support\Fa;

    $activeType = request('listing_type');
    $tabQuery = fn (?string $type) => route('properties.index', array_filter(array_merge(request()->except(['page', 'listing_type']), ['listing_type' => $type]), fn ($v) => $v !== null && $v !== ''));
    $hasAdvanced = request()->filled('property_type') || request()->filled('min_area') || request()->filled('max_area');
@endphp

<section class="hero has-search">
    <h1>ملک موردنظرت را پیدا کن</h1>
    <p>آگهی‌های خرید، فروش و اجاره از صاحبان ملک و مشاوران، در یک جا و بدون پیچیدگی.</p>
</section>

<form class="search" method="GET" action="{{ route('properties.index') }}" role="search" aria-label="جستجوی آگهی">
    @if($activeType)<input type="hidden" name="listing_type" value="{{ $activeType }}">@endif
    <div class="seg" role="navigation" aria-label="نوع آگهی">
        <a href="{{ $tabQuery(null) }}" @if(! $activeType) aria-current="true" @endif>همه آگهی‌ها</a>
        @foreach(PropertyListing::LISTING_TYPES as $key => $label)
            <a href="{{ $tabQuery($key) }}" @if($activeType === $key) aria-current="true" @endif>{{ $label }}</a>
        @endforeach
    </div>
    <div class="search-row">
        <label class="field">شهر<input name="city" value="{{ request('city') }}" placeholder="مثلاً تهران" autocomplete="off"></label>
        <label class="field">محله<input name="neighborhood" value="{{ request('neighborhood') }}" placeholder="مثلاً ونک" autocomplete="off"></label>
        <label class="field">نوع ملک
            <select name="property_type">
                <option value="">همه</option>
                @foreach(PropertyListing::PROPERTY_TYPES as $k => $v)<option value="{{ $k }}" @selected(request('property_type') === $k)>{{ $v }}</option>@endforeach
            </select>
        </label>
        <button class="btn lg" type="submit"><svg class="icon" aria-hidden="true"><use href="#i-search"/></svg>جستجو</button>
    </div>
    <details @if($hasAdvanced) open @endif>
        <summary>فیلتر متراژ</summary>
        <div class="fields">
            <label class="field">حداقل متراژ<input type="number" min="0" name="min_area" value="{{ request('min_area') }}" placeholder="مثلاً ۶۰"></label>
            <label class="field">حداکثر متراژ<input type="number" min="0" name="max_area" value="{{ request('max_area') }}" placeholder="مثلاً ۱۵۰"></label>
        </div>
        <div class="actions"><a class="btn light" href="{{ route('properties.index') }}">پاک‌کردن فیلترها</a></div>
    </details>
</form>

<div class="results-head">
    <h2>{{ $activeType ? 'آگهی‌های '.(PropertyListing::LISTING_TYPES[$activeType] ?? '') : 'تازه‌ترین آگهی‌ها' }}</h2>
    @if($listings->total() > 0)<span>{{ Fa::num($listings->total()) }} آگهی</span>@endif
</div>

<div class="grid">
@forelse($listings as $listing)
    @php
        $photos = $listing->photoUrls();
        $badge = match($listing->listing_type) { 'rent' => 'rent', 'buy_request', 'rent_request' => 'req', default => '' };
    @endphp
    <a class="card" href="{{ route('properties.show', $listing) }}">
        <div class="photo @if($photos) has-img @endif">
            <span class="badge {{ $badge }}">{{ $listing->listingTypeLabel() }}</span>
            @if($photos)
                <img src="{{ $photos[0] }}" alt="{{ $listing->title }}" loading="lazy" decoding="async">
                @if(count($photos) > 1)<span class="count"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>{{ Fa::num(count($photos)) }}</span>@endif
            @else
                <svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>
            @endif
        </div>
        <div class="body">
            <h3>{{ $listing->title }}</h3>
            <div class="loc"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg>{{ $listing->city }}@if($listing->neighborhood)، {{ $listing->neighborhood }}@endif</div>
            @if($listing->area || $listing->bedrooms !== null || $listing->floor !== null)
            <div class="specs">
                @if($listing->area)<span><svg class="icon" aria-hidden="true"><use href="#i-area"/></svg>{{ Fa::num((float) $listing->area) }} متر</span>@endif
                @if($listing->bedrooms !== null)<span><svg class="icon" aria-hidden="true"><use href="#i-bed"/></svg>{{ Fa::num($listing->bedrooms) }} خواب</span>@endif
                @if($listing->floor !== null)<span><svg class="icon" aria-hidden="true"><use href="#i-floor"/></svg>طبقه {{ Fa::num($listing->floor) }}</span>@endif
            </div>
            @endif
            <div class="price">
                @foreach($listing->priceRows(true) as $row)
                    <div>@if($row['label'])<small>{{ $row['label'] }}</small>@endif<span>{{ $row['value'] }}</span></div>
                @endforeach
            </div>
        </div>
    </a>
@empty
    <div class="empty" style="grid-column:1/-1">
        <h3>آگهی‌ای با این مشخصات پیدا نشد</h3>
        <p class="muted">فیلترها را کمی کم‌تر کنید یا خودتان اولین آگهی این محدوده را ثبت کنید.</p>
        <div class="actions" style="justify-content:center"><a class="btn light" href="{{ route('properties.index') }}">پاک‌کردن فیلترها</a><a class="btn" href="{{ route('properties.create') }}">ثبت آگهی</a></div>
    </div>
@endforelse
</div>
<div class="pagination">{{ $listings->links() }}</div>
@endsection
