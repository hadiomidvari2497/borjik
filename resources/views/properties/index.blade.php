@extends('layouts.marketplace')
@section('title', 'خرید، فروش و اجاره ملک')
@section('content')
@php
    use App\Models\PropertyListing;
    use App\Support\Fa;

    $activeType = request('listing_type');
    $tabQuery = fn (?string $type) => route('properties.index', array_filter(array_merge(request()->except(['page', 'listing_type']), ['listing_type' => $type]), fn ($v) => $v !== null && $v !== ''));
    $money = fn ($v) => \App\Support\Fa::short((int) $v);
    $activeFilters = array_values(array_filter([
        request()->filled('q') ? '«'.\Illuminate\Support\Str::limit(request('q'), 18).'»' : null,
        $activeType ? (PropertyListing::LISTING_TYPES[$activeType] ?? null) : null,
        request()->filled('province') ? request('province') : null,
        request()->filled('city') ? request('city') : null,
        request()->filled('neighborhood') ? request('neighborhood') : null,
        request()->filled('property_type') ? (PropertyListing::PROPERTY_TYPES[request('property_type')] ?? null) : null,
        request()->filled('min_area') ? 'از '.Fa::digits(request('min_area')).' متر' : null,
        request()->filled('max_area') ? 'تا '.Fa::digits(request('max_area')).' متر' : null,
        request()->filled('min_price') ? 'از '.$money(request('min_price')) : null,
        request()->filled('max_price') ? 'تا '.$money(request('max_price')) : null,
        request()->filled('bedrooms') ? 'حداقل '.Fa::digits(request('bedrooms')).' خواب' : null,
        request('parking') === '1' ? 'پارکینگ‌دار' : null,
    ]));
@endphp

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
        @if($activeFilters)<span class="sum-count">{{ Fa::num(count($activeFilters)) }}</span>@endif
        <span class="sum-chev" aria-hidden="true"></span>
    </summary>
    <form class="search-body" method="GET" action="{{ route('properties.index') }}" role="search" aria-label="جستجوی آگهی">
        @if($activeType)<input type="hidden" name="listing_type" value="{{ $activeType }}">@endif
        <div class="seg" role="navigation" aria-label="نوع آگهی">
            <a href="{{ $tabQuery(null) }}" @if(! $activeType) aria-current="true" @endif>همه آگهی‌ها</a>
            @foreach(PropertyListing::LISTING_TYPES as $key => $label)
                <a href="{{ $tabQuery($key) }}" @if($activeType === $key) aria-current="true" @endif>{{ $label }}</a>
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
                    @foreach(PropertyListing::PROPERTY_TYPES as $k => $v)<option value="{{ $k }}" @selected(request('property_type') === $k)>{{ $v }}</option>@endforeach
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
    <h2>{{ $activeType ? 'آگهی‌های '.(PropertyListing::LISTING_TYPES[$activeType] ?? '') : 'تازه‌ترین آگهی‌ها' }}</h2>
    <div class="results-tools">
        @if($listings->total() > 0)<span>{{ Fa::num($listings->total()) }} آگهی</span>@endif
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
@endsection
