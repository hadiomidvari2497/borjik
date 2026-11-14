@extends('layouts.marketplace')
@section('title', $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی')
@push('head')<link rel="stylesheet" href="{{ asset('css/listing-form.css') }}?v={{ @filemtime(public_path('css/listing-form.css')) }}">@endpush
@push('scripts')<script>window.LISTING_UNITS = @json($unitAutofillData);</script><script src="{{ asset('js/listing-form.js') }}?v={{ @filemtime(public_path('js/listing-form.js')) }}" defer></script>@endpush
@section('content')
@php
    $type = old('listing_type', $listing->listing_type ?: 'sale');
    $existing = collect($listing->photos ?? [])->values();
    $keep = collect(old('existing_photos', $existing->all()));
@endphp
<section class="hero"><h1>{{ $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی ملکی' }}</h1><p>{{ $listing->exists ? 'اطلاعات آگهی را به‌روز کنید.' : 'در چند مرحله‌ی کوتاه آگهی خود را منتشر کنید؛ ثبت آگهی برای همه آزاد است.' }}</p></section>

<form class="lform" id="listing-form" method="POST" enctype="multipart/form-data" novalidate action="{{ $listing->exists ? route('properties.update',$listing) : route('properties.store') }}">
@csrf @if($listing->exists)@method('PUT')@endif

@if($errors->any())
<div class="lform-errors" id="form-errors" role="alert" tabindex="-1">
    <b>لطفاً موارد زیر را اصلاح کنید:</b>
    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<section class="lsec">
    <header><span class="n">۱</span><div><h2>نوع آگهی</h2><p>مشخص کنید چه می‌خواهید ثبت کنید.</p></div></header>
    <div class="seg-pick" role="radiogroup" aria-label="نوع آگهی">
        @foreach(['sale'=>'فروش','rent'=>'اجاره','buy_request'=>'درخواست خرید','rent_request'=>'درخواست اجاره'] as $k=>$v)
            <label><input type="radio" name="listing_type" value="{{ $k }}" @checked($type===$k)><span>{{ $v }}</span></label>
        @endforeach
    </div>
    <div class="grid2">
        <div class="fld"><label for="f-property_type">نوع ملک<i class="req">*</i></label>
            <div class="ctl"><select id="f-property_type" name="property_type" required>@foreach(['residential'=>'مسکونی','commercial'=>'تجاری','land'=>'زمین'] as $k=>$v)<option value="{{ $k }}" @selected(old('property_type',$listing->property_type ?: 'residential')===$k)>{{ $v }}</option>@endforeach</select></div>
        </div>
        @if($units->isNotEmpty())
        <div class="fld"><label for="property-unit">اتصال به واحد ساختمان <em class="opt">(اختیاری)</em></label>
            <div class="ctl"><select name="unit_id" id="property-unit"><option value="">آگهی مستقل (بدون اتصال)</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string) old('unit_id',$listing->unit_id)===(string) $unit->id)>{{ $unit->building?->name ?? 'ساختمان' }} · {{ $unit->block?->name ? $unit->block->name.' · ' : '' }}واحد {{ $unit->getAttribute('unit_number') ?? $unit->getAttribute('number') ?? $unit->id }}</option>@endforeach</select></div>
            <small class="help">اگر مدیر یا مالک واحد هستید، با اتصال آگهی اطلاعات عمومی مجاز خودکار پر می‌شود.</small>
        </div>
        @else
        <div class="fld"><label>نوع ثبت</label><p class="help" style="margin:0;font-size:13px">آگهی مستقل (بدون اتصال به واحد)</p></div>
        @endif
    </div>
</section>

<section class="lsec">
    <header><span class="n">۲</span><div><h2>موقعیت و عنوان</h2><p>عنوان کوتاه و دقیق، تعداد بازدید را بیشتر می‌کند.</p></div></header>
    <div class="grid2">
        <div class="span2">@include('properties.partials.field', ['name'=>'title','label'=>'عنوان آگهی','required'=>true,'maxlength'=>180,'placeholder'=>'مثلاً: آپارتمان ۹۵ متری نوساز در ونک','listing'=>$listing])</div>
        @include('properties.partials.field', ['name'=>'province','label'=>'استان','placeholder'=>'مثلاً: تهران','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'city','label'=>'شهر','required'=>true,'placeholder'=>'مثلاً: تهران','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'neighborhood','label'=>'محله','placeholder'=>'مثلاً: ونک','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'address','label'=>'آدرس تقریبی','placeholder'=>'خیابان، کوچه، پلاک (اختیاری)','help'=>'اگر گزینه‌ی «عدم نمایش آدرس» روشن باشد، عموم آن را نمی‌بینند.','listing'=>$listing])
    </div>
</section>

<section class="lsec">
    <header><span class="n">۳</span><div><h2>مشخصات ملک</h2><p>فقط چیزهایی را که می‌دانید پر کنید.</p></div></header>
    <div class="grid3">
        @include('properties.partials.field', ['name'=>'area','label'=>'متراژ','type'=>'number','suffix'=>'متر','placeholder'=>'۹۵','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'bedrooms','label'=>'اتاق خواب','type'=>'number','placeholder'=>'۲','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'bathrooms','label'=>'سرویس بهداشتی','type'=>'number','placeholder'=>'۱','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'parking_count','label'=>'پارکینگ','type'=>'number','placeholder'=>'۱','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'floor','label'=>'طبقه','type'=>'number','placeholder'=>'۳','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'total_floors','label'=>'کل طبقات','type'=>'number','placeholder'=>'۵','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'built_year','label'=>'سال ساخت','type'=>'number','placeholder'=>'۱۴۰۲','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'document_type','label'=>'نوع سند','placeholder'=>'مثلاً: تک‌برگ','listing'=>$listing])
    </div>
</section>

<section class="lsec">
    <header><span class="n">۴</span><div><h2>قیمت</h2><p>مبلغ‌ها را به تومان وارد کنید؛ جداکننده‌ی هزارگان خودکار گذاشته می‌شود.</p></div></header>
    <div class="grid2">
        @include('properties.partials.field', ['name'=>'price','label'=>'قیمت / مبلغ مورد نظر','money'=>true,'suffix'=>'تومان','show'=>'sale buy_request','placeholder'=>'۵,۰۰۰,۰۰۰,۰۰۰','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'deposit','label'=>'ودیعه (رهن)','money'=>true,'suffix'=>'تومان','show'=>'rent rent_request','placeholder'=>'۲۰۰,۰۰۰,۰۰۰','listing'=>$listing])
        @include('properties.partials.field', ['name'=>'monthly_rent','label'=>'اجاره ماهانه','money'=>true,'suffix'=>'تومان','show'=>'rent rent_request','placeholder'=>'۱۵,۰۰۰,۰۰۰','listing'=>$listing])
    </div>
</section>

<section class="lsec">
    <header><span class="n">۵</span><div><h2>توضیحات و تصاویر</h2><p>آگهی با عکس باکیفیت چند برابر بیشتر دیده می‌شود.</p></div></header>
    <div class="fld @error('description') has-error @enderror">
        <label for="f-description">توضیحات کامل<i class="req">*</i></label>
        <div class="ctl"><textarea id="f-description" name="description" required maxlength="10000" placeholder="ویژگی‌های مهم ملک، امکانات، شرایط معامله و…">{{ old('description',$listing->description) }}</textarea></div>
        <small class="help"><span id="desc-count">۰</span> / ۱۰٬۰۰۰ نویسه</small>
        @error('description')<small class="ferr" role="alert">{{ $message }}</small>@enderror
    </div>

    <div class="photos" id="photos" data-max="12">
        <div class="photos-head"><b>تصاویر ملک</b><span id="photo-count" aria-live="polite">۰ از ۱۲</span></div>
        <input id="property-photos" class="sr-only" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple tabindex="-1">
        <input type="hidden" name="main_photo" id="main-photo" value="">
        <button type="button" class="drop" id="photo-drop"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg><b>انتخاب تصویر یا رها کردن اینجا</b><small>JPG، PNG یا WebP · هر تصویر تا ۵ مگابایت · حداکثر ۱۲ تصویر</small></button>
        <div id="photo-error" class="ferr" role="alert"></div>
        <div id="photo-grid" class="pgrid" aria-live="polite">
            @foreach($existing as $photo)
                <div class="ph" data-existing="{{ $photo }}" @if(! $keep->contains($photo)) hidden @endif>
                    <img src="{{ asset('storage/'.$photo) }}" alt="تصویر فعلی">
                    <input type="hidden" name="existing_photos[]" value="{{ $photo }}" @if(! $keep->contains($photo)) disabled @endif>
                    <button type="button" class="ph-main" data-act="main" aria-label="انتخاب به‌عنوان تصویر اصلی" title="تصویر اصلی">★</button>
                    <button type="button" class="ph-del" data-act="del" aria-label="حذف تصویر"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button>
                </div>
            @endforeach
        </div>
        <small class="help">تصویر اول (برچسب «اصلی») روی کارت آگهی نمایش داده می‌شود؛ با ★ می‌توانید تصویر اصلی را عوض کنید.</small>
    </div>
</section>

<section class="lsec">
    <header><span class="n">۶</span><div><h2>راه‌های ارتباط و حریم خصوصی</h2><p>شماره‌ی موبایل فقط برای روش‌هایی نمایش داده می‌شود که فعال کنید.</p></div></header>
    <div class="checks">
        <label><input type="checkbox" name="allow_phone_call" value="1" @checked(old('allow_phone_call', $listing->allow_phone_call ?? $listing->show_phone ?? false))><span>امکان تماس تلفنی فعال باشد</span></label>
        <label><input type="checkbox" name="allow_sms" value="1" @checked(old('allow_sms', $listing->allow_sms ?? $listing->show_phone ?? false))><span>امکان ارسال پیامک فعال باشد</span></label>
        <label><input type="checkbox" name="hide_address" value="1" @checked(old('hide_address',$listing->exists ? $listing->hide_address : true))><span>آدرس دقیق در آگهی عمومی نمایش داده نشود</span></label>
        <label><input type="checkbox" name="allow_exchange" value="1" @checked(old('allow_exchange',$listing->allow_exchange))><span>معاوضه پذیرفته می‌شود</span></label>
    </div>
</section>

<div class="lbar">
    <a class="btn light" href="{{ route('properties.mine') }}">انصراف</a>
    <button class="btn lg" type="submit" id="submit-btn">{{ $listing->exists ? 'ذخیره تغییرات' : 'ثبت و انتشار آگهی' }}</button>
</div>
</form>
@endsection
