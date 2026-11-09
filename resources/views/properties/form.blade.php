@extends('layouts.marketplace')
@section('title', $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی')
@section('content')
@php
    $type = old('listing_type', $listing->listing_type ?: 'sale');
    $v = fn (string $key) => $listing->{$key};
    $invalid = fn (string $key) => $errors->has($key);
    $existingPhotos = old('existing_photos') !== null
        ? array_values(array_intersect((array) old('existing_photos'), $listing->photos ?? []))
        : array_values($listing->photos ?? []);
    $photoErrors = collect($errors->get('photos'))->merge(collect($errors->get('photos.*'))->flatten())->unique()->values();
    $typeLabels = ['sale' => 'فروش', 'rent' => 'اجاره', 'buy_request' => 'درخواست خرید', 'rent_request' => 'درخواست اجاره'];
@endphp
<section class="hero"><h1>{{ $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی ملکی' }}</h1><p>ثبت آگهی برای همه کاربران آزاد است؛ اتصال به واحد ساختمان اختیاری است.</p></section>

<form class="listing-form" method="POST" enctype="multipart/form-data" novalidate action="{{ $listing->exists ? route('properties.update',$listing) : route('properties.store') }}">
    @csrf @if($listing->exists)@method('PUT')@endif

    @if($errors->any())
        <div class="form-alert" role="alert" id="form-alert"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg><span>در {{ \App\Support\Fa::num($errors->count()) }} مورد نیاز به اصلاح هست؛ خطاها کنار هر فیلد نوشته شده‌اند.</span></div>
    @endif
    <div class="form-intro"><strong>نکته:</strong><span>ثبت آگهی مستقل از عضویت در ساختمان است. اگر مدیر یا مالک واحد هستید، می‌توانید آن را به واحد مربوط متصل کنید تا اطلاعات عمومی مجاز به‌صورت خودکار تکمیل شود. فیلدهای دارای <b class="req">*</b> اجباری‌اند.</span></div>

    {{-- ۱. اطلاعات اصلی --}}
    <section class="form-card" aria-labelledby="sec-main">
        <h2 id="sec-main"><span class="step">۱</span>اطلاعات اصلی</h2>
        <div class="fields">
            <label class="field @if($invalid('unit_id')) has-error @endif" for="property-unit"><span class="lbl"><span class="lbl-text">اتصال اختیاری به واحد</span></span>
                <select name="unit_id" id="property-unit"><option value="">آگهی مستقل (بدون اتصال به واحد)</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string) old('unit_id',$listing->unit_id)===(string) $unit->id)>{{ $unit->building?->name ?? 'ساختمان' }} · {{ $unit->block?->name ? $unit->block->name.' · ' : '' }}واحد {{ $unit->getAttribute('unit_number') ?? $unit->getAttribute('number') ?? $unit->id }}</option>@endforeach</select>
                @error('unit_id')<span class="field-error" role="alert">{{ $message }}</span>@enderror
            </label>
            <label class="field @if($invalid('listing_type')) has-error @endif" for="f-listing_type"><span class="lbl"><span class="lbl-text">نوع آگهی</span><b class="req" aria-hidden="true">*</b></span>
                <select name="listing_type" id="f-listing_type" required @class(['is-invalid' => $invalid('listing_type')])>@foreach($typeLabels as $k => $label)<option value="{{ $k }}" @selected($type===$k)>{{ $label }}</option>@endforeach</select>
                @error('listing_type')<span class="field-error" role="alert">{{ $message }}</span>@enderror
            </label>
            <label class="field @if($invalid('property_type')) has-error @endif" for="f-property_type"><span class="lbl"><span class="lbl-text">نوع ملک</span><b class="req" aria-hidden="true">*</b></span>
                <select name="property_type" id="f-property_type" required @class(['is-invalid' => $invalid('property_type')])>@foreach(['residential'=>'مسکونی','commercial'=>'تجاری','land'=>'زمین'] as $k=>$label)<option value="{{ $k }}" @selected(old('property_type',$listing->property_type)===$k)>{{ $label }}</option>@endforeach</select>
                @error('property_type')<span class="field-error" role="alert">{{ $message }}</span>@enderror
            </label>
            <div class="span-all">@include('properties.partials.input', ['name' => 'title', 'label' => 'عنوان آگهی', 'required' => true, 'maxlength' => 180, 'value' => $v('title'), 'placeholder' => 'مثلاً آپارتمان ۸۵ متری نوساز در ونک'])</div>
            @include('properties.partials.input', ['name' => 'province', 'label' => 'استان', 'value' => $v('province'), 'placeholder' => 'مثلاً تهران'])
            @include('properties.partials.input', ['name' => 'city', 'label' => 'شهر', 'required' => true, 'value' => $v('city'), 'placeholder' => 'مثلاً تهران'])
            @include('properties.partials.input', ['name' => 'neighborhood', 'label' => 'محله', 'value' => $v('neighborhood'), 'placeholder' => 'مثلاً ونک'])
            <div class="span-all">@include('properties.partials.input', ['name' => 'address', 'label' => 'آدرس تقریبی', 'value' => $v('address'), 'placeholder' => 'مثلاً خیابان ملاصدرا، نرسیده به پل ونک', 'hint' => 'در صورت فعال بودن گزینه عدم نمایش آدرس، این مقدار در صفحه عمومی نشان داده نمی‌شود.'])</div>
        </div>
        <label class="check"><input type="checkbox" name="hide_address" value="1" @checked(old('hide_address',$listing->exists ? $listing->hide_address : true))> آدرس دقیق در آگهی عمومی نمایش داده نشود</label>
    </section>

    {{-- ۲. مشخصات ملک --}}
    <section class="form-card" aria-labelledby="sec-specs">
        <h2 id="sec-specs"><span class="step">۲</span>مشخصات ملک</h2>
        <div class="fields">
            @include('properties.partials.input', ['name' => 'area', 'label' => 'متراژ', 'suffix' => 'متر', 'inputmode' => 'numeric', 'value' => $v('area') !== null ? (float) $v('area') : '', 'placeholder' => 'مثلاً ۸۵'])
            @include('properties.partials.input', ['name' => 'bedrooms', 'label' => 'تعداد اتاق', 'inputmode' => 'numeric', 'value' => $v('bedrooms'), 'placeholder' => 'مثلاً ۲'])
            @include('properties.partials.input', ['name' => 'bathrooms', 'label' => 'تعداد سرویس', 'inputmode' => 'numeric', 'value' => $v('bathrooms'), 'placeholder' => 'مثلاً ۱'])
            @include('properties.partials.input', ['name' => 'parking_count', 'label' => 'تعداد پارکینگ', 'inputmode' => 'numeric', 'value' => $v('parking_count'), 'placeholder' => 'مثلاً ۱'])
            @include('properties.partials.input', ['name' => 'floor', 'label' => 'طبقه', 'inputmode' => 'numeric', 'value' => $v('floor'), 'placeholder' => 'مثلاً ۳'])
            @include('properties.partials.input', ['name' => 'total_floors', 'label' => 'کل طبقات', 'inputmode' => 'numeric', 'value' => $v('total_floors'), 'placeholder' => 'مثلاً ۵'])
            @include('properties.partials.input', ['name' => 'built_year', 'label' => 'سال ساخت (شمسی)', 'inputmode' => 'numeric', 'value' => $v('built_year'), 'placeholder' => 'مثلاً ۱۴۰۲'])
            @include('properties.partials.input', ['name' => 'document_type', 'label' => 'نوع سند', 'value' => $v('document_type'), 'placeholder' => 'مثلاً تک‌برگ، قولنامه‌ای'])
        </div>
        <label class="check"><input type="checkbox" name="allow_exchange" value="1" @checked(old('allow_exchange',$listing->allow_exchange))> معاوضه پذیرفته می‌شود</label>
    </section>

    {{-- ۳. قیمت: فقط فیلدهای مرتبط با نوع آگهی --}}
    <section class="form-card" aria-labelledby="sec-price" id="price-card">
        <h2 id="sec-price"><span class="step">۳</span>قیمت</h2>
        <p class="form-help" id="price-hint" data-h-sale="قیمت کل ملک را به تومان وارد کنید." data-h-rent="حداقل یکی از ودیعه یا اجاره ماهانه را وارد کنید." data-h-buy_request="حداکثر بودجه‌ای که برای خرید دارید (اختیاری)." data-h-rent_request="حداکثر ودیعه و اجاره‌ای که می‌توانید بپردازید (اختیاری).">{{ ['sale' => 'قیمت کل ملک را به تومان وارد کنید.', 'rent' => 'حداقل یکی از ودیعه یا اجاره ماهانه را وارد کنید.', 'buy_request' => 'حداکثر بودجه‌ای که برای خرید دارید (اختیاری).', 'rent_request' => 'حداکثر ودیعه و اجاره‌ای که می‌توانید بپردازید (اختیاری).'][$type] }}</p>
        <div class="fields">
            <div data-for="sale buy_request" @if(! in_array($type, ['sale','buy_request'], true)) hidden @endif>
                @include('properties.partials.input', ['name' => 'price', 'label' => $type === 'buy_request' ? 'حداکثر بودجه خرید' : 'قیمت فروش', 'labels' => ['sale' => 'قیمت فروش', 'buy_request' => 'حداکثر بودجه خرید'], 'required' => $type === 'sale', 'money' => true, 'suffix' => 'تومان', 'value' => $v('price'), 'placeholder' => 'مثلاً ۵٬۰۰۰٬۰۰۰٬۰۰۰'])
            </div>
            <div data-for="rent rent_request" @if(! in_array($type, ['rent','rent_request'], true)) hidden @endif>
                @include('properties.partials.input', ['name' => 'deposit', 'label' => $type === 'rent_request' ? 'حداکثر ودیعه' : 'ودیعه (رهن)', 'labels' => ['rent' => 'ودیعه (رهن)', 'rent_request' => 'حداکثر ودیعه'], 'money' => true, 'suffix' => 'تومان', 'value' => $v('deposit'), 'placeholder' => 'مثلاً ۲۰۰٬۰۰۰٬۰۰۰'])
            </div>
            <div data-for="rent rent_request" @if(! in_array($type, ['rent','rent_request'], true)) hidden @endif>
                @include('properties.partials.input', ['name' => 'monthly_rent', 'label' => $type === 'rent_request' ? 'حداکثر اجاره ماهانه' : 'اجاره ماهانه', 'labels' => ['rent' => 'اجاره ماهانه', 'rent_request' => 'حداکثر اجاره ماهانه'], 'money' => true, 'suffix' => 'تومان', 'value' => $v('monthly_rent'), 'placeholder' => 'مثلاً ۸٬۰۰۰٬۰۰۰'])
            </div>
        </div>
    </section>

    {{-- ۴. توضیحات و تصاویر --}}
    <section class="form-card" aria-labelledby="sec-media">
        <h2 id="sec-media"><span class="step">۴</span>توضیحات و تصاویر</h2>
        <label class="field @if($invalid('description')) has-error @endif" for="f-description"><span class="lbl"><span class="lbl-text">توضیحات کامل</span><b class="req" aria-hidden="true">*</b><span class="sr-only">(اجباری)</span></span>
            <textarea id="f-description" name="description" required maxlength="10000" @class(['is-invalid' => $invalid('description')]) placeholder="ویژگی‌های مهم ملک، وضعیت سند، امکانات و شرایط معامله را دقیق بنویسید." @if($invalid('description')) aria-invalid="true" aria-describedby="f-description-err" @endif>{{ old('description',$listing->description) }}</textarea>
            @error('description')<span class="field-error" id="f-description-err" role="alert">{{ $message }}</span>@enderror
        </label>

        <div class="uploader" id="uploader" data-max="12">
            <div class="uploader-head"><span class="lbl-text">تصاویر ملک</span><span class="photo-counter" id="photo-counter" aria-live="polite">{{ \App\Support\Fa::num(count($existingPhotos)) }} از ۱۲</span></div>
            <input id="property-photos" class="sr-only" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
            <label class="dropzone" id="dropzone" for="property-photos">
                <svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>
                <strong>تصاویر را اینجا رها کنید</strong>
                <span class="muted">یا برای انتخاب از گالری/دستگاه بزنید</span>
                <span class="btn light dz-btn" aria-hidden="true">انتخاب تصویر</span>
                <small class="form-help">حداکثر ۱۲ تصویر، هرکدام تا ۵ مگابایت · فرمت‌های JPG، PNG و WebP</small>
            </label>
            <div id="photo-error" class="field-error" role="alert" aria-live="polite">@foreach($photoErrors as $m)<div>{{ $m }}</div>@endforeach</div>
            <input type="hidden" name="cover_photo" id="cover-photo" value="{{ old('cover_photo', '') }}">
            <ul class="photo-grid" id="photo-grid" aria-label="تصاویر انتخاب‌شده">
                @foreach($existingPhotos as $photo)
                    <li class="ph-item" data-kind="existing" data-path="{{ $photo }}">
                        <img src="{{ asset('storage/'.$photo) }}" alt="تصویر فعلی آگهی" loading="lazy">
                        <input type="hidden" name="existing_photos[]" value="{{ $photo }}">
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ۵. راه‌های ارتباطی --}}
    <section class="form-card" aria-labelledby="sec-contact">
        <h2 id="sec-contact"><span class="step">۵</span>راه‌های ارتباطی</h2>
        <p class="form-help">می‌توانید هر روش ارتباطی را جداگانه فعال یا غیرفعال کنید. شماره موبایل فقط برای روش‌های فعال نمایش داده می‌شود؛ گفتگوی درون‌برنامه‌ای همیشه فعال است.</p>
        <label class="check"><input type="checkbox" name="allow_phone_call" value="1" @checked(old('allow_phone_call', $listing->allow_phone_call ?? $listing->show_phone ?? false))> امکان تماس تلفنی فعال باشد</label>
        <label class="check"><input type="checkbox" name="allow_sms" value="1" @checked(old('allow_sms', $listing->allow_sms ?? $listing->show_phone ?? false))> امکان ارسال پیامک فعال باشد</label>
    </section>

    <div class="form-actions">
        <button class="btn lg" type="submit">{{ $listing->exists ? 'ذخیره تغییرات' : 'ثبت و انتشار آگهی' }}</button>
        <a class="btn light lg" href="{{ route('properties.mine') }}">انصراف</a>
    </div>
@php($mapApiKey = config('services.neshan.map_key'))
@if($mapApiKey)
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.css">
    @endpush
@endif
<section class="form-card property-location-card" aria-labelledby="property-location-title">
    <div class="property-map-heading">
        <div>
            <span class="property-map-eyebrow">موقعیت ملک</span>
            <h2 id="property-location-title">انتخاب موقعیت روی نقشه</h2>
            <p>روی محل ملک کلیک کن تا نشانگر ثبت شود؛ برای دقیق‌تر کردن نقطه می‌توانی نشانگر را بکشانی. موقعیت عمومی آگهی با کمی جابه‌جایی نمایش داده می‌شود.</p>
        </div>
        <span class="property-map-heading-icon" aria-hidden="true">⌖</span>
    </div>
@if($mapApiKey)
    <div class="property-map-shell">
        <div id="property-location-map" role="application" aria-label="نقشه انتخاب موقعیت ملک"></div>
        <div class="property-map-overlay">برای انتخاب، روی نقشه ضربه بزن</div>
    </div>
    <div class="property-map-footer">
        <div class="property-map-feedback"><span class="property-map-live-dot" aria-hidden="true"></span><span id="property-location-status" role="status" aria-live="polite">برای ثبت موقعیت، روی محل ملک در نقشه کلیک کن.</span></div>
        <button class="btn light property-map-clear" type="button" id="clear-property-location">حذف موقعیت</button>
    </div>
@else
    <div class="property-map-unavailable"><strong>نقشه هنوز فعال نشده</strong><span>برای فعال‌شدن انتخاب موقعیت، کلید نقشه باید در تنظیمات محیطی سرور ثبت شود.</span></div>
@endif
<input type="hidden" name="latitude" id="property-latitude" value="{{ old('latitude', $listing->latitude) }}">
<input type="hidden" name="longitude" id="property-longitude" value="{{ old('longitude', $listing->longitude) }}">
</section>

</form>

@push('scripts')
<script>
(function () {
    var FA = '۰۱۲۳۴۵۶۷۸۹', AR = '٠١٢٣٤٥٦٧٨٩';
    function toLatin(s) { return String(s).replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); }).replace(/[٠-٩]/g, function (d) { return AR.indexOf(d); }); }
    function toFa(s) { return String(s).replace(/\d/g, function (d) { return FA[d]; }); }

    /* ── ۱. فیلدهای مبلغ: جداکننده‌ی هزارگان + ارقام فارسی ── */
    function fmt(v) { var n = toLatin(v).replace(/\D/g, ''); return toFa(n.replace(/\B(?=(\d{3})+(?!\d))/g, '٬')); }
    document.querySelectorAll('input[data-money]').forEach(function (el) {
        if (el.value) el.value = fmt(el.value);
        el.addEventListener('input', function () {
            var pos = el.selectionStart == null ? el.value.length : el.selectionStart;
            var digitsBefore = toLatin(el.value.slice(0, pos)).replace(/\D/g, '').length;
            el.value = fmt(el.value);
            var i = 0, seen = 0;
            while (i < el.value.length && seen < digitsBefore) { if (/[۰-۹]/.test(el.value[i])) seen++; i++; }
            try { el.setSelectionRange(i, i); } catch (e) {}
        });
    });

    /* ── ۲. فقط فیلدهای مرتبط با نوع آگهی ── */
    var typeSel = document.getElementById('f-listing_type');
    var priceHint = document.getElementById('price-hint');
    function applyType() {
        var t = typeSel.value;
        document.querySelectorAll('#price-card [data-for]').forEach(function (g) {
            var show = g.getAttribute('data-for').split(' ').indexOf(t) !== -1;
            g.hidden = !show;
            g.querySelectorAll('input').forEach(function (i) { i.disabled = !show; });
            g.querySelectorAll('.lbl-text').forEach(function (l) { var x = l.getAttribute('data-l-' + t); if (x) l.textContent = x; });
        });
        var price = document.getElementById('f-price');
        if (price) price.required = (t === 'sale');
        if (priceHint) priceHint.textContent = priceHint.getAttribute('data-h-' + t) || '';
        var holder = document.querySelector('#price-card [data-for="sale buy_request"] .lbl');
        if (holder) {
            var star = holder.querySelector('.req');
            if (t === 'sale' && !star) { var b = document.createElement('b'); b.className = 'req'; b.setAttribute('aria-hidden', 'true'); b.textContent = '*'; holder.appendChild(b); }
            if (t !== 'sale' && star) star.remove();
        }
    }
    if (typeSel) { typeSel.addEventListener('change', applyType); applyType(); }

    /* ── ۳. پر شدن خودکار از واحد ── */
    var unitSel = document.getElementById('property-unit');
    var unitData = @json($unitAutofillData);
    if (unitSel) unitSel.addEventListener('change', function () {
        var d = unitData[this.value]; if (!d) return;
        Object.keys(d).forEach(function (name) {
            var f = document.querySelector('[name="' + name + '"]');
            if (f) f.value = d[name] == null ? '' : toFa(d[name]);
        });
    });

    /* ── ۴. اسکرول و فوکوس روی اولین خطا ── */
    var firstBad = document.querySelector('.is-invalid, .has-error input, .has-error select');
    if (firstBad) {
        firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
        try { firstBad.focus({ preventScroll: true }); } catch (e) {}
    } else {
        var phErr = document.querySelector('#photo-error div');
        if (phErr) phErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    var form = document.querySelector('.listing-form');
    if (form) form.addEventListener('submit', function (e) {
        var bad = null;
        form.querySelectorAll('input:not([disabled]):not([type=hidden]):not([type=file]), select, textarea').forEach(function (el) {
            if (el.required && !String(el.value).trim()) {
                el.classList.add('is-invalid');
                var box = el.closest('.field');
                if (box && !box.querySelector('.field-error')) {
                    var m = document.createElement('span'); m.className = 'field-error'; m.setAttribute('role', 'alert'); m.textContent = 'این فیلد را پر کنید.';
                    box.appendChild(m);
                }
                if (!bad) bad = el;
            }
        });
        if (bad) { e.preventDefault(); bad.scrollIntoView({ behavior: 'smooth', block: 'center' }); try { bad.focus({ preventScroll: true }); } catch (x) {} }
    });
    form && form.addEventListener('input', function (e) {
        if (e.target.classList && e.target.classList.contains('is-invalid') && String(e.target.value).trim()) {
            e.target.classList.remove('is-invalid');
            var box = e.target.closest('.field'); var m = box && box.querySelector('.field-error'); if (m) m.remove();
        }
    });

    /* ── ۵. آپلود تصاویر: کشیدن‌ورها‌کردن، پیش‌نمایش، حذف، عکس اصلی و شمارنده ── */
    var MAX = 12, MAXBYTES = 5 * 1024 * 1024, OK = ['image/jpeg', 'image/png', 'image/webp'];
    var input = document.getElementById('property-photos');
    var grid = document.getElementById('photo-grid');
    var drop = document.getElementById('dropzone');
    var counter = document.getElementById('photo-counter');
    var errBox = document.getElementById('photo-error');
    var coverField = document.getElementById('cover-photo');
    if (!input || !grid) return;
    var items = [];
    var coverItem = null;

    function setError(msgs) { errBox.replaceChildren(); msgs.forEach(function (m) { var d = document.createElement('div'); d.textContent = m; errBox.appendChild(d); }); }
    function mkBtn(cls, label, text) {
        var b = document.createElement('button'); b.type = 'button'; b.className = cls; b.setAttribute('aria-label', label); b.textContent = text; return b;
    }
    function decorate(li, item) {
        li.querySelectorAll('.ph-cover,.ph-del,.ph-badge').forEach(function (n) { n.remove(); });
        var del = mkBtn('ph-del', 'حذف تصویر', '×');
        del.addEventListener('click', function () { remove(item); });
        var cv = mkBtn('ph-cover', 'انتخاب به‌عنوان عکس اصلی', 'عکس اصلی');
        cv.addEventListener('click', function () { coverItem = item; render(); });
        var badge = document.createElement('span'); badge.className = 'ph-badge'; badge.textContent = 'عکس اصلی';
        li.append(del, cv, badge);
    }
    grid.querySelectorAll('li[data-kind="existing"]').forEach(function (li) {
        var item = { kind: 'existing', path: li.getAttribute('data-path'), el: li };
        items.push(item); decorate(li, item);
    });
    var savedCover = coverField.value;
    if (savedCover.indexOf('existing:') === 0) { coverItem = items.filter(function (i) { return 'existing:' + i.path === savedCover; })[0] || null; }

    function remove(item) {
        items = items.filter(function (i) { return i !== item; });
        if (item.url) URL.revokeObjectURL(item.url);
        item.el.remove();
        if (coverItem === item) coverItem = null;
        setError([]); render();
    }
    function render() {
        if (!coverItem || items.indexOf(coverItem) === -1) coverItem = items[0] || null;
        items.forEach(function (i) { i.el.classList.toggle('is-cover', i === coverItem); });
        counter.textContent = toFa(items.length) + ' از ۱۲';
        counter.classList.toggle('is-full', items.length >= MAX);
        // فایل‌های تازه به ترتیب (عکس اصلی اگر تازه است، اول) روی input قرار می‌گیرند
        var news = items.filter(function (i) { return i.kind === 'new'; });
        if (coverItem && coverItem.kind === 'new') news = [coverItem].concat(news.filter(function (i) { return i !== coverItem; }));
        try { var dt = new DataTransfer(); news.forEach(function (i) { dt.items.add(i.file); }); input.files = dt.files; } catch (e) {}
        coverField.value = !coverItem ? '' : (coverItem.kind === 'existing' ? 'existing:' + coverItem.path : 'new');
        // ترتیب نمایش با ترتیب items یکی باشد
        items.forEach(function (i) { grid.appendChild(i.el); });
    }
    function addFiles(list) {
        var msgs = [];
        Array.prototype.slice.call(list).forEach(function (file) {
            if (OK.indexOf(file.type) === -1) { msgs.push('«' + file.name + '» تصویر معتبر نیست (فقط JPG، PNG و WebP).'); return; }
            if (file.size > MAXBYTES) { msgs.push('«' + file.name + '» بزرگ‌تر از ۵ مگابایت است.'); return; }
            if (items.some(function (i) { return i.file && i.file.name === file.name && i.file.size === file.size && i.file.lastModified === file.lastModified; })) return;
            if (items.length >= MAX) { if (msgs.indexOf('حداکثر ۱۲ تصویر می‌توانید انتخاب کنید.') === -1) msgs.push('حداکثر ۱۲ تصویر می‌توانید انتخاب کنید.'); return; }
            var li = document.createElement('li'); li.className = 'ph-item'; li.setAttribute('data-kind', 'new');
            var img = document.createElement('img'); var url = URL.createObjectURL(file); img.src = url; img.alt = file.name; li.appendChild(img);
            var item = { kind: 'new', file: file, el: li, url: url }; items.push(item); decorate(li, item); grid.appendChild(li);
        });
        setError(msgs); render();
    }
    // تغییر input فقط با انتخاب کاربر رخ می‌دهد (تنظیم برنامه‌نویسی files رویداد change نمی‌سازد)
    input.addEventListener('change', function () { addFiles(this.files); });
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
    drop.addEventListener('drop', function (e) { e.preventDefault(); if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files); });
    render();
})();
</script>
@if($mapApiKey)
<script src="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.umd.js"></script>
<script>
(function () {
    const initPropertyLocationMap = () => {
        const mapElement = document.getElementById('property-location-map');
        if (!mapElement) return;

        const status = document.getElementById('property-location-status');
        const latInput = document.getElementById('property-latitude');
        const lngInput = document.getElementById('property-longitude');
        const clearButton = document.getElementById('clear-property-location');
        if (!window.maplibregl) {
            if (status) status.textContent = 'کتابخانه نقشه بارگذاری نشد؛ اتصال اینترنت و دسترسی به سرویس نقشه را بررسی کن.';
            return;
        }

        const hasPoint = Boolean(latInput.value && lngInput.value);
        const initial = hasPoint ? [Number(lngInput.value), Number(latInput.value)] : [51.389, 35.6892];
        let map;
        try {
            map = new maplibregl.Map({
                container: mapElement,
                style: 'https://static.neshan.org/sdk/maplibre/styles/light.json',
                center: initial,
                zoom: hasPoint ? 16 : 11,
                apiKey: @json($mapApiKey),
                attributionControl: true
            });
        } catch (error) {
            console.error('Property location map initialization failed:', error);
            if (status) status.textContent = 'راه‌اندازی نقشه ناموفق بود؛ لطفاً صفحه را تازه‌سازی کن و اتصال نقشه را بررسی کن.';
            return;
        }
        let marker = null;
        let lastMapClickAt = 0;
        let pointerStart = null;
        let lastPointerSelectionAt = 0;

        const syncPoint = (point) => {
            latInput.value = Number(point.lat).toFixed(7);
            lngInput.value = Number(point.lng).toFixed(7);
            if (status) status.textContent = 'موقعیت ثبت شد. برای اصلاح، نشانگر را روی نقشه جابه‌جا کن.';
        };
        const setPoint = (point) => {
            if (!marker) {
                marker = new maplibregl.Marker({draggable: true, color: '#16816f', anchor: 'bottom'})
                    .setLngLat([point.lng, point.lat]).addTo(map);
                marker.on('dragend', () => syncPoint(marker.getLngLat()));
            } else {
                marker.setLngLat([point.lng, point.lat]);
            }
            syncPoint(point);
        };

        map.on('load', () => {
            map.resize();
            if (hasPoint) setPoint({lat: Number(latInput.value), lng: Number(lngInput.value)});
        });
        map.on('click', (event) => {
            lastMapClickAt = Date.now();
            if (Date.now() - lastPointerSelectionAt < 350) return;
            setPoint(event.lngLat);
        });

        // Observe pointer events at document capture level so SDK handlers,
        // pointer capture, and nested canvas elements cannot hide a tap.
        const onPointerDown = (event) => {
            if (!mapElement.contains(event.target) || (event.button !== undefined && event.button !== 0)) return;
            if (event.target.closest && event.target.closest('.maplibregl-marker')) {
                pointerStart = null;
                return;
            }
            pointerStart = {x: event.clientX, y: event.clientY, pointerId: event.pointerId};
            if (status) status.textContent = 'لمس نقشه دریافت شد؛ در حال ثبت موقعیت…';
        };
        const onPointerUp = (event) => {
            if (!pointerStart || pointerStart.pointerId !== event.pointerId) return;
            const start = pointerStart;
            pointerStart = null;
            const moved = Math.hypot(event.clientX - start.x, event.clientY - start.y);
            if (moved > 8 || !mapElement.contains(event.target)) return;
            try {
                const bounds = mapElement.getBoundingClientRect();
                const point = map.unproject([event.clientX - bounds.left, event.clientY - bounds.top]);
                lastPointerSelectionAt = Date.now();
                setPoint(point);
            } catch (error) {
                console.error('Property map point selection failed:', error);
                if (status) status.textContent = 'نقطه دریافت شد اما ثبت آن ناموفق بود؛ کنسول مرورگر را بررسی کن.';
            }
        };
        document.addEventListener('pointerdown', onPointerDown, true);
        document.addEventListener('pointerup', onPointerUp, true);
        document.addEventListener('pointercancel', () => { pointerStart = null; }, true);

        // Canvas click is an additional fallback for SDK/browser combinations
        // that dispatch click but do not expose a usable pointer sequence.
        mapElement.addEventListener('click', (event) => {
            if (Date.now() - lastMapClickAt < 100 || Date.now() - lastPointerSelectionAt < 350) return;
            if (event.target && event.target.closest && event.target.closest('.maplibregl-marker')) return;
            try {
                const bounds = mapElement.getBoundingClientRect();
                const point = map.unproject([event.clientX - bounds.left, event.clientY - bounds.top]);
                setPoint(point);
            } catch (error) {
                console.error('Property map click selection failed:', error);
                if (status) status.textContent = 'کلیک دریافت شد اما مختصات قابل محاسبه نیست.';
            }
        }, true);

        map.on('error', (event) => {
            console.error('Property location map error:', event && event.error ? event.error : event);
            if (status) status.textContent = 'بارگذاری نقشه با خطا مواجه شد؛ کلید نقشه و دسترسی سرویس را بررسی کن.';
        });
        if (clearButton) clearButton.addEventListener('click', () => {
            if (marker) marker.remove();
            marker = null;
            latInput.value = '';
            lngInput.value = '';
            if (status) status.textContent = 'موقعیت انتخاب‌شده پاک شد؛ برای ثبت دوباره روی نقشه کلیک کن.';
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPropertyLocationMap, {once: true});
    } else {
        initPropertyLocationMap();
    }
})();
</script>
@endif

@endpush
@endsection