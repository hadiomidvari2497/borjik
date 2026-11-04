@extends('layouts.marketplace')
@section('title', 'پیام‌ها و گفتگوهای آگهی')
@section('content')
<section class="hero">
    <h1>پیام‌ها و گفتگوها</h1>
    <p>گفتگوهای خصوصی شما با خریداران و فروشندگان آگهی‌ها.</p>
    <div class="actions"><a class="btn light" href="{{ route('properties.mine') }}">آگهی‌های من</a></div>
</section>
@if(session('status'))<div class="notice" style="margin:16px 0">{{ session('status') }}</div>@endif
<section class="panel" style="margin-top:18px">
    @forelse($inquiries as $inquiry)
        @php($isOwner = (int) auth()->id() === (int) $inquiry->recipient_user_id)
        <article style="padding:16px 0;border-bottom:1px solid var(--line)">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <strong>{{ $isOwner ? $inquiry->sender_name : $inquiry->recipient->display_name }}</strong>
                    <div class="muted" style="font-size:12px;margin-top:5px">
                        درباره: <a href="{{ route('properties.show', $inquiry->listing) }}">{{ $inquiry->listing->title }}</a>
                        · {{ \App\Support\Fa::date($inquiry->updated_at, 'Y/m/d H:i') }}
                    </div>
                </div>
                <span class="tag {{ $inquiry->status === 'new' ? 'rent' : 'plain' }}">{{ ['new'=>'جدید','contacted'=>'در حال گفتگو','closed'=>'بسته‌شده'][$inquiry->status] ?? $inquiry->status }}</span>
            </div>
            <p style="white-space:pre-line;line-height:2;margin:12px 0">{{ \Illuminate\Support\Str::limit($inquiry->message, 180) }}</p>
            <div class="actions">
                <a class="btn" href="{{ route('properties.chat.show', $inquiry) }}">باز کردن گفتگو</a>
                @if($isOwner && $inquiry->sender_mobile)
                    <a class="btn light" href="tel:{{ $inquiry->sender_mobile }}">تماس با فرستنده · {{ $inquiry->sender_mobile }}</a>
                @endif
            </div>
        </article>
    @empty
        <div class="notice">هنوز گفتگویی ندارید. از صفحه یک آگهی می‌توانید گفتگو را آغاز کنید.</div>
    @endforelse
    <div class="pagination">{{ $inquiries->links() }}</div>
</section>
@endsection
