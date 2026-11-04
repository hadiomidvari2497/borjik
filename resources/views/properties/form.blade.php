@extends('layouts.marketplace')
@section('title', $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی')
@section('content')
<section class="hero"><h1>{{ $listing->exists ? 'ویرایش آگهی' : 'ثبت آگهی ملکی' }}</h1><p>ثبت آگهی برای همه کاربران آزاد است؛ اتصال به واحد ساختمان اختیاری است.</p></section>
<form class="panel" method="POST" enctype="multipart/form-data" action="{{ $listing->exists ? route('properties.update',$listing) : route('properties.store') }}">@csrf @if($listing->exists)@method('PUT')@endif
@if($errors->any())<div class="error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="fields">
<label class="field">اتصال اختیاری به واحد<select name="unit_id" id="property-unit"><option value="">آگهی مستقل (بدون اتصال به واحد)</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string) old('unit_id',$listing->unit_id)===(string) $unit->id)>{{ $unit->building?->name ?? 'ساختمان' }} · {{ $unit->block?->name ? $unit->block->name.' · ' : '' }}واحد {{ $unit->getAttribute('unit_number') ?? $unit->getAttribute('number') ?? $unit->id }}</option>@endforeach</select></label>
<label class="field">نوع آگهی<select name="listing_type" required>@foreach(['sale'=>'فروش','rent'=>'اجاره','buy_request'=>'درخواست خرید','rent_request'=>'درخواست اجاره'] as $k=>$v)<option value="{{ $k }}" @selected(old('listing_type',$listing->listing_type)===$k)>{{ $v }}</option>@endforeach</select></label>
<label class="field">نوع ملک<select name="property_type" required>@foreach(['residential'=>'مسکونی','commercial'=>'تجاری','land'=>'زمین'] as $k=>$v)<option value="{{ $k }}" @selected(old('property_type',$listing->property_type)===$k)>{{ $v }}</option>@endforeach</select></label>
<label class="field">عنوان<input name="title" value="{{ old('title',$listing->title) }}" required maxlength="180"></label><label class="field">استان<input name="province" value="{{ old('province',$listing->province) }}"></label><label class="field">شهر<input name="city" value="{{ old('city',$listing->city) }}" required></label><label class="field">محله<input name="neighborhood" value="{{ old('neighborhood',$listing->neighborhood) }}"></label>
<label class="field">متراژ<input type="number" name="area" min="1" value="{{ old('area',$listing->area) }}"></label><label class="field">تعداد اتاق<input type="number" name="bedrooms" min="0" value="{{ old('bedrooms',$listing->bedrooms) }}"></label><label class="field">تعداد سرویس<input type="number" name="bathrooms" min="0" value="{{ old('bathrooms',$listing->bathrooms) }}"></label><label class="field">تعداد پارکینگ<input type="number" name="parking_count" min="0" value="{{ old('parking_count',$listing->parking_count) }}"></label><label class="field">طبقه<input type="number" name="floor" value="{{ old('floor',$listing->floor) }}"></label><label class="field">کل طبقات<input type="number" name="total_floors" min="1" value="{{ old('total_floors',$listing->total_floors) }}"></label><label class="field">سال ساخت شمسی<input type="number" name="built_year" min="1300" max="1500" value="{{ old('built_year',$listing->built_year) }}"></label>
<label class="field">قیمت فروش / مبلغ درخواستی<input type="number" name="price" min="0" value="{{ old('price',$listing->price) }}"></label><label class="field">ودیعه<input type="number" name="deposit" min="0" value="{{ old('deposit',$listing->deposit) }}"></label><label class="field">اجاره ماهانه<input type="number" name="monthly_rent" min="0" value="{{ old('monthly_rent',$listing->monthly_rent) }}"></label>
<label class="field">نوع سند<input name="document_type" value="{{ old('document_type',$listing->document_type) }}"></label><label class="field">آدرس تقریبی<input name="address" value="{{ old('address',$listing->address) }}"></label>
<label class="field">توضیحات<textarea name="description" required>{{ old('description',$listing->description) }}</textarea></label><label class="field">تصاویر (حداکثر ۱۲ عکس)<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
</div>
@if($listing->photos)@foreach($listing->photos as $photo)<label><input type="checkbox" name="existing_photos[]" value="{{ $photo }}" checked> حفظ تصویر موجود</label>@endforeach @endif
<div class="actions"><label><input type="checkbox" name="hide_address" value="1" @checked(old('hide_address',$listing->exists ? $listing->hide_address : true))> آدرس دقیق در آگهی عمومی نمایش داده نشود</label><label><input type="checkbox" name="allow_exchange" value="1" @checked(old('allow_exchange',$listing->allow_exchange))> معاوضه پذیرفته می‌شود</label></div>
<div class="actions"><button class="btn">ثبت و انتشار آگهی</button><a class="btn light" href="{{ route('properties.mine') }}">انصراف</a></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selector = document.getElementById('property-unit');
    const data = @json($unitAutofillData);
    if (!selector) return;
    selector.addEventListener('change', function () {
        const details = data[this.value];
        if (!details) return;
        Object.entries(details).forEach(([name, value]) => {
            if (value === null || value === undefined || value === '') return;
            const field = document.querySelector('[name="' + name + '"]');
            if (field) field.value = value;
        });
    });
});
</script>
@endsection
