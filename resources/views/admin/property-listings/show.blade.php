@extends('admin.layout')
@section('title', 'بررسی آگهی ملکی')
@section('content')
@php
    $F = \App\Support\Fa::class;
    $typeLabels = ['sale' => 'فروش', 'rent' => 'اجاره', 'buy_request' => 'درخواست خرید', 'rent_request' => 'درخواست اجاره'];
    $propertyLabels = ['residential' => 'مسکونی', 'commercial' => 'تجاری', 'land' => 'زمین'];
    $statusLabels = ['active' => 'فعال', 'closed' => 'غیرفعال', 'rejected' => 'ردشده'];
@endphp
<div class="card">
    <div class="page-h">
        <div><h2>{{ $listing->title }}</h2><p class="muted">شناسه آگهی: #{{ $F::num($listing->id) }}</p></div>
        <a class="btn sec sm" href="{{ route('admin.property-listings.index') }}">بازگشت به بازارچه</a>
    </div>
    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
        <div><small class="muted">ثبت‌کننده</small><div><b>{{ $listing->user?->display_name ?? 'کاربر حذف‌شده' }}</b></div><div dir="ltr">{{ $listing->user?->mobile ?? '—' }}</div></div>
        <div><small class="muted">نوع آگهی</small><div><b>{{ $typeLabels[$listing->listing_type] ?? $listing->listing_type }}</b></div></div>
        <div><small class="muted">نوع ملک</small><div><b>{{ $propertyLabels[$listing->property_type] ?? $listing->property_type }}</b></div></div>
        <div><small class="muted">وضعیت</small><div><b>{{ $statusLabels[$listing->status] ?? $listing->status }}</b></div></div>
        <div><small class="muted">شهر و محله</small><div><b>{{ $listing->province }} {{ $listing->city }} {{ $listing->neighborhood }}</b></div></div>
        <div><small class="muted">تاریخ ثبت</small><div><b>{{ $listing->created_at ? $F::date($listing->created_at, 'j F Y H:i') : '—' }}</b></div></div>
    </div>
</div>

<div class="card">
    <h3>جزئیات ملک و قیمت</h3>
    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px">
        <div><small class="muted">متراژ</small><div>{{ $listing->area ? $F::num($listing->area) . ' متر' : '—' }}</div></div>
        <div><small class="muted">اتاق</small><div>{{ $listing->bedrooms === null ? '—' : $F::num($listing->bedrooms) }}</div></div>
        <div><small class="muted">سرویس</small><div>{{ $listing->bathrooms === null ? '—' : $F::num($listing->bathrooms) }}</div></div>
        <div><small class="muted">پارکینگ</small><div>{{ $listing->parking_count === null ? '—' : $F::num($listing->parking_count) }}</div></div>
        <div><small class="muted">طبقه</small><div>{{ $listing->floor === null ? '—' : $F::num($listing->floor) }}</div></div>
        <div><small class="muted">سال ساخت</small><div>{{ $listing->built_year ? $F::num($listing->built_year) : '—' }}</div></div>
        <div><small class="muted">قیمت فروش</small><div>{{ $listing->price ? $F::money($listing->price) : '—' }}</div></div>
        <div><small class="muted">ودیعه</small><div>{{ $listing->deposit ? $F::money($listing->deposit) : '—' }}</div></div>
        <div><small class="muted">اجاره ماهانه</small><div>{{ $listing->monthly_rent ? $F::money($listing->monthly_rent) : '—' }}</div></div>
    </div>
    @if($listing->unit)
        <p class="muted" style="margin-top:14px">متصل به واحد {{ $listing->unit->number ?? $listing->unit->id }} از ساختمان {{ $listing->unit->building?->name ?? '—' }}</p>
    @else
        <p class="muted" style="margin-top:14px">این آگهی مستقل است و به واحد ساختمانی متصل نیست.</p>
    @endif
    <h3 style="margin-top:18px">توضیحات</h3>
    <div style="white-space:pre-line;line-height:2">{{ $listing->description }}</div>
    @if($listing->address && ! $listing->hide_address)
        <p><b>آدرس درج‌شده:</b> {{ $listing->address }}</p>
    @endif
    @if($listing->photos)
        <h3 style="margin-top:18px">تصاویر</h3>
        <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px">
            @foreach($listing->photos as $photo)
                <a href="{{ \Illuminate\Support\Facades\\Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}" target="_blank" rel="noopener"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photo) }}" alt="تصویر آگهی" style="width:100%;height:160px;object-fit:cover;border-radius:12px"></a>
            @endforeach
        </div>
    @endif
</div>

<div class="card">
    <h3>رسیدگی و وضعیت انتشار</h3>
    @if($listing->moderation_note)
        <p class="muted">یادداشت رسیدگی قبلی: {{ $listing->moderation_note }}</p>
    @endif
    @if($listing->moderated_at)
        <p class="muted">آخرین رسیدگی: {{ $F::date($listing->moderated_at, 'j F Y H:i') }}</p>
    @endif
    <form method="POST" action="{{ route('admin.property-listings.status', $listing->id) }}" class="fgrid">
        @csrf
        @method('PATCH')
        <div class="fld">
            <label for="status">وضعیت جدید</label>
            <select id="status" name="status" required>
                @foreach($statusLabels as $key => $label)
                    <option value="{{ $key }}" @selected($listing->status === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="fld">
            <label for="moderation_note">یادداشت مدیر (اختیاری)</label>
            <textarea id="moderation_note" name="moderation_note" rows="3" maxlength="2000" placeholder="دلیل رد یا توضیح تغییر وضعیت">{{ old('moderation_note', $listing->moderation_note) }}</textarea>
        </div>
        <div class="fbar"><button class="btn" type="submit">ذخیره وضعیت</button></div>
    </form>
</div>
@endsection
