@php
    $F = \App\Support\Fa::class;

    $photos = $listing->photoUrls();
    $tagClass = match($listing->listing_type) { 'rent' => 'rent', 'buy_request', 'rent_request' => 'req', default => '' };
    $facts = array_values(array_filter([
        $listing->area ? ['i-area', $F::num((float) $listing->area).' متر', 'متراژ'] : null,
        $listing->bedrooms !== null ? ['i-bed', $F::num($listing->bedrooms), 'اتاق خواب'] : null,
        $listing->bathrooms !== null ? ['i-bath', $F::num($listing->bathrooms), 'سرویس'] : null,
        $listing->parking_count !== null ? ['i-car', $F::num($listing->parking_count), 'پارکینگ'] : null,
        $listing->floor !== null ? ['i-floor', $F::num($listing->floor).($listing->total_floors ? ' از '.$F::num($listing->total_floors) : ''), 'طبقه'] : null,
        $listing->built_year ? ['i-cal', $F::digits($listing->built_year), 'سال ساخت'] : null,
        $listing->document_type ? ['i-doc', $listing->document_type, 'نوع سند'] : null,
        $listing->allow_exchange ? ['i-swap', 'پذیرفته می‌شود', 'معاوضه'] : null,
    ]));
    $highlights = array_slice($facts, 0, 4);
    $ownerName = $listing->user->display_name ?? '';
    $priceRows = $listing->priceRows();
    $shortRows = $listing->priceRows(true);
    $perMeter = ($listing->listing_type === 'sale' && (int) $listing->price > 0 && (float) $listing->area > 0)
        ? (int) round(((int) $listing->price) / (float) $listing->area) : null;
    $isOwner = auth()->check() && (int) auth()->id() === (int) $listing->user_id;
    $publicPhone = ($listing->show_phone && $listing->user?->mobile) ? preg_replace('/[^0-9+]/', '', (string) $listing->user->mobile) : null;
    $amenities = collect($listing->amenities ?? [])->filter(fn ($a) => is_string($a) && trim($a) !== '')->values();
    $days = $listing->created_at ? (int) floor(abs($listing->created_at->diffInDays(now()))) : null;
    $ago = match (true) {
        $days === null => null,
        $days <= 0 => 'امروز',
        $days === 1 => 'دیروز',
        $days < 30 => $F::num($days).' روز پیش',
        $days < 365 => $F::num(intdiv($days, 30)).' ماه پیش',
        default => $F::num(intdiv($days, 365)).' سال پیش',
    };
    $descLong = mb_strlen((string) $listing->description) > 420;
    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateListing',
        'name' => $listing->title,
        'url' => route('properties.show', $listing),
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) $listing->description), 300),
        'datePosted' => $listing->created_at?->toIso8601String(),
        'image' => $photos ? array_slice($photos, 0, 6) : null,
        'contentLocation' => array_filter([
            '@type' => 'Place',
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'addressRegion' => $listing->province ?: null,
                'addressLocality' => $listing->city ?: null,
            ], fn ($v) => $v !== null),
        ]),
    ], fn ($v) => $v !== null && $v !== []);
@endphp
@extends('layouts.marketplace')
@section('title', $listing->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($listing->description), 150))
@push('head')<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>@endpush
@push('scripts')<script src="{{ asset('js/marketplace.js') }}?v={{ @filemtime(public_path('js/marketplace.js')) }}" defer></script>@endpush
@section('content')
@if(session('status'))<div class="notice" style="margin-bottom:16px">{{ session('status') }}</div>@endif

<nav class="crumbs" aria-label="مسیر">
    <a href="{{ route('properties.index') }}">آگهی‌ها</a><span class="sep">/</span>
    @if($listing->city)<a href="{{ route('properties.index', ['city' => $listing->city]) }}">{{ $listing->city }}</a><span class="sep">/</span>@endif
    <span>{{ \Illuminate\Support\Str::limit($listing->title, 48) }}</span>
</nav>

<div class="detail">
    <div class="detail-main">
        <header class="titlebar rise">
            <div class="titlebar-top">
                <div class="tags">
                    <span class="tag {{ $tagClass }}">{{ $listing->listingTypeLabel() }}</span>
                    @if($listing->propertyTypeLabel())<span class="tag plain">{{ $listing->propertyTypeLabel() }}</span>@endif
                </div>
                <div class="tools">
                    <button type="button" class="chip-btn" data-share data-title="{{ $listing->title }}" data-url="{{ route('properties.show', $listing) }}"><svg class="icon" aria-hidden="true"><use href="#i-share"/></svg><span>اشتراک‌گذاری</span></button>
                </div>
            </div>
            <h1>{{ $listing->title }}</h1>
            <div class="meta">
                <span class="meta-item"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg>{{ $listing->city }}@if($listing->neighborhood)، {{ $listing->neighborhood }}@endif</span>
                @if($ago)<span class="meta-item" title="{{ $listing->created_at ? $F::date($listing->created_at, 'j F Y') : '' }}"><svg class="icon" aria-hidden="true"><use href="#i-clock"/></svg>{{ $ago }}</span>@endif
                <span class="meta-item"><svg class="icon" aria-hidden="true"><use href="#i-hash"/></svg>کد آگهی {{ $F::num($listing->id) }}</span>
            </div>
            <div class="price price-inline">
                @foreach($priceRows as $row)
                    <div>@if($row['label'])<small>{{ $row['label'] }}</small>@endif<span>{{ $row['value'] }}</span></div>
                @endforeach
            </div>
            @if($highlights)
            <ul class="highlights" aria-label="ویژگی‌های کلیدی">
                @foreach($highlights as [$icon, $value, $label])
                    <li><span class="hl-ic"><svg class="icon" aria-hidden="true"><use href="#{{ $icon }}"/></svg></span><b>{{ $value }}</b><small>{{ $label }}</small></li>
                @endforeach
            </ul>
            @endif
        </header>

        @if($photos)
        <section class="gallery rise @if(count($photos) < 2) single @endif" data-gallery tabindex="0" aria-roledescription="اسلایدر" aria-label="تصاویر آگهی {{ $listing->title }}">
            <div class="stage">
                <div class="track">
                    @foreach($photos as $i => $url)
                        <figure class="slide" aria-label="تصویر {{ $F::num($i + 1) }} از {{ $F::num(count($photos)) }}">
                            <img src="{{ $url }}" alt="{{ $listing->title }} — تصویر {{ $F::num($i + 1) }}" @if($i > 0) loading="lazy" @endif decoding="async" draggable="false">
                        </figure>
                    @endforeach
                </div>
                <button type="button" class="nav-btn prev" aria-label="تصویر قبلی"><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></button>
                <button type="button" class="nav-btn next" aria-label="تصویر بعدی"><svg class="icon" aria-hidden="true"><use href="#i-chev-l"/></svg></button>
                <button type="button" class="zoom-btn" data-lightbox-open aria-label="نمایش تمام‌صفحه تصاویر"><svg class="icon" aria-hidden="true"><use href="#i-expand"/></svg><span>تمام‌صفحه</span></button>
                <span class="pos" aria-live="polite">{{ $F::num(1) }} از {{ $F::num(count($photos)) }}</span>
                <div class="dots" aria-hidden="true"></div>
            </div>
            @if(count($photos) > 1)
            <div class="thumbs" aria-label="پیش‌نمایش تصاویر">
                @foreach($photos as $i => $url)
                    <button type="button" aria-label="نمایش تصویر {{ $F::num($i + 1) }}" @if($i === 0) aria-current="true" @endif><img src="{{ $url }}" alt="" loading="lazy" decoding="async"></button>
                @endforeach
            </div>
            @endif
        </section>
        @else
        <section class="gallery rise"><div class="gallery-empty"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg><span>برای این آگهی تصویری ثبت نشده است</span></div></section>
        @endif

        @if($facts)
        <section class="panel rise">
            <h2>مشخصات ملک</h2>
            <div class="facts">
                @foreach($facts as [$icon, $value, $label])
                    <div class="fact"><span class="fact-ic"><svg class="icon" aria-hidden="true"><use href="#{{ $icon }}"/></svg></span><div><b>{{ $value }}</b><span>{{ $label }}</span></div></div>
                @endforeach
            </div>
        </section>
        @endif

        <section class="panel rise">
            <h2>توضیحات</h2>
            <div class="desc-box @if($descLong) is-collapsible @endif" @if($descLong) data-collapsible @endif>
                <p class="desc">{{ $listing->description }}</p>
            </div>
            @if($descLong)<button type="button" class="more-btn" data-collapse-toggle hidden aria-expanded="false"><span>نمایش بیشتر</span><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></button>@endif
            @if($listing->address && ! $listing->hide_address)
                <p class="address"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg><span>آدرس: {{ $listing->address }}</span></p>
            @endif
        </section>

        @if($amenities->isNotEmpty())
        <section class="panel rise">
            <h2>امکانات و ویژگی‌ها</h2>
            <ul class="amenities">
                @foreach($amenities as $amenity)
                    <li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>{{ $amenity }}</li>
                @endforeach
            </ul>
        </section>
        @endif

        <section class="panel safety rise">
            <h2><svg class="icon" aria-hidden="true"><use href="#i-shield"/></svg>نکات ایمنی معامله</h2>
            <ul>
                <li>پیش از بازدید حضوری و بررسی اصالت سند، هیچ مبلغی (بیعانه، ودیعه یا پیش‌پرداخت) واریز نکنید.</li>
                <li>اطلاعات حساب، رمز پیامکی و کد تأیید را در اختیار هیچ‌کس قرار ندهید.</li>
                <li>قرارداد را با حضور طرفین و در دفتر اسناد رسمی یا مشاور املاک معتبر تنظیم کنید.</li>
                <li>اگر آگهی مشکوک بود، از بخش «گزارش تخلف» به ما اطلاع دهید.</li>
            </ul>
        </section>
    </div>

    <aside class="aside">
        <div class="pricebox rise" id="contact">
            <div class="pricebox-head">
                <small>{{ $listing->listingTypeLabel() }}@if($listing->propertyTypeLabel()) · {{ $listing->propertyTypeLabel() }}@endif</small>
                <div class="price">
                    @foreach($priceRows as $row)
                        <div>@if($row['label'])<small>{{ $row['label'] }}</small>@endif<span>{{ $row['value'] }}</span></div>
                    @endforeach
                </div>
                @if($perMeter)<div class="per-meter"><svg class="icon" aria-hidden="true"><use href="#i-ruler"/></svg>قیمت هر متر: <b>{{ $F::money($perMeter) }}</b></div>@endif
            </div>
            <div class="pricebox-body">
                <div class="owner">
                    <span class="avatar" aria-hidden="true">{{ \Illuminate\Support\Str::substr($ownerName ?: '؟', 0, 1) }}</span>
                    <div><b>{{ $ownerName }}</b><span class="muted">ثبت‌کننده آگهی</span></div>
                </div>
                @if($publicPhone)
                    <div class="actions cta">
                        <a class="btn block lg" href="tel:{{ $publicPhone }}"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg>تماس با فروشنده · {{ $listing->user->mobile }}</a>
                        <a class="btn light block lg" href="sms:{{ $publicPhone }}">ارسال پیامک</a>
                    </div>
                @endif
                @auth
                    @if($isOwner)
                        <p>این آگهی متعلق به شماست.</p>
                        <a class="btn block lg" href="{{ route('properties.inquiries.index') }}"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>مشاهده گفتگوها</a>
                    @else
                        <form method="POST" action="{{ route('properties.inquiries.store', $listing) }}" class="inquiry">
                            @csrf
                            <label class="field">پیام شما
                                <textarea name="message" rows="4" minlength="2" maxlength="2000" required placeholder="سلام، درباره این ملک می‌خواستم بیشتر بدانم...">{{ old('message') }}</textarea>
                            </label>
                            @error('message')<p class="error">{{ $message }}</p>@enderror
                            <label class="check">
                                <input type="checkbox" name="share_mobile" value="1" @checked(old('share_mobile'))>
                                <span>در صورت تمایل، شماره موبایل من هم برای طرف مقابل نمایش داده شود.</span>
                            </label>
                            <button class="btn block lg" type="submit"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>شروع گفتگو با فروشنده</button>
                        </form>
                    @endif
                @else
                    <a class="btn block lg" href="{{ route('login') }}"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>برای گفتگو با فروشنده وارد شوید</a>
                @endauth
            </div>
        </div>

        @auth
            @if(! $isOwner)
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

@if($similar->isNotEmpty())
<section class="similar" aria-labelledby="similar-title">
    <div class="results-head"><h2 id="similar-title">آگهی‌های مشابه</h2><a class="more-link" href="{{ route('properties.index', array_filter(['listing_type' => $listing->listing_type, 'city' => $listing->city])) }}">مشاهده همه ←</a></div>
    <div class="grid">
        @foreach($similar as $item)
            @include('properties.partials.card', ['listing' => $item])
        @endforeach
    </div>
</section>
@endif

<div class="mbar-spacer" aria-hidden="true"></div>
<div class="mbar" role="region" aria-label="دسترسی سریع">
    <div class="mbar-price">
        @foreach(array_slice($shortRows, 0, 1) as $row)
            @if($row['label'])<small>{{ $row['label'] }}</small>@endif<b>{{ $row['value'] }}</b>
        @endforeach
    </div>
    @if($publicPhone)<a class="btn lg" href="tel:{{ $publicPhone }}"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg>تماس</a>@endif
    <a class="btn @if($publicPhone) light @endif lg" href="#contact"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>{{ $isOwner ? 'گفتگوها' : 'پیام' }}</a>
</div>

@if($photos)
<dialog class="lightbox" data-lightbox aria-label="نمایش تصاویر آگهی">
    <button type="button" class="lb-close" data-lb-close aria-label="بستن"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button>
    <div class="lb-stage">
        <img src="" alt="" data-lb-img decoding="async">
        <button type="button" class="lb-nav prev" data-lb-prev aria-label="تصویر قبلی"><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></button>
        <button type="button" class="lb-nav next" data-lb-next aria-label="تصویر بعدی"><svg class="icon" aria-hidden="true"><use href="#i-chev-l"/></svg></button>
    </div>
    <div class="lb-bar"><strong>{{ \Illuminate\Support\Str::limit($listing->title, 60) }}</strong><span data-lb-pos></span></div>
</dialog>
@endif
@endsection
