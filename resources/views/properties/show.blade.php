@extends('layouts.marketplace')
@section('title', $listing->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($listing->description), 150))
@push('scripts')<script src="{{ asset('js/marketplace.js') }}?v={{ @filemtime(public_path('js/marketplace.js')) }}" defer></script>@endpush
@section('content')
@php
    use App\Support\Fa;

    $photos = $listing->photoUrls();
    $tagClass = match($listing->listing_type) { 'rent' => 'rent', 'buy_request', 'rent_request' => 'req', default => '' };
    $facts = array_values(array_filter([
        $listing->area ? ['i-area', Fa::num((float) $listing->area).' متر', 'متراژ'] : null,
        $listing->bedrooms !== null ? ['i-bed', Fa::num($listing->bedrooms), 'اتاق خواب'] : null,
        $listing->bathrooms !== null ? ['i-bath', Fa::num($listing->bathrooms), 'سرویس'] : null,
        $listing->parking_count !== null ? ['i-car', Fa::num($listing->parking_count), 'پارکینگ'] : null,
        $listing->floor !== null ? ['i-floor', Fa::num($listing->floor).($listing->total_floors ? ' از '.Fa::num($listing->total_floors) : ''), 'طبقه'] : null,
        $listing->built_year ? ['i-cal', Fa::digits($listing->built_year), 'سال ساخت'] : null,
        $listing->document_type ? ['i-doc', $listing->document_type, 'نوع سند'] : null,
        $listing->allow_exchange ? ['i-swap', 'پذیرفته می‌شود', 'معاوضه'] : null,
    ]));
    $ownerName = $listing->user->display_name ?? '';
@endphp

<nav class="crumbs" aria-label="مسیر">
    <a href="{{ route('properties.index') }}">آگهی‌ها</a><span class="sep">/</span>
    @if($listing->city)<a href="{{ route('properties.index', ['city' => $listing->city]) }}">{{ $listing->city }}</a><span class="sep">/</span>@endif
    <span>{{ \Illuminate\Support\Str::limit($listing->title, 48) }}</span>
</nav>

<div class="detail">
    <div class="detail-main">
        <header class="titlebar">
            <div class="tags">
                <span class="tag {{ $tagClass }}">{{ $listing->listingTypeLabel() }}</span>
                @if($listing->propertyTypeLabel())<span class="tag plain">{{ $listing->propertyTypeLabel() }}</span>@endif
            </div>
            <h1>{{ $listing->title }}</h1>
            <div class="loc"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg>{{ $listing->city }}@if($listing->neighborhood)، {{ $listing->neighborhood }}@endif</div>
            <div class="price price-inline">
                @foreach($listing->priceRows() as $row)
                    <div>@if($row['label'])<small>{{ $row['label'] }}</small>@endif<span>{{ $row['value'] }}</span></div>
                @endforeach
            </div>
        </header>

        @if($photos)
        <section class="gallery @if(count($photos) < 2) single @endif" data-gallery tabindex="0" aria-roledescription="اسلایدر" aria-label="تصاویر آگهی {{ $listing->title }}">
            <div class="stage">
                <div class="track">
                    @foreach($photos as $i => $url)
                        <figure class="slide" aria-label="تصویر {{ Fa::num($i + 1) }} از {{ Fa::num(count($photos)) }}">
                            <img src="{{ $url }}" alt="{{ $listing->title }} — تصویر {{ Fa::num($i + 1) }}" @if($i > 0) loading="lazy" @endif decoding="async" draggable="false">
                        </figure>
                    @endforeach
                </div>
                <button type="button" class="nav-btn prev" aria-label="تصویر قبلی"><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></button>
                <button type="button" class="nav-btn next" aria-label="تصویر بعدی"><svg class="icon" aria-hidden="true"><use href="#i-chev-l"/></svg></button>
                <span class="pos" aria-live="polite">{{ Fa::num(1) }} از {{ Fa::num(count($photos)) }}</span>
                <div class="dots" aria-hidden="true"></div>
            </div>
            @if(count($photos) > 1)
            <div class="thumbs" aria-label="پیش‌نمایش تصاویر">
                @foreach($photos as $i => $url)
                    <button type="button" aria-label="نمایش تصویر {{ Fa::num($i + 1) }}" @if($i === 0) aria-current="true" @endif><img src="{{ $url }}" alt="" loading="lazy" decoding="async"></button>
                @endforeach
            </div>
            @endif
        </section>
        @else
        <section class="gallery"><div class="gallery-empty"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg><span>برای این آگهی تصویری ثبت نشده است</span></div></section>
        @endif

        @if($facts)
        <section class="panel">
            <h2>مشخصات ملک</h2>
            <div class="facts">
                @foreach($facts as [$icon, $value, $label])
                    <div class="fact"><svg class="icon" aria-hidden="true"><use href="#{{ $icon }}"/></svg><div><b>{{ $value }}</b><span>{{ $label }}</span></div></div>
                @endforeach
            </div>
        </section>
        @endif

        <section class="panel">
            <h2>توضیحات</h2>
            <p class="desc">{{ $listing->description }}</p>
            @if($listing->address && ! $listing->hide_address)
                <p class="loc" style="margin-top:14px"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg>آدرس: {{ $listing->address }}</p>
            @endif
        </section>
    </div>

    <aside class="aside">
        <div class="pricebox">
            <div class="price">
                @foreach($listing->priceRows() as $row)
                    <div>@if($row['label'])<small>{{ $row['label'] }}</small>@endif<span>{{ $row['value'] }}</span></div>
                @endforeach
            </div>
            <div class="owner">
                <span class="avatar" aria-hidden="true">{{ \Illuminate\Support\Str::substr($ownerName ?: '؟', 0, 1) }}</span>
                <div><b>{{ $ownerName }}</b><span class="muted">ثبت‌کننده آگهی</span></div>
            </div>
            @auth
                <p>برای هماهنگی با ثبت‌کننده از طریق اطلاعات تماس حساب کاربری اقدام کنید.</p>
            @else
                <a class="btn block lg" href="{{ route('login') }}">برای مشاهده اطلاعات تماس وارد شوید</a>
            @endauth
        </div>

        @auth
            @if((int) auth()->id() !== (int) $listing->user_id)
                <details class="report" @if($errors->any()) open @endif>
                    <summary>گزارش تخلف یا اطلاعات نادرست</summary>
                    <form method="POST" action="{{ route('properties.reports.store', $listing->id) }}" class="panel">
                        @csrf
                        <label class="field">دلیل گزارش
                            <select name="reason" required>
                                <option value="">انتخاب کنید</option>
                                <option value="fraud" @selected(old('reason') === 'fraud')>احتمال کلاهبرداری</option>
                                <option value="incorrect" @selected(old('reason') === 'incorrect')>اطلاعات نادرست</option>
                                <option value="duplicate" @selected(old('reason') === 'duplicate')>آگهی تکراری</option>
                                <option value="inappropriate" @selected(old('reason') === 'inappropriate')>محتوای نامناسب</option>
                                <option value="prohibited" @selected(old('reason') === 'prohibited')>مورد غیرمجاز</option>
                                <option value="other" @selected(old('reason') === 'other')>سایر موارد</option>
                            </select>
                        </label>
                        <label class="field">توضیحات تکمیلی (اختیاری)
                            <textarea name="details" rows="3" maxlength="1500" placeholder="جزئیات مورد گزارش را بنویسید">{{ old('details') }}</textarea>
                        </label>
                        @error('reason')<p class="error">{{ $message }}</p>@enderror
                        @error('details')<p class="error">{{ $message }}</p>@enderror
                        @error('report')<p class="error">{{ $message }}</p>@enderror
                        <button class="btn" type="submit">ثبت گزارش</button>
                    </form>
                </details>
            @endif
        @else
            <p class="muted" style="padding:0 6px">برای گزارش تخلف این آگهی نیز ابتدا وارد حساب کاربری شوید.</p>
        @endauth
    </aside>
</div>
@endsection
