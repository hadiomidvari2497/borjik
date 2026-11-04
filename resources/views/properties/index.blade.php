@extends('layouts.marketplace')
@section('title', 'خرید، فروش و اجاره ملک')
@section('content')
<style>
.market-hero{position:relative;overflow:hidden;padding:34px;border-radius:24px;background:linear-gradient(120deg,#103c39 0%,#1d7768 65%,#36a18a 100%);color:#fff;margin-bottom:20px}
.market-hero:after{content:"";position:absolute;width:240px;height:240px;border:1px solid #ffffff30;border-radius:50%;left:-60px;top:-130px;box-shadow:0 0 0 35px #ffffff0c,0 0 0 70px #ffffff08}
.market-hero h1{font-size:clamp(23px,4vw,32px);margin:0 0 8px}.market-hero p{margin:0;color:#e0f1ed;line-height:2;font-size:13px}
.search-box{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;margin-top:22px;max-width:760px;position:relative;z-index:1}
.search-box input{min-width:0;border:1px solid #ffffff50;background:#ffffffed;border-radius:13px;padding:13px 15px;font:inherit;color:#193b38}
.filter-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}.filter-heading h2{font-size:16px;margin:0}
.filter-toggle{font:inherit;color:#176b5d;background:#e8f3f0;border:0;border-radius:10px;padding:8px 12px;cursor:pointer}
.results-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:22px 0 14px}.results-head h2{font-size:17px;margin:0}.sort-select{border:1px solid #d9e4e0;border-radius:10px;padding:9px;background:#fff;font:inherit;color:#193b38}
.listing-photo{height:190px;position:relative;background:#e7efec}.listing-photo img{width:100%;height:100%;object-fit:cover;display:block}.photo-count{position:absolute;bottom:10px;right:10px;border-radius:20px;background:#102e2bbd;color:white;padding:5px 9px;font-size:11px}
.listing-type{position:absolute;top:10px;right:10px;background:#fff;color:#176b5d;border-radius:20px;padding:6px 10px;font-size:11px;font-weight:800}
.listing-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}.listing-meta span{font-size:11px;color:#657d76;background:#f2f6f4;padding:5px 8px;border-radius:8px}
.price-line{margin-top:14px;padding-top:12px;border-top:1px solid #edf1ef;color:#176b5d;font-weight:900;font-size:13px}
.empty-state{text-align:center;padding:38px 18px}.empty-state .symbol{font-size:36px;margin-bottom:8px}
@media(max-width:620px){.market-hero{padding:24px 18px}.search-box{grid-template-columns:1fr}.results-head{align-items:flex-start;flex-direction:column}.sort-select{width:100%}}
</style>
<section class="market-hero">
    <h1>خانه‌ای برای قصه بعدی زندگی‌ات پیدا کن</h1>
    <p>آگهی‌های خرید، فروش و اجاره را در بازار املاک صابخونه جست‌وجو کن.</p>
    <form class="search-box" method="GET" action="{{ route('properties.index') }}">
        <input name="q" value="{{ request('q') }}" placeholder="جست‌وجو بر اساس عنوان، محله یا توضیحات..." aria-label="عبارت جست‌وجو">
        <button class="btn light" type="submit">جست‌وجو در آگهی‌ها</button>
    </form>
</section>
<form class="filters" method="GET" action="{{ route('properties.index') }}">
    <div class="filter-heading"><h2>فیلترهای دقیق‌تر</h2><a class="muted" href="{{ route('properties.index') }}">حذف همه فیلترها</a></div>
    <input type="hidden" name="q" value="{{ request('q') }}">
    <div class="fields">
        <label class="field">نوع آگهی
            <select name="listing_type"><option value="">همه موارد</option>@foreach(['sale'=>'فروش','rent'=>'اجاره','buy_request'=>'درخواست خرید','rent_request'=>'درخواست اجاره'] as $k=>$v)<option value="{{ $k }}" @selected(request('listing_type')===$k)>{{ $v }}</option>@endforeach</select>
        </label>
        <label class="field">نوع ملک
            <select name="property_type"><option value="">همه انواع</option>@foreach(['residential'=>'مسکونی','commercial'=>'تجاری','land'=>'زمین'] as $k=>$v)<option value="{{ $k }}" @selected(request('property_type')===$k)>{{ $v }}</option>@endforeach</select>
        </label>
        <label class="field">استان<input name="province" value="{{ request('province') }}" placeholder="مثلاً تهران"></label>
        <label class="field">شهر<input name="city" value="{{ request('city') }}" placeholder="نام شهر"></label>
        <label class="field">محله<input name="neighborhood" value="{{ request('neighborhood') }}" placeholder="نام محله"></label>
        <label class="field">حداقل متراژ (متر)<input type="number" name="min_area" min="1" value="{{ request('min_area') }}"></label>
        <label class="field">حداکثر متراژ (متر)<input type="number" name="max_area" min="1" value="{{ request('max_area') }}"></label>
        <label class="field">حداقل مبلغ (تومان)<input type="number" name="min_price" min="0" value="{{ request('min_price') }}"></label>
        <label class="field">حداکثر مبلغ (تومان)<input type="number" name="max_price" min="0" value="{{ request('max_price') }}"></label>
        <label class="field">حداقل تعداد اتاق<input type="number" name="bedrooms" min="0" max="30" value="{{ request('bedrooms') }}"></label>
        <label class="field">پارکینگ
            <select name="parking"><option value="">مهم نیست</option><option value="1" @selected(request('parking')==='1')>فقط دارای پارکینگ</option><option value="0" @selected(request('parking')==='0')>بدون محدودیت</option></select>
        </label>
    </div>
    <div class="actions"><button class="btn" type="submit">اعمال فیلترها</button><a class="btn light" href="{{ route('properties.index') }}">پاک‌کردن فیلترها</a></div>
</form>
<div class="results-head">
    <h2>آگهی‌های موجود <span class="muted">({{ number_format($listings->total()) }} مورد)</span></h2>
    <form method="GET" action="{{ route('properties.index') }}">
        @foreach(request()->except('sort','page') as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
        <select class="sort-select" name="sort" aria-label="مرتب‌سازی آگهی‌ها" onchange="this.form.submit()">
            <option value="newest" @selected(request('sort','newest')==='newest')>جدیدترین آگهی‌ها</option>
            <option value="price_asc" @selected(request('sort')==='price_asc')>ارزان‌ترین</option>
            <option value="price_desc" @selected(request('sort')==='price_desc')>گران‌ترین</option>
            <option value="area_desc" @selected(request('sort')==='area_desc')>بیشترین متراژ</option>
        </select>
    </form>
</div>
<div class="grid">
    @forelse($listings as $listing)
        <a class="card" href="{{ route('properties.show',$listing) }}">
            <div class="listing-photo">
                @if($listing->photos)<img src="{{ asset('storage/'.$listing->photos[0]) }}" alt="{{ $listing->title }}" loading="lazy">@else<div class="photo">تصویر آگهی ثبت نشده</div>@endif
                <span class="listing-type">{{ ['sale'=>'فروش','rent'=>'اجاره','buy_request'=>'درخواست خرید','rent_request'=>'درخواست اجاره'][$listing->listing_type] }}</span>
                @if(count($listing->photos ?? []) > 1)<span class="photo-count">{{ number_format(count($listing->photos)) }} تصویر</span>@endif
            </div>
            <div class="body">
                <h3>{{ $listing->title }}</h3>
                <div class="muted">{{ $listing->city }} @if($listing->neighborhood) · {{ $listing->neighborhood }} @endif</div>
                <div class="listing-meta">
                    @if($listing->area)<span>{{ number_format((float)$listing->area) }} متر</span>@endif
                    @if($listing->bedrooms !== null)<span>{{ number_format($listing->bedrooms) }} خواب</span>@endif
                    @if($listing->parking_count > 0)<span>پارکینگ</span>@endif
                </div>
                <div class="price-line">
                    @if($listing->listing_type==='rent')
                        ودیعه {{ $listing->deposit ? number_format((int)$listing->deposit).' تومان' : 'توافقی' }} · اجاره {{ $listing->monthly_rent ? number_format((int)$listing->monthly_rent).' تومان' : 'توافقی' }}
                    @elseif(in_array($listing->listing_type,['buy_request','rent_request'],true))
                        مبلغ پیشنهادی: {{ $listing->price ? number_format((int)$listing->price).' تومان' : 'توافقی' }}
                    @else
                        {{ $listing->price ? number_format((int)$listing->price).' تومان' : 'توافقی' }}
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="panel empty-state"><div class="symbol">⌕</div><h3>آگهی‌ای با این مشخصات پیدا نشد</h3><p class="muted">فیلترها را کمی تغییر بده یا همه آگهی‌ها را ببین.</p><a class="btn" href="{{ route('properties.index') }}">نمایش همه آگهی‌ها</a></div>
    @endforelse
</div>
<div class="pagination">{{ $listings->links() }}</div>
@endsection
