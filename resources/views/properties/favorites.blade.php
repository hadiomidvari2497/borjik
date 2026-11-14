@extends('layouts.marketplace')
@section('title','آگهی‌های نشان‌شده')
@section('content')
<section class="hero"><h1>آگهی‌های نشان‌شده</h1><p>آگهی‌هایی که ذخیره کرده‌ای تا هر وقت خواستی دوباره به آن‌ها سر بزنی.</p></section>
<div class="favorites-intro"><p data-favorites-count data-count="{{ $listings->total() }}">{{ \App\Support\Fa::num($listings->total()) }} آگهی ذخیره‌شده</p><a class="btn light" href="{{ route('properties.index') }}">مشاهده آگهی‌ها</a></div>
<div class="grid" id="saved-listings-grid">@forelse($listings as $listing)@include('properties.partials.card',['listing'=>$listing])@empty
<div class="empty" style="grid-column:1/-1"><div class="empty-ico"><svg class="icon" aria-hidden="true"><use href="#i-bookmark"/></svg></div><h3>هنوز آگهی‌ای نشان نکرده‌ای</h3><p class="muted">وقتی در میان آگهی‌ها موردی توجهت را جلب کرد، نشانش کن تا اینجا نگه داشته شود.</p><div class="actions" style="justify-content:center"><a class="btn lg" href="{{ route('properties.index') }}">رفتن به بازار</a></div></div>
@endforelse</div><div class="pagination">{{ $listings->links() }}</div>
@endsection
