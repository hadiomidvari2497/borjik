@extends('layouts.marketplace')
@section('title', $listing->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($listing->description), 150))
@section('content')
@if(session('status'))<div class="panel" style="margin-bottom:16px">{{ session('status') }}</div>@endif
<div class="detail">
    <section class="panel">
        <div class="photos">
            @forelse($listing->photos ?? [] as $photo)
                <img src="{{ asset('storage/'.$photo) }}" alt="{{ $listing->title }}">
            @empty
                <div class="photo">تصویری ثبت نشده</div>
            @endforelse
        </div>
    </section>
    <section class="panel">
        <div class="muted">{{ $listing->city }} @if($listing->neighborhood) · {{ $listing->neighborhood }} @endif</div>
        <h1>{{ $listing->title }}</h1>
        <p>{{ $listing->description }}</p>
        <hr>
        <p>متراژ: {{ $listing->area ? number_format((float)$listing->area).' متر' : '—' }}</p>
        <p>اتاق: {{ $listing->bedrooms ?? '—' }} · طبقه: {{ $listing->floor ?? '—' }} از {{ $listing->total_floors ?? '—' }}</p>
        <p>پارکینگ: {{ $listing->parking_count ?? '—' }}</p>
        <p>نوع ملک: {{ ['residential'=>'مسکونی','commercial'=>'تجاری','land'=>'زمین'][$listing->property_type] }}</p>
        <div class="price">
            @if($listing->listing_type==='rent')
                ودیعه {{ number_format((int)$listing->deposit) }} تومان · اجاره {{ number_format((int)$listing->monthly_rent) }} تومان
            @else
                {{ $listing->price ? number_format((int)$listing->price).' تومان' : 'توافقی' }}
            @endif
        </div>
        @if($listing->address && !$listing->hide_address)<p>آدرس: {{ $listing->address }}</p>@endif
        <p class="muted">ثبت‌کننده: {{ $listing->user->display_name }}</p>
        @auth
            <p>برای هماهنگی با ثبت‌کننده از طریق اطلاعات تماس حساب کاربری اقدام کنید.</p>
            @if((int) auth()->id() !== (int) $listing->user_id)
                <details style="margin-top:20px">
                    <summary style="cursor:pointer;color:var(--brand-d);font-weight:700">گزارش تخلف یا اطلاعات نادرست</summary>
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
            <a class="btn" href="{{ route('login') }}">برای مشاهده اطلاعات تماس وارد شوید</a>
            <p class="muted" style="margin-top:12px">برای گزارش تخلف این آگهی نیز ابتدا وارد حساب کاربری شوید.</p>
        @endauth
    </section>
</div>
@endsection
