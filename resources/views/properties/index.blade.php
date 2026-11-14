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
        <p class="map-caption">دقت موقعیت هر آگهی مطابق انتخاب ثبت‌کننده است؛ آگهی‌های دارای موقعیت تقریبی با جابه‌جایی نشان داده می‌شوند.</p>
        <p class="map-status" id="property-map-status" role="status">برای مشاهده آگهی‌های محدوده، نقشه را جابه‌جا کنید.</p>
        <ul class="map-legend" aria-label="راهنمای نقشه"><li><i></i>موقعیت دقیق</li><li><i class="is-halo"></i>موقعیت تقریبی</li><li><i class="is-cluster"></i>چند آگهی نزدیک هم</li></ul>
    @else
        <div class="empty"><h3>نقشه هنوز فعال نشده</h3><p class="muted">پس از ثبت کلید نقشه در تنظیمات سرور، آگهی‌های دارای موقعیت روی نقشه نمایش داده می‌شوند.</p></div>
    @endif
</aside>
</div>
@if($mapApiKey)
    @push('scripts')
    <script src="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.umd.js"></script>
    <script src="{{ asset('js/market-map.js') }}?v={{ @filemtime(public_path('js/market-map.js')) }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('property-results-map');
        if (!el || !window.maplibregl || !window.MarketMap) return;
        const M = window.MarketMap;
        const map = M.create({container: el, apiKey: @json($mapApiKey), center: M.CENTER, zoom: 5, locate: true});
        const status = document.getElementById('property-map-status');
        const panel = document.getElementById('property-map-panel');
        const SRC = 'property-listing-points';
        const endpoint = @json(route('properties.map-search'));
        let controller = null, initialLoad = true, sourceReady = false, failed = false;

        const setStatus = (text, loading) => {
            if (status) status.textContent = text;
            if (panel) panel.classList.toggle('is-loading', Boolean(loading));
            el.setAttribute('aria-busy', loading ? 'true' : 'false');
        };

        /* کارت شناور آگهی انتخاب‌شده */
        const card = document.createElement('div');
        card.className = 'property-map-feature-card';
        card.hidden = true;
        card.innerHTML = '<button type="button" class="property-map-feature-close" aria-label="بستن">×</button><strong></strong><p><span class="pm-price"></span><span class="pm-vis"></span></p><a></a>';
        const cardTitle = card.querySelector('strong'), cardPrice = card.querySelector('.pm-price'), cardVis = card.querySelector('.pm-vis'), cardLink = card.querySelector('a');
        const selectFeature = (id) => { if (sourceReady) map.setFilter('property-listing-selected', ['==', ['get', 'id'], id == null ? -1 : id]); };
        const closeCard = () => { card.hidden = true; selectFeature(null); };
        card.addEventListener('click', (event) => event.stopPropagation());
        card.querySelector('button').addEventListener('click', closeCard);
        el.appendChild(card);

        const collection = (points) => ({type: 'FeatureCollection', features: points.map((p) => ({
            type: 'Feature',
            geometry: {type: 'Point', coordinates: [p.longitude, p.latitude]},
            properties: {id: p.id, title: p.title, price: p.price, url: p.url, location_visibility: p.location_visibility}
        }))});

        const refresh = async () => {
            if (controller) controller.abort();
            controller = new AbortController();
            const signal = controller.signal, params = new URLSearchParams(window.location.search);
            if (!initialLoad) {
                const b = map.getBounds();
                params.set('south', b.getSouth()); params.set('west', b.getWest()); params.set('north', b.getNorth()); params.set('east', b.getEast());
            }
            setStatus('در حال جستجوی آگهی‌های این محدوده…', true);
            try {
                const response = await fetch(endpoint + '?' + params.toString(), {headers: {'Accept': 'application/json'}, signal});
                if (!response.ok) throw new Error('map search failed');
                const payload = await response.json();
                map.getSource(SRC).setData(collection(payload.data));
                if (initialLoad) {
                    initialLoad = false;
                    if (payload.data.length === 1) {
                        map.easeTo({center: [payload.data[0].longitude, payload.data[0].latitude], zoom: 14});
                    } else if (payload.data.length > 1) {
                        const coords = payload.data.map((p) => [p.longitude, p.latitude]);
                        const bounds = coords.reduce((acc, point) => acc.extend(point), new maplibregl.LngLatBounds(coords[0], coords[0]));
                        map.fitBounds(bounds, {padding: 64, maxZoom: 14, duration: 500});
                    }
                }
                setStatus(payload.data.length
                    ? M.fa(payload.data.length) + ' آگهی دارای موقعیت در این محدوده پیدا شد.'
                    : 'در این محدوده آگهی‌ای با موقعیت ثبت‌شده نیست؛ نقشه را جابه‌جا یا کوچک‌نمایی کنید.', false);
            } catch (error) {
                if (error && error.name === 'AbortError') return;
                setStatus('دریافت آگهی‌های نقشه ممکن نشد؛ نقشه را کمی جابه‌جا کنید یا دوباره تلاش کنید.', false);
            }
        };
        const refreshSoon = M.debounce(refresh, 350);

        map.on('load', function () {
            map.addSource(SRC, {type: 'geojson', data: collection([]), cluster: true, clusterMaxZoom: 14, clusterRadius: 46});
            const single = ['!', ['has', 'point_count']];
            // هاله‌ی موقعیت تقریبی
            map.addLayer({id: 'property-listing-halo', type: 'circle', source: SRC,
                filter: ['all', single, ['==', ['get', 'location_visibility'], 'approximate']],
                paint: {'circle-radius': ['interpolate', ['linear'], ['zoom'], 9, 10, 13, 26, 16, 54], 'circle-color': '#16845f', 'circle-opacity': 0.14, 'circle-stroke-color': '#16845f', 'circle-stroke-width': 1, 'circle-stroke-opacity': 0.35}});
            map.addLayer({id: 'property-listing-points', type: 'circle', source: SRC, filter: single,
                paint: {'circle-radius': ['interpolate', ['linear'], ['zoom'], 5, 6, 14, 10], 'circle-color': '#16845f', 'circle-stroke-color': '#fff', 'circle-stroke-width': 2}});
            map.addLayer({id: 'property-listing-selected', type: 'circle', source: SRC, filter: ['==', ['get', 'id'], -1],
                paint: {'circle-radius': 17, 'circle-color': 'rgba(22,132,95,.18)', 'circle-stroke-color': '#0f6a4b', 'circle-stroke-width': 2.5}});
            map.addLayer({id: 'property-listing-labels', type: 'symbol', source: SRC, filter: single, minzoom: 11,
                layout: {'text-field': ['get', 'price'], 'text-size': 12, 'text-offset': [0, 1.8], 'text-anchor': 'top', 'text-optional': true},
                paint: {'text-color': '#174b3a', 'text-halo-color': '#fff', 'text-halo-width': 1.5}});
            map.addLayer({id: 'property-clusters', type: 'circle', source: SRC, filter: ['has', 'point_count'],
                paint: {'circle-color': ['step', ['get', 'point_count'], '#16845f', 10, '#0f6a4b', 50, '#0b4f39'], 'circle-radius': ['step', ['get', 'point_count'], 18, 10, 22, 50, 28], 'circle-stroke-color': '#fff', 'circle-stroke-width': 3}});
            const countLayer = (field) => ({id: 'property-cluster-count', type: 'symbol', source: SRC, filter: ['has', 'point_count'],
                layout: {'text-field': field, 'text-size': 13, 'text-allow-overlap': true}, paint: {'text-color': '#fff'}});
            try { map.addLayer(countLayer(['number-format', ['get', 'point_count'], {locale: 'fa'}])); }
            catch (error) { map.addLayer(countLayer(['to-string', ['get', 'point_count']])); }
            sourceReady = true;

            map.on('click', (event) => {
                let features = [];
                try { features = map.queryRenderedFeatures(event.point, {layers: ['property-clusters', 'property-listing-points']}) || []; }
                catch (error) { console.error('Property map hit-test failed:', error); }
                if (!features.length) { closeCard(); return; }
                const feature = features[0], p = feature.properties || {};
                if (p.point_count) {
                    // خوشه: به همان نقطه نزدیک‌تر شو تا آگهی‌ها از هم جدا شوند.
                    map.easeTo({center: feature.geometry.coordinates, zoom: Math.min(map.getZoom() + 2.5, 17), duration: 450});
                    return;
                }
                cardTitle.textContent = p.title || 'جزئیات آگهی';
                cardPrice.textContent = p.price || '';
                cardVis.textContent = p.location_visibility === 'exact' ? 'موقعیت دقیق' : 'موقعیت تقریبی';
                cardLink.href = p.url || '#'; cardLink.textContent = 'مشاهده آگهی'; cardLink.hidden = !p.url;
                card.hidden = false;
                selectFeature(p.id);
            });
            ['property-listing-points', 'property-clusters'].forEach((layer) => {
                map.on('mouseenter', layer, () => { map.getCanvas().style.cursor = 'pointer'; });
                map.on('mouseleave', layer, () => { map.getCanvas().style.cursor = ''; });
            });
            refresh();
        });
        map.on('moveend', () => { if (sourceReady) refreshSoon(); });
        map.on('error', () => {
            if (failed) return;
            failed = true;
            setStatus('بارگذاری نقشه با خطا مواجه شد؛ کلید و سرویس نقشه را بررسی کنید.', false);
        });

        const layout = document.getElementById('property-map-layout'), listButton = document.getElementById('show-list-view'), mapButton = document.getElementById('show-map-view');
        const setView = (view) => {
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
