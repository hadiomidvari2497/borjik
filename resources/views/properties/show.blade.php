@extends('layouts.marketplace')
@section('title', $listing->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($listing->description), 150))
@section('content')
<style>
.detail-wrap{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(300px,.75fr);gap:20px;align-items:start}
.detail-panel{background:#fff;border:1px solid #e1eae7;border-radius:19px;padding:20px;box-shadow:0 8px 25px #173b3508}
.gallery{display:grid;grid-template-columns:repeat(4,1fr);gap:9px}.gallery a{display:block;overflow:hidden;border-radius:13px;background:#edf3f0}.gallery img{width:100%;height:125px;object-fit:cover;display:block}.gallery a:first-child{grid-column:span 4}.gallery a:first-child img{height:350px}
.detail-kicker{display:flex;align-items:center;gap:8px;flex-wrap:wrap;color:#648078;font-size:12px}.type-pill{background:#e8f3f0;color:#176b5d;padding:6px 10px;border-radius:20px;font-weight:800}
.detail-title{font-size:clamp(21px,3vw,29px);line-height:1.6;margin:13px 0 5px}.detail-location{color:#71857e;font-size:13px}
.detail-price{margin:20px 0;padding:17px;border-radius:14px;background:linear-gradient(110deg,#eaf5f1,#f6faf8);color:#145f53;font-size:17px;font-weight:900;line-height:2}
.spec-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:18px 0}.spec{padding:12px;border:1px solid #e8efec;border-radius:12px}.spec small{display:block;color:#81918c;font-size:11px;margin-bottom:4px}.spec strong{font-size:14px;color:#234b44}
.detail-section{border-top:1px solid #edf1ef;padding-top:17px;margin-top:17px}.detail-section h2{font-size:15px;margin:0 0 12px}.description{font-size:13px;line-height:2.2;color:#405e56;white-space:pre-line}
.amenities{display:flex;gap:8px;flex-wrap:wrap}.amenities span{background:#f1f6f4;border-radius:9px;padding:8px 11px;font-size:12px;color:#315e53}
.owner-box{display:flex;align-items:center;gap:12px;background:#f6f9f8;border-radius:13px;padding:13px}.owner-avatar{width:42px;height:42px;display:grid;place-items:center;border-radius:50%;background:#dcefe9;color:#176b5d;font-weight:900;font-size:18px}
.report-box{margin-top:20px}.report-box summary{cursor:pointer;color:#a34b3d;font-size:12px;font-weight:800}
@media(max-width:800px){.detail-wrap{grid-template-columns:1fr}.gallery a:first-child img{height:260px}}
@media(max-width:480px){.detail-panel{padding:14px}.gallery{grid-template-columns:repeat(3,1fr)}.gallery a:first-child{grid-column:span 3}.gallery a:first-child img{height:220px}.gallery img{height:90px}}
</style>
@if(session('status'))<div class="panel" style="margin-bottom:16px">{{ session('status') }}</div>@endif
<div class="detail-wrap">
    <section class="detail-panel">
        <div class="gallery">
            @forelse($listing->photos ?? [] as $photo)
                <a href="{{ asset('storage/'.$photo) }}" target="_blank" rel="noopener"><img src="{{ asset('storage/'.$photo) }}" alt="{{ $listing->title }} - تصویر {{ $loop->iteration }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}"></a>
            @empty
                <div class="photo" style="grid-column:span 4;height:260px">تصویری برای این آگهی ثبت نشده است</div>
            @endforelse
        </div>
        <div class="detail-section">
            <h2>درباره ملک</h2>
            <div class="description">{{ $listing->description }}</div>
        </div>
        @if(!empty($listing->amenities))
            <div class="detail-section"><h2>امکانات و ویژگی‌ها</h2><div class="amenities">@foreach($listing->amenities as $amenity)<span>{{ $amenity }}</span>@endforeach</div></div>
        @endif
    </section>
    <aside class="detail-panel">
        <div class="detail-kicker">
            <span class="type-pill">{{ ['sale'=>'فروش','rent'=>'اجاره','buy_request'=>'درخواست خرید','rent_request'=>'درخواست اجاره'][$listing->listing_type] }}</span>
            <span>{{ ['residential'=>'مسکونی','commercial'=>'تجاری','land'=>'زمین'][$listing->property_type] }}</span>
        </div>
        <h1 class="detail-title">{{ $listing->title }}</h1>
        <div class="detail-location">⌖ {{ $listing->city }} @if($listing->neighborhood) · {{ $listing->neighborhood }} @endif</div>
        <div class="detail-price">
            @if($listing->listing_type==='rent')
                <div>ودیعه: {{ $listing->deposit ? number_format((int)$listing->deposit).' تومان' : 'توافقی' }}</div>
                <div>اجاره ماهانه: {{ $listing->monthly_rent ? number_format((int)$listing->monthly_rent).' تومان' : 'توافقی' }}</div>
            @elseif(in_array($listing->listing_type,['buy_request','rent_request'],true))
                مبلغ پیشنهادی: {{ $listing->price ? number_format((int)$listing->price).' تومان' : 'توافقی' }}
            @else
                {{ $listing->price ? number_format((int)$listing->price).' تومان' : 'قیمت توافقی' }}
            @endif
        </div>
        <div class="spec-grid">
            <div class="spec"><small>متراژ</small><strong>{{ $listing->area ? number_format((float)$listing->area).' متر' : 'اعلام نشده' }}</strong></div>
            <div class="spec"><small>اتاق خواب</small><strong>{{ $listing->bedrooms ?? '—' }}</strong></div>
            <div class="spec"><small>سرویس بهداشتی</small><strong>{{ $listing->bathrooms ?? '—' }}</strong></div>
            <div class="spec"><small>پارکینگ</small><strong>{{ $listing->parking_count ?? '—' }}</strong></div>
            <div class="spec"><small>طبقه</small><strong>{{ $listing->floor ?? '—' }} @if($listing->total_floors) از {{ $listing->total_floors }} @endif</strong></div>
            <div class="spec"><small>سال ساخت</small><strong>{{ $listing->built_year ?? '—' }}</strong></div>
            <div class="spec"><small>نوع سند</small><strong>{{ $listing->document_type ?: 'اعلام نشده' }}</strong></div>
            <div class="spec"><small>معاوضه</small><strong>{{ $listing->allow_exchange ? 'پذیرفته می‌شود' : 'ذکر نشده' }}</strong></div>
        </div>
        @if($listing->address && !$listing->hide_address)
            <div class="detail-section"><h2>نشانی</h2><p class="description">{{ $listing->address }}</p></div>
        @else
            <p class="muted">نشانی دقیق برای حفظ حریم خصوصی نمایش داده نمی‌شود.</p>
        @endif
        <div class="detail-section">
            <h2>ثبت‌کننده آگهی</h2>
            <div class="owner-box"><div class="owner-avatar">{{ mb_substr($listing->user->display_name,0,1) }}</div><div><strong>{{ $listing->user->display_name }}</strong><div class="muted">عضو بازار املاک صابخونه</div></div></div>
            @auth
                <p class="muted" style="line-height:2;margin-top:12px">برای حفظ حریم خصوصی، اطلاعات تماس مستقیم در صفحه عمومی منتشر نمی‌شود.</p>
            @else
                <a class="btn" style="width:100%;margin-top:12px" href="{{ route('login') }}">ورود به حساب کاربری</a>
            @endauth
        </div>
        @auth
            @if((int) auth()->id() !== (int) $listing->user_id)
                <details class="report-box">
                    <summary>گزارش تخلف یا اطلاعات نادرست</summary>
                    <form method="POST" action="{{ route('properties.reports.store', $listing->id) }}" class="panel" style="margin-top:12px">
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
                        <label class="field">توضیحات تکمیلی<textarea name="details" rows="3" maxlength="1500">{{ old('details') }}</textarea></label>
                        @error('reason')<p class="error">{{ $message }}</p>@enderror
                        @error('details')<p class="error">{{ $message }}</p>@enderror
                        @error('report')<p class="error">{{ $message }}</p>@enderror
                        <button class="btn" type="submit">ثبت گزارش</button>
                    </form>
                </details>
            @endif
        @else
            <p class="muted" style="margin-top:12px">برای گزارش تخلف این آگهی ابتدا وارد حساب کاربری شوید.</p>
        @endauth
    </aside>
</div>
@endsection
