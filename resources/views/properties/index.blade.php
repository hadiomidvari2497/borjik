@extends('layouts.marketplace')
@section('title', 'خرید، فروش و اجاره ملک')
@section('content')
@php($mapApiKey = $mapApiKey ?? config('services.neshan.map_key'))
@if($mapApiKey)
    @push('head')<link rel="stylesheet" href="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.css">@endpush
@endif


<section class="hero has-search">
    <h1>ملک موردنظرت را پیدا کن</h1>
    <p>آگهی‌های خرید، فروش و اجاره از صاحبان ملک و مشاوران، در یک جا و بدون پیچیدگی.</p>
</section>

<details class="search" id="search">
    <summary>
        <svg class="icon" aria-hidden="true"><use href="#i-search"/></svg>
        <span class="sum-text">
            <b>جستجو و فیلتر آگهی‌ها</b>
            <small>{{ $activeFilters ? implode(' · ', $activeFilters) : 'نوع آگهی، شهر، محله، قیمت و متراژ' }}</small>
        </span>
        @if($activeFilters)<span class="sum-count">{{ \App\Support\Fa::num(count($activeFilters)) }}</span>@endif
        <span class="sum-chev" aria-hidden="true"></span>
    </summary>
    <form class="search-body" method="GET" action="{{ route('properties.index') }}" role="search" aria-label="جستجوی آگهی">
        @if(request('listing_type'))<input type="hidden" name="listing_type" value="{{ request('listing_type') }}">@endif
        <div class="seg" role="navigation" aria-label="نوع آگهی">
            <a href="{{ route('properties.index', request()->except(['page', 'listing_type'])) }}" @if(! request('listing_type')) aria-current="true" @endif>همه آگهی‌ها</a>
            @foreach(\App\Models\PropertyListing::LISTING_TYPES as $key => $label)
                <a href="{{ route('properties.index', array_filter(array_merge(request()->except(['page', 'listing_type']), ['listing_type' => $key]), fn ($v) => $v !== null && $v !== '')) }}" @if(request('listing_type') === $key) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
        </div>
        <div class="fields">
            <label class="field span-all">عبارت جستجو<input name="q" value="{{ request('q') }}" placeholder="عنوان، محله یا توضیحات" autocomplete="off"></label>
            <label class="field">استان<input name="province" value="{{ request('province') }}" placeholder="مثلاً تهران" autocomplete="off"></label>
            <label class="field">شهر<input name="city" value="{{ request('city') }}" placeholder="مثلاً تهران" autocomplete="off"></label>
            <label class="field">محله<input name="neighborhood" value="{{ request('neighborhood') }}" placeholder="مثلاً ونک" autocomplete="off"></label>
            <label class="field">نوع ملک
                <select name="property_type">
                    <option value="">همه</option>
                    @foreach(\App\Models\PropertyListing::PROPERTY_TYPES as $k => $v)<option value="{{ $k }}" @selected(request('property_type') === $k)>{{ $v }}</option>@endforeach
                </select>
            </label>
            <label class="field">حداقل متراژ<input type="number" inputmode="numeric" min="1" name="min_area" value="{{ request('min_area') }}" placeholder="مثلاً ۶۰"></label>
            <label class="field">حداکثر متراژ<input type="number" inputmode="numeric" min="1" name="max_area" value="{{ request('max_area') }}" placeholder="مثلاً ۱۵۰"></label>
            <label class="field">حداقل قیمت / اجاره<input type="number" inputmode="numeric" min="0" name="min_price" value="{{ request('min_price') }}" placeholder="تومان"></label>
            <label class="field">حداکثر قیمت / اجاره<input type="number" inputmode="numeric" min="0" name="max_price" value="{{ request('max_price') }}" placeholder="تومان"></label>
            <label class="field">حداقل تعداد اتاق<input type="number" inputmode="numeric" min="0" max="30" name="bedrooms" value="{{ request('bedrooms') }}"></label>
            <label class="field">پارکینگ<select name="parking"><option value="">مهم نیست</option><option value="1" @selected(request('parking') === '1')>فقط دارای پارکینگ</option></select></label>
        </div>
        <div class="actions">
            <button class="btn lg" type="submit"><svg class="icon" aria-hidden="true"><use href="#i-search"/></svg>جستجو</button>
            @if($activeFilters)<a class="btn light lg" href="{{ route('properties.index') }}">پاک‌کردن فیلترها</a>@endif
        </div>
    </form>
</details>

<div class="results-head">
    <h2>{{ request('listing_type') ? 'آگهی‌های '.(\App\Models\PropertyListing::LISTING_TYPES[request('listing_type')] ?? '') : 'تازه‌ترین آگهی‌ها' }}</h2>
    <div class="results-tools">
        @if($listings->total() > 0)<span>{{ \App\Support\Fa::num($listings->total()) }} آگهی</span>@endif
        <form class="sort" method="GET" action="{{ route('properties.index') }}">
            @foreach(request()->except(['sort','page']) as $key => $value)@if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
            <label class="sr-only" for="market-sort">مرتب‌سازی</label>
            <select id="market-sort" name="sort" onchange="this.form.submit()">
                <option value="newest" @selected(request('sort','newest') === 'newest')>جدیدترین</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>ارزان‌ترین</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>گران‌ترین</option>
                <option value="area_desc" @selected(request('sort') === 'area_desc')>بیشترین متراژ</option>
            </select>
        </form>
    </div>
</div>

@if($mapApiKey)
<div class="map-toolbar"><span>جستجوی دیداری در محدوده نقشه</span><div><button type="button" class="btn light" id="show-list-view" aria-pressed="true">فهرست</button><button type="button" class="btn light" id="show-map-view" aria-pressed="false">نقشه</button></div></div>
@else
<div class="map-toolbar"><span>جستجوی دیداری روی نقشه پس از تنظیم کلید نقشه فعال می‌شود.</span></div>
@endif
<div class="map-layout" id="property-map-layout">
<div class="map-list" id="property-map-list">
<div class="grid">
@forelse($listings as $listing)
    @include('properties.partials.card', ['listing' => $listing])
@empty
    <div class="empty" style="grid-column:1/-1">
        <div class="empty-ico"><svg class="icon" aria-hidden="true"><use href="#i-search"/></svg></div>
        @if($activeFilters)
            <h3>آگهی‌ای با این مشخصات پیدا نشد</h3>
            <p class="muted">فیلترها را کمی کم‌تر کنید یا خودتان اولین آگهی این محدوده را ثبت کنید.</p>
            <div class="actions" style="justify-content:center"><a class="btn lg" href="{{ route('properties.index') }}">پاک‌کردن فیلترها</a><a class="btn light lg" href="{{ route('properties.create') }}">ثبت آگهی</a></div>
        @else
            <h3>هنوز آگهی منتشرشده‌ای نداریم</h3>
            <p class="muted">اولین آگهی بازار را شما ثبت کنید؛ ثبت آگهی رایگان است و بعد از تأیید مدیریت اینجا نمایش داده می‌شود.</p>
            <div class="actions" style="justify-content:center"><a class="btn lg" href="{{ route('properties.create') }}"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>ثبت آگهی رایگان</a></div>
        @endif
    </div>
@endforelse
</div>
<div class="pagination">{{ $listings->links() }}</div>
</div>
<aside class="map-panel" id="property-map-panel">
    @if($mapApiKey)
        <div id="property-results-map" aria-label="نقشه آگهی‌های ملکی"></div>
        <p class="map-caption">موقعیت نشانگرها تقریبی است و نشانی دقیق ملک نمایش داده نمی‌شود.</p>
        <p class="map-status" id="property-map-status" role="status">برای مشاهده آگهی‌های محدوده، نقشه را جابه‌جا کنید.</p>
    @else
        <div class="empty"><h3>نقشه هنوز فعال نشده</h3><p class="muted">پس از ثبت کلید نقشه در تنظیمات سرور، آگهی‌های دارای موقعیت روی نقشه نمایش داده می‌شوند.</p></div>
    @endif
</aside>
</div>
@if($mapApiKey)
    @push('scripts')
    <script src="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.umd.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('property-results-map');
        if (!el || !window.maplibregl) return;
        const map = new maplibregl.Map({container: el, style: 'https://static.neshan.org/sdk/maplibre/styles/light.json', center: [51.389, 35.6892], zoom: 10, apiKey: @json($mapApiKey)});
        const markers = [];
        const status = document.getElementById('property-map-status');
        let requestId = 0;
        const clearMarkers = () => { markers.forEach(marker => marker.remove()); markers.length = 0; };
        const refresh = async () => {
            const id = ++requestId;
            const b = map.getBounds();
            const params = new URLSearchParams(window.location.search);
            params.set('south', b.getSouth()); params.set('west', b.getWest());
            params.set('north', b.getNorth()); params.set('east', b.getEast());
            if (status) status.textContent = 'در حال جستجوی آگهی‌های این محدوده…';
            try {
                const response = await fetch(@json(route('properties.map-search')) + '?' + params.toString(), {headers: {'Accept': 'application/json'}});
                if (!response.ok) throw new Error('map search failed');
                const payload = await response.json();
                if (id !== requestId) return;
                clearMarkers();
                payload.data.forEach(point => {
                    const node = document.createElement('button');
                    node.type = 'button'; node.className = 'property-map-marker'; node.textContent = point.price;
                    node.setAttribute('aria-label', point.title + '، ' + point.price);
                    const popupContent = document.createElement('div');
                    const title = document.createElement('strong'); title.textContent = point.title;
                    const price = document.createElement('p'); price.textContent = point.price;
                    const link = document.createElement('a'); link.href = point.url; link.textContent = 'مشاهده آگهی';
                    popupContent.append(title, price, link);
                    const marker = new maplibregl.Marker({element: node, anchor: 'bottom'})
                        .setLngLat([point.longitude, point.latitude])
                        .setPopup(new maplibregl.Popup({offset: 18}).setDOMContent(popupContent)).addTo(map);
                    markers.push(marker);
                });
                if (status) status.textContent = payload.data.length + ' آگهی دارای موقعیت در این محدوده پیدا شد.';
            } catch (error) {
                if (status && id === requestId) status.textContent = 'دریافت آگهی‌های نقشه ممکن نشد؛ دوباره تلاش کنید.';
            }
        };
        map.on('load', refresh); map.on('moveend', refresh);
        map.on('error', () => { if (status) status.textContent = 'بارگذاری نقشه با خطا مواجه شد؛ کلید و سرویس نقشه را بررسی کنید.'; });
        const layout = document.getElementById('property-map-layout');
        const listButton = document.getElementById('show-list-view');
        const mapButton = document.getElementById('show-map-view');
        const setView = view => {
            layout.dataset.view = view;
            listButton.setAttribute('aria-pressed', view === 'list' ? 'true' : 'false');
            mapButton.setAttribute('aria-pressed', view === 'map' ? 'true' : 'false');
            if (view === 'map') setTimeout(() => map.resize(), 80);
        };
        listButton.addEventListener('click', () => setView('list'));
        mapButton.addEventListener('click', () => setView('map'));
        setView('list');
    });
    </script>
    @endpush
@endif
@endsection
