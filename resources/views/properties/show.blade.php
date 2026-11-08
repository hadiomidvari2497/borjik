@extends('layouts.marketplace')
@section('title', $listing->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($listing->description), 150))
@push('scripts')<script src="{{ asset('js/marketplace.js') }}?v={{ @filemtime(public_path('js/marketplace.js')) }}" defer></script>@endpush
@section('content')
<style>
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
    const openButton = document.querySelector('[data-contact-open]');
    if (!modal || !openButton) return;
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
    openButton.addEventListener('click', openModal);
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
            @if(auth()->check() && (int) auth()->id() === (int) $listing->user_id)
                <p>این آگهی متعلق به شماست.</p>
                <a class="btn block lg" href="{{ route('properties.inquiries.index') }}">مشاهده گفتگوها</a>
            @else
                @php
                    $sellerPhone = $listing->user?->mobile;
                    $publicPhone = $sellerPhone ? preg_replace('/[^0-9+]/', '', (string) $sellerPhone) : '';
                    $canCall = (bool) $listing->allow_phone_call && $sellerPhone && $publicPhone;
                    $canSms = (bool) $listing->allow_sms && $sellerPhone && $publicPhone;
                @endphp
                <button class="btn block lg" type="button" data-contact-open aria-haspopup="dialog" aria-controls="seller-contact-modal">ارتباط با فروشنده</button>
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
            @endif    @endauth
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
