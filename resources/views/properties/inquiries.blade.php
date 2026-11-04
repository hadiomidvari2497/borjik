@extends('layouts.marketplace')
@section('title', 'درخواست‌های تماس آگهی‌ها')
@section('content')
<section class="hero">
    <h1>درخواست‌های تماس</h1>
    <p>پیام افرادی که درباره آگهی‌های شما پرس‌وجو کرده‌اند، اینجا نمایش داده می‌شود.</p>
</section>
@if(session('status'))<div class="notice" style="margin:16px 0">{{ session('status') }}</div>@endif
<section class="panel" style="margin-top:18px">
    @forelse($inquiries as $inquiry)
        <article style="padding:16px 0;border-bottom:1px solid var(--line)">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <strong>{{ $inquiry->sender_name }}</strong>
                    <div class="muted" style="font-size:12px;margin-top:5px">
                        درباره: <a href="{{ route('properties.show', $inquiry->listing) }}">{{ $inquiry->listing->title }}</a>
                        · {{ \App\Support\Fa::date($inquiry->created_at, 'Y/m/d H:i') }}
                    </div>
                </div>
                <span class="tag {{ $inquiry->status === 'new' ? 'rent' : 'plain' }}">{{ ['new'=>'جدید','contacted'=>'تماس گرفته شد','closed'=>'بسته‌شده'][$inquiry->status] ?? $inquiry->status }}</span>
            </div>
            <p style="white-space:pre-line;line-height:2;margin:14px 0">{{ $inquiry->message }}</p>
            @if($inquiry->sender_mobile)
                <p><strong>شماره تماس با رضایت فرستنده:</strong> <a href="tel:{{ $inquiry->sender_mobile }}">{{ $inquiry->sender_mobile }}</a></p>
            @else
                <p class="muted" style="font-size:12px">فرستنده اجازه اشتراک شماره موبایل نداده است.</p>
            @endif
            <form method="POST" action="{{ route('properties.inquiries.update', $inquiry) }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                @csrf @method('PATCH')
                <label class="field" style="max-width:220px;margin:0">وضعیت
                    <select name="status">
                        <option value="new" @selected($inquiry->status === 'new')>جدید</option>
                        <option value="contacted" @selected($inquiry->status === 'contacted')>تماس گرفته شد</option>
                        <option value="closed" @selected($inquiry->status === 'closed')>بسته‌شده</option>
                    </select>
                </label>
                <button class="btn" type="submit">ذخیره وضعیت</button>
            </form>
        </article>
    @empty
        <div class="notice">هنوز درخواست تماسی برای آگهی‌های شما ثبت نشده است.</div>
    @endforelse
    <div class="pagination">{{ $inquiries->links() }}</div>
</section>
@endsection
