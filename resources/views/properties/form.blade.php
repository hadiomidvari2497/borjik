@extends('layouts.marketplace')
@section('title', $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی')
@section('content')
@php($mapApiKey = config('services.neshan.map_key'))
@if($mapApiKey)
    @push('head')<link rel="stylesheet" href="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.css">@endpush
@endif
<style>
.form-section{margin:0 0 22px;padding:18px;border:1px solid #e7efec;border-radius:15px;background:#fbfdfc}
.form-section h2{font-size:15px;margin:0 0 14px;color:#1d5e53}.form-help{font-size:11px;color:#7b8f88;line-height:1.9;margin:5px 0 0}
.photo-preview{display:grid;grid-template-columns:repeat(auto-fill,minmax(105px,1fr));gap:9px;margin-top:12px}
.photo-preview img{width:100%;height:92px;object-fit:cover;border-radius:10px;border:1px solid #e1eae7}
.photo-existing{display:flex;align-items:center;gap:10px;padding:8px;border:1px solid #e6eeea;border-radius:10px;margin:7px 0;font-size:12px}
.photo-existing img{width:64px;height:54px;object-fit:cover;border-radius:7px}
.form-intro{display:flex;gap:10px;align-items:flex-start;background:#edf6f2;border-radius:12px;padding:12px;color:#35665b;font-size:12px;line-height:1.9;margin-bottom:18px}
@media(max-width:600px){.form-section{padding:13px}}
</style>
<section class="hero"><h1>{{ $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی ملکی' }}</h1><p>ثبت آگهی برای همه کاربران آزاد است؛ اتصال به واحد ساختمان اختیاری است.</p></section>
<form class="panel" method="POST" enctype="multipart/form-data" action="{{ $listing->exists ? route('properties.update',$listing) : route('properties.store') }}">@csrf @if($listing->exists)@method('PUT')@endif
@if($errors->any())<div class="error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="form-intro"><strong>نکته:</strong><span>ثبت آگهی مستقل از عضویت در ساختمان است. اگر مدیر یا مالک واحد هستید، می‌توانید آن را به واحد مربوط متصل کنید تا اطلاعات عمومی مجاز به‌صورت خودکار تکمیل شود.</span></div>
<section class="form-section"><h2>اطلاعات اصلی آگهی</h2><div class="fields">
<label class="field">اتصال اختیاری به واحد<select name="unit_id" id="property-unit"><option value="">آگهی مستقل (بدون اتصال به واحد)</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string) old('unit_id',$listing->unit_id)===(string) $unit->id)>{{ $unit->building?->name ?? 'ساختمان' }} · {{ $unit->block?->name ? $unit->block->name.' · ' : '' }}واحد {{ $unit->getAttribute('unit_number') ?? $unit->getAttribute('number') ?? $unit->id }}</option>@endforeach</select></label>
<label class="field">نوع آگهی<select name="listing_type" required>@foreach(['sale'=>'فروش','rent'=>'اجاره','buy_request'=>'درخواست خرید','rent_request'=>'درخواست اجاره'] as $k=>$v)<option value="{{ $k }}" @selected(old('listing_type',$listing->listing_type)===$k)>{{ $v }}</option>@endforeach</select></label>
<label class="field">نوع ملک<select name="property_type" required>@foreach(['residential'=>'مسکونی','commercial'=>'تجاری','land'=>'زمین'] as $k=>$v)<option value="{{ $k }}" @selected(old('property_type',$listing->property_type)===$k)>{{ $v }}</option>@endforeach</select></label>
<label class="field">عنوان<input name="title" value="{{ old('title',$listing->title) }}" required maxlength="180"></label><label class="field">استان<input name="province" value="{{ old('province',$listing->province) }}"></label><label class="field">شهر<input name="city" value="{{ old('city',$listing->city) }}" required></label><label class="field">محله<input name="neighborhood" value="{{ old('neighborhood',$listing->neighborhood) }}"></label>
<label class="field">متراژ<input type="number" name="area" min="1" value="{{ old('area',$listing->area) }}"></label><label class="field">تعداد اتاق<input type="number" name="bedrooms" min="0" value="{{ old('bedrooms',$listing->bedrooms) }}"></label><label class="field">تعداد سرویس<input type="number" name="bathrooms" min="0" value="{{ old('bathrooms',$listing->bathrooms) }}"></label><label class="field">تعداد پارکینگ<input type="number" name="parking_count" min="0" value="{{ old('parking_count',$listing->parking_count) }}"></label><label class="field">طبقه<input type="number" name="floor" value="{{ old('floor',$listing->floor) }}"></label><label class="field">کل طبقات<input type="number" name="total_floors" min="1" value="{{ old('total_floors',$listing->total_floors) }}"></label><label class="field">سال ساخت شمسی<input type="number" name="built_year" min="1300" max="1500" value="{{ old('built_year',$listing->built_year) }}"></label>
<label class="field">قیمت فروش / مبلغ درخواستی<input type="number" name="price" min="0" value="{{ old('price',$listing->price) }}"></label><label class="field">ودیعه<input type="number" name="deposit" min="0" value="{{ old('deposit',$listing->deposit) }}"></label><label class="field">اجاره ماهانه<input type="number" name="monthly_rent" min="0" value="{{ old('monthly_rent',$listing->monthly_rent) }}"></label>
<label class="field">نوع سند<input name="document_type" value="{{ old('document_type',$listing->document_type) }}"></label><label class="field">آدرس تقریبی<input name="address" value="{{ old('address',$listing->address) }}"><span class="form-help">در صورت فعال بودن گزینه عدم نمایش آدرس، این مقدار در صفحه عمومی نشان داده نمی‌شود.</span></label>
</div></section>
<section class="form-section"><h2>موقعیت ملک روی نقشه</h2>
<p class="form-help">نقطه دقیق ملک را انتخاب کنید. در نقشه عمومی، موقعیت با کمی جابه‌جایی نمایش داده می‌شود تا نشانی دقیق ملک محفوظ بماند.</p>
@if($mapApiKey)
    <div id="property-location-map" style="height:360px;border-radius:14px;overflow:hidden;background:#edf3f1"></div>
    <div class="actions"><button class="btn light" type="button" id="clear-property-location">حذف موقعیت انتخاب‌شده</button><span class="form-help" id="property-location-status" role="status">برای انتخاب موقعیت، روی نقشه کلیک کنید یا نشانگر را جابه‌جا کنید.</span></div>
    <input type="hidden" name="latitude" id="property-latitude" value="{{ old('latitude', $listing->latitude) }}">
    <input type="hidden" name="longitude" id="property-longitude" value="{{ old('longitude', $listing->longitude) }}">
@else
    <div class="notice">برای فعال‌شدن انتخاب موقعیت، کلید نقشه باید در تنظیمات محیطی سرور ثبت شود.</div>
@endif
</section>
<section class="form-section"><h2>توضیحات و تصاویر</h2><div class="fields">
<label class="field">توضیحات کامل<textarea name="description" required maxlength="10000">{{ old('description',$listing->description) }}</textarea><span class="form-help">ویژگی‌های مهم ملک و شرایط معامله را دقیق بنویسید.</span></label>
<label class="field">افزودن تصاویر (حداکثر ۱۲ عکس، هرکدام تا ۵ مگابایت)<input id="property-photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple><span class="form-help">فرمت‌های مجاز: JPG، PNG و WebP</span></label>
</div><div id="photo-error" class="error" role="status" aria-live="polite"></div><div id="photo-preview" class="photo-preview" aria-live="polite"></div>
@if($listing->photos)
    <h3 style="font-size:13px;margin:18px 0 8px">تصاویر فعلی؛ برای نگه‌داشتن تیک را روشن بگذارید</h3>
    @foreach($listing->photos as $photo)
        <label class="photo-existing"><img src="{{ asset('storage/'.$photo) }}" alt="تصویر فعلی"><input type="checkbox" name="existing_photos[]" value="{{ $photo }}" checked> حفظ این تصویر</label>
    @endforeach
@endif
</section>
<div class="form-section"><h2>راه‌های ارتباط با من</h2><p class="form-help">می‌توانید هر روش ارتباطی را جداگانه فعال یا غیرفعال کنید. شماره موبایل فقط برای روش‌های فعال نمایش داده می‌شود.</p><div class="actions"><label><input type="checkbox" name="allow_phone_call" value="1" @checked(old('allow_phone_call', $listing->allow_phone_call ?? $listing->show_phone ?? false))> امکان تماس تلفنی فعال باشد</label><label><input type="checkbox" name="allow_sms" value="1" @checked(old('allow_sms', $listing->allow_sms ?? $listing->show_phone ?? false))> امکان ارسال پیامک فعال باشد</label></div></div>
<div class="actions"><label><input type="checkbox" name="hide_address" value="1" @checked(old('hide_address',$listing->exists ? $listing->hide_address : true))> آدرس دقیق در آگهی عمومی نمایش داده نشود</label><label><input type="checkbox" name="allow_exchange" value="1" @checked(old('allow_exchange',$listing->allow_exchange))> معاوضه پذیرفته می‌شود</label></div>
<div class="actions"><button class="btn">ثبت و انتشار آگهی</button><a class="btn light" href="{{ route('properties.mine') }}">انصراف</a></div>
</form>
@if($mapApiKey)
<script src="https://unpkg.com/@neshan-maps-platform/maplibre-sdk@6.16.0/dist/neshan-maplibre-sdk.umd.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapElement = document.getElementById('property-location-map');
    if (mapElement && window.maplibregl) {
        const key = @json($mapApiKey);
        const latInput = document.getElementById('property-latitude');
        const lngInput = document.getElementById('property-longitude');
        const status = document.getElementById('property-location-status');
        const initial = latInput.value && lngInput.value
            ? [Number(lngInput.value), Number(latInput.value)] : [51.389, 35.6892];
        const map = new maplibregl.Map({container: mapElement, style: 'https://static.neshan.org/sdk/maplibre/styles/light.json', center: initial, zoom: latInput.value ? 15 : 11, apiKey: key});
        let marker = null;
        const setPoint = (lngLat) => {
            if (!marker) marker = new maplibregl.Marker({draggable: true, color: '#16816f'}).setLngLat(lngLat).addTo(map);
            else marker.setLngLat(lngLat);
            latInput.value = Number(lngLat.lat).toFixed(7);
            lngInput.value = Number(lngLat.lng).toFixed(7);
            if (status) status.textContent = 'موقعیت انتخاب شد؛ نقطه دقیق فقط در اطلاعات داخلی آگهی ذخیره می‌شود.';
            marker.off('dragend');
            marker.on('dragend', () => setPoint(marker.getLngLat()));
        };
        map.on('click', event => setPoint(event.lngLat));
        if (latInput.value && lngInput.value) map.on('load', () => setPoint({lat: Number(latInput.value), lng: Number(lngInput.value)}));
        document.getElementById('clear-property-location')?.addEventListener('click', () => {
            marker?.remove(); marker = null; latInput.value = ''; lngInput.value = '';
            if (status) status.textContent = 'موقعیت انتخاب‌شده حذف شد.';
        });
        map.on('error', () => { if (status) status.textContent = 'بارگذاری نقشه با خطا مواجه شد؛ اتصال و اعتبار کلید نقشه را بررسی کنید.'; });
    }
</script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    const photoInput = document.getElementById('property-photos');
    const preview = document.getElementById('photo-preview');
    if (photoInput && preview) {
        photoInput.addEventListener('change', function () {
            preview.replaceChildren();
            const error = document.getElementById('photo-error');
            if (error) error.textContent = '';
            const files = Array.from(this.files || []);
            if (files.length > 12) {
                if (error) error.textContent = 'حداکثر ۱۲ تصویر می‌توانید انتخاب کنید.';
                this.value = '';
                return;
            }
            files.forEach(function (file) {
                if (!file.type.startsWith('image/')) return;
                const image = document.createElement('img');
                image.alt = file.name;
                image.src = URL.createObjectURL(file);
                image.onload = function () { URL.revokeObjectURL(image.src); };
                preview.appendChild(image);
            });
        });
    }
    const selector = document.getElementById('property-unit');
    const data = @json($unitAutofillData);
    if (!selector) return;
    selector.addEventListener('change', function () {
        const details = data[this.value];
        if (!details) return;
        Object.entries(details).forEach(([name, value]) => {
            const field = document.querySelector('[name="' + name + '"]');
            if (field) field.value = value ?? '';
        });
    });
});
</script>
@endsection
