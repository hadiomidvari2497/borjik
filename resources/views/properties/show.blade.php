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
    $moreFacts = array_slice($facts, 4); // چهار ویژگی کلیدی بالای صفحه تکرار نشوند
    $ownerName = $listing->user->display_name ?? '';
    $priceRows = $listing->priceRows();
    $shortRows = $listing->priceRows(true);
    $perMeter = ($listing->listing_type === 'sale' && (int) $listing->price > 0 && (float) $listing->area > 0)
        ? (int) round(((int) $listing->price) / (float) $listing->area) : null;
    $isOwner = auth()->check() && (int) auth()->id() === (int) $listing->user_id;
    $sellerPhone = $listing->user?->mobile;
    $publicPhone = $sellerPhone ? preg_replace('/[^0-9+]/', '', (string) $sellerPhone) : '';
    $canCall = (bool) $listing->allow_phone_call && $sellerPhone && $publicPhone;
    $canSms = (bool) $listing->allow_sms && $sellerPhone && $publicPhone;
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
@if($mapPoint && $mapApiKey)
@push('head')<link rel="stylesheet" href="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.css">@endpush
@endif
@push('head')<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>@endpush
@if($mapPoint && $mapApiKey)
@push('scripts')
<script src="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.umd.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const el=document.getElementById('property-detail-map');
    if(!el||!window.maplibregl)return;
    const lng=@json($mapPoint['longitude']),lat=@json($mapPoint['latitude']);
    const map=new maplibregl.Map({container:el,style:'https://static.neshan.org/sdk/maplibre/styles/light.json',center:[lng,lat],zoom:15,apiKey:@json($mapApiKey)});
    map.on('load',function(){
        map.addSource('property-detail-location',{type:'geojson',data:{type:'Feature',geometry:{type:'Point',coordinates:[lng,lat]},properties:{}}});
        map.addLayer({id:'property-detail-location-point',type:'circle',source:'property-detail-location',paint:{'circle-radius':10,'circle-color':'#16845f','circle-stroke-color':'#fff','circle-stroke-width':3}});
    });
});
</script>
@endpush
@endif
@push('scripts')<script src="{{ asset('js/marketplace.js') }}?v={{ @filemtime(public_path('js/marketplace.js')) }}" defer></script>@endpush
@section('content')
<style>
.property-location-map{height:340px;min-height:280px;width:100%;overflow:hidden;border-radius:18px;background:#edf3ef}
.property-location-note{margin:10px 2px 0;color:#72857d;font-size:12px;line-height:1.8}
@media(max-width:600px){.property-location-map{height:260px;min-height:240px}}
.seller-contact-backdrop{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(12,28,24,.58);backdrop-filter:blur(5px)}
.seller-contact-backdrop[hidden]{display:none}
.seller-contact-dialog{position:relative;width:min(100%,430px);max-height:min(88vh,650px);overflow:auto;padding:28px 22px 20px;border:1px solid #e1ece7;border-radius:24px;background:#fff;box-shadow:0 24px 80px rgba(0,0,0,.24);direction:rtl}
.seller-contact-close{position:absolute;top:12px;left:14px;width:36px;height:36px;border:0;border-radius:50%;background:#f0f5f2;color:#49645b;font-size:26px;line-height:1;cursor:pointer}
.seller-contact-heading{text-align:center;padding:5px 12px 20px}
.seller-contact-mark{display:inline-grid;place-items:center;width:54px;height:54px;border-radius:18px;background:#eaf5ef;color:#24765e;font-size:26px}
.seller-contact-heading h2{margin:12px 0 5px;font-size:19px;color:#183e34}
.seller-contact-heading p,.seller-contact-footnote{margin:0;color:#7b8d86;font-size:12px;line-height:1.9}
.seller-contact-options{display:grid;gap:10px}
.seller-contact-option{display:flex;align-items:center;gap:13px;width:100%;min-height:72px;padding:12px 14px;border:1px solid #e5eee9;border-radius:15px;background:#fbfdfc;color:#25483d;text-decoration:none;text-align:right;cursor:pointer;transition:border-color .18s,background .18s,transform .18s}
.seller-contact-option:hover{border-color:#8fc4ae;background:#f2faf5;transform:translateY(-1px)}
.seller-contact-icon{display:grid;place-items:center;flex:0 0 43px;height:43px;border-radius:13px;background:#e9f4ee;color:#26775e;font-size:22px}
.seller-contact-copy{display:grid;gap:4px;flex:1;min-width:0}
.seller-contact-copy b{font-size:14px}
.seller-contact-copy small{color:#71867d;font-size:12px;direction:ltr;text-align:right;overflow-wrap:anywhere}
.seller-contact-arrow{color:#8ba99b;font-size:19px}
.seller-contact-option.is-disabled{cursor:not-allowed;opacity:.56;background:#f6f7f6;transform:none}
.seller-contact-option.is-disabled .seller-contact-icon{background:#edf0ee;color:#89958f}
.seller-contact-footnote{margin-top:14px;text-align:center}
@media(max-width:480px){.seller-contact-backdrop{align-items:flex-end;padding:10px}.seller-contact-dialog{width:100%;max-height:85vh;border-radius:22px 22px 16px 16px;padding:26px 16px 18px}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-contact-modal]');
    const openButtons = document.querySelectorAll('[data-contact-open]');
    if (!modal || !openButtons.length) return;
    const closeButton = modal.querySelector('[data-contact-close]');
    let previousFocus = null;
    function openModal() {
        previousFocus = document.activeElement;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        closeButton?.focus();
    }
    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        previousFocus?.focus();
    }
    openButtons.forEach(function (button) { button.addEventListener('click', openModal); });
    closeButton?.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
});
</script>
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

        @if($moreFacts)
        <section class="panel rise">
            <h2>سایر مشخصات ملک</h2>
            <div class="facts">
                @foreach($moreFacts as [$icon, $value, $label])
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

        @if($mapPoint)
        <section class="panel rise" aria-labelledby="property-location-title">
            <h2 id="property-location-title">موقعیت ملک روی نقشه</h2>
            @if($mapApiKey)
                <div id="property-detail-map" class="property-location-map" role="img" aria-label="موقعیت تقریبی ملک روی نقشه"></div>
                <p class="property-location-note">برای حفظ حریم خصوصی، نشانگر محل تقریبی ملک را نمایش می‌دهد و نه نشانی دقیق آن را.</p>
            @else
                <p class="property-location-note">موقعیت این ملک ثبت شده است؛ نمایش نقشه پس از فعال‌سازی سرویس نقشه در دسترس خواهد بود.</p>
            @endif
        </section>
        @endif

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
        <div class="pricebox rise @if($isOwner) is-owner @endif" id="contact">
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
                @unless($isOwner)<div class="owner">
                    <span class="avatar" aria-hidden="true">{{ \Illuminate\Support\Str::substr($ownerName ?: '؟', 0, 1) }}</span>
                    <div><b>{{ $ownerName }}</b><span class="muted">ثبت‌کننده آگهی</span></div>
                </div>@endunless
                @if($isOwner)
                    <p>این آگهی متعلق به شماست.</p>
                    <a class="btn block lg" href="{{ route('properties.inquiries.index') }}"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>مشاهده گفتگوها</a>
                @else
                    <button class="btn block lg aside-cta" type="button" data-contact-open aria-haspopup="dialog" aria-controls="seller-contact-modal"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>ارتباط با فروشنده</button>
                @endif
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

@unless($isOwner)
@auth
    <form id="seller-chat-start" method="POST" action="{{ route('properties.inquiries.store', $listing) }}" hidden>
        @csrf
        <input type="hidden" name="message" value="سلام، درباره آگهی «{{ $listing->title }}» مایل به گفتگو هستم.">
    </form>
@endauth
                <div class="seller-contact-backdrop" id="seller-contact-modal" data-contact-modal hidden>
            <section class="seller-contact-dialog" role="dialog" aria-modal="true" aria-labelledby="seller-contact-title">
                <button type="button" class="seller-contact-close" data-contact-close aria-label="بستن پنجره">×</button>
                <div class="seller-contact-heading">
                    <span class="seller-contact-mark" aria-hidden="true">↗</span>
                    <h2 id="seller-contact-title">ارتباط با فروشنده</h2>
                    <p>روش موردنظرتان را برای ارتباط با ثبت‌کننده آگهی انتخاب کنید.</p>
                </div>
                <div class="seller-contact-options">
                    @if($canCall)
                        <a class="seller-contact-option" href="tel:{{ $publicPhone }}">
                            <span class="seller-contact-icon" aria-hidden="true">☎</span>
                            <span class="seller-contact-copy"><b>تماس</b><small>{{ $sellerPhone }}</small></span>
                            <span class="seller-contact-arrow" aria-hidden="true">←</span>
                        </a>
                    @else
                        <div class="seller-contact-option is-disabled" aria-disabled="true">
                            <span class="seller-contact-icon" aria-hidden="true">☎</span>
                            <span class="seller-contact-copy"><b>تماس</b><small>تماس تلفنی توسط فروشنده غیرفعال شده است</small></span>
                        </div>
                    @endif
                    @if($canSms)
                        <a class="seller-contact-option" href="sms:{{ $publicPhone }}">
                            <span class="seller-contact-icon" aria-hidden="true">✉</span>
                            <span class="seller-contact-copy"><b>پیامک</b><small>{{ $sellerPhone }}</small></span>
                            <span class="seller-contact-arrow" aria-hidden="true">←</span>
                        </a>
                    @else
                        <div class="seller-contact-option is-disabled" aria-disabled="true">
                            <span class="seller-contact-icon" aria-hidden="true">✉</span>
                            <span class="seller-contact-copy"><b>پیامک</b><small>ارسال پیامک توسط فروشنده غیرفعال شده است</small></span>
                        </div>
                    @endif
                    @auth
                        <button class="seller-contact-option" type="submit" form="seller-chat-start">
                            <span class="seller-contact-icon" aria-hidden="true">☷</span>
                            <span class="seller-contact-copy"><b>چت</b><small>گفتگوی مستقیم داخل صابخونه</small></span>
                            <span class="seller-contact-arrow" aria-hidden="true">←</span>
                        </button>
                    @else
                        <a class="seller-contact-option" href="{{ route('login') }}">
                            <span class="seller-contact-icon" aria-hidden="true">☷</span>
                            <span class="seller-contact-copy"><b>چت</b><small>برای شروع گفتگو وارد حساب کاربری شوید</small></span>
                            <span class="seller-contact-arrow" aria-hidden="true">←</span>
                        </a>
                    @endauth
                </div>
                <p class="seller-contact-footnote">شماره موبایل فقط برای روش‌هایی نمایش داده می‌شود که فروشنده فعال کرده باشد.</p>
            </section>
        </div>
@endunless

<div class="mbar-spacer" aria-hidden="true"></div>
<div class="mbar" role="region" aria-label="دسترسی سریع">
    <div class="mbar-price">
        @foreach(array_slice($shortRows, 0, 1) as $row)
            @if($row['label'])<small>{{ $row['label'] }}</small>@endif<b>{{ $row['value'] }}</b>
        @endforeach
    </div>
    @if($isOwner)
        <a class="btn lg" href="{{ route('properties.inquiries.index') }}"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>گفتگوها</a>
    @else
        @if($canCall)<a class="btn lg" href="tel:{{ $publicPhone }}"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg>تماس</a>@endif
        <button class="btn @if($canCall) light @endif lg" type="button" data-contact-open aria-haspopup="dialog" aria-controls="seller-contact-modal"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>ارتباط</button>
    @endif
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
