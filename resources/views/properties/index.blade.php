@extends('layouts.marketplace')
@section('title', 'خرید، فروش و اجاره ملک')
@section('content')
<section class="hero"><h1>ملک موردنظرت را پیدا کن</h1><p>آگهی‌های خرید، فروش و اجاره در بازار تخصصی املاک صابخونه</p></section>
<form class="filters" method="GET"><div class="fields">
<label class="field">نوع آگهی<select name="listing_type"><option value="">همه موارد</option>@foreach(['sale'=>'فروش','rent'=>'اجاره','buy_request'=>'درخواست خرید','rent_request'=>'درخواست اجاره'] as $k=>$v)<option value="{{ $k }}" @selected(request('listing_type')===$k)>{{ $v }}</option>@endforeach</select></label>
<label class="field">نوع ملک<select name="property_type"><option value="">همه</option>@foreach(['residential'=>'مسکونی','commercial'=>'تجاری','land'=>'زمین'] as $k=>$v)<option value="{{ $k }}" @selected(request('property_type')===$k)>{{ $v }}</option>@endforeach</select></label>
<label class="field">شهر<input name="city" value="{{ request('city') }}" placeholder="مثلاً تهران"></label>
<label class="field">محله<input name="neighborhood" value="{{ request('neighborhood') }}"></label>
<label class="field">حداقل متراژ<input type="number" name="min_area" value="{{ request('min_area') }}"></label><label class="field">حداکثر متراژ<input type="number" name="max_area" value="{{ request('max_area') }}"></label>
</div><div class="actions"><button class="btn">جستجو و فیلتر</button><a class="btn light" href="{{ route('properties.index') }}">پاک‌کردن فیلترها</a></div></form>
<div class="grid">@forelse($listings as $listing)<a class="card" href="{{ route('properties.show',$listing) }}"><div class="photo">@if($listing->photos)<img src="{{ asset('storage/'.$listing->photos[0]) }}" alt="{{ $listing->title }}">@else تصویر آگهی @endif</div><div class="body"><h3>{{ $listing->title }}</h3><div class="muted">{{ $listing->city }} @if($listing->neighborhood) · {{ $listing->neighborhood }} @endif</div><div class="muted">{{ number_format((float)$listing->area) }} متر · {{ ['sale'=>'فروش','rent'=>'اجاره','buy_request'=>'درخواست خرید','rent_request'=>'درخواست اجاره'][$listing->listing_type] }}</div><div class="price">@if($listing->listing_type==='rent')ودیعه {{ number_format((int)$listing->deposit) }} · اجاره {{ number_format((int)$listing->monthly_rent) }}@else{{ $listing->price ? number_format((int)$listing->price).' تومان' : 'توافقی' }}@endif</div></div></a>@empty<div class="panel">هنوز آگهی فعالی ثبت نشده است.</div>@endforelse</div><div class="pagination">{{ $listings->links() }}</div>
@endsection
