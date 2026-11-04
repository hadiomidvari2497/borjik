@extends('layouts.marketplace')
@section('title', 'گفتگو درباره آگهی')
@section('content')
<section class="hero">
    <h1>گفتگو درباره آگهی</h1>
    <p><a href="{{ route('properties.show', $inquiry->listing) }}">{{ $inquiry->listing->title }}</a></p>
    <div class="muted">طرف گفتگو: {{ (int) auth()->id() === (int) $inquiry->sender_user_id ? $inquiry->recipient->display_name : $inquiry->sender_name }}</div>
</section>
@if(session('status'))<div class="notice" style="margin-top:14px">{{ session('status') }}</div>@endif
<section class="panel" style="margin-top:18px">
    <div style="display:flex;flex-direction:column;gap:12px">
        <article style="max-width:85%;padding:13px 16px;border-radius:14px;background:#edf6f2;align-self:{{ (int) auth()->id() === (int) $inquiry->sender_user_id ? 'flex-start' : 'flex-end' }}">
            <div class="muted" style="font-size:11px;margin-bottom:6px">{{ $inquiry->sender_name }} · {{ \App\Support\Fa::date($inquiry->created_at, 'Y/m/d H:i') }}</div>
            <div style="white-space:pre-wrap;line-height:2">{{ $inquiry->message }}</div>
            @if($inquiry->sender_mobile && (int) auth()->id() === (int) $inquiry->recipient_user_id)
                <div style="margin-top:8px"><a href="tel:{{ $inquiry->sender_mobile }}">تماس با فرستنده: {{ $inquiry->sender_mobile }}</a></div>
            @endif
        </article>
        @foreach($messages as $message)
            <article style="max-width:85%;padding:13px 16px;border-radius:14px;background:{{ (int) $message->sender_user_id === (int) auth()->id() ? '#edf6f2' : '#f5f7f6' }};align-self:{{ (int) $message->sender_user_id === (int) auth()->id() ? 'flex-start' : 'flex-end' }}">
                <div class="muted" style="font-size:11px;margin-bottom:6px">{{ $message->sender->display_name }} · {{ \App\Support\Fa::date($message->created_at, 'Y/m/d H:i') }}</div>
                <div style="white-space:pre-wrap;line-height:2">{{ $message->body }}</div>
            </article>
        @endforeach
    </div>
    @if($inquiry->status !== 'closed')
        <form method="POST" action="{{ route('properties.chat.send', $inquiry) }}" style="margin-top:20px">
            @csrf
            <label class="field">پیام جدید
                <textarea name="body" rows="3" maxlength="2000" required placeholder="پیام خود را بنویسید...">{{ old('body') }}</textarea>
            </label>
            @error('body')<p class="error">{{ $message }}</p>@enderror
            <button class="btn" type="submit">ارسال پیام</button>
        </form>
    @else
        <div class="notice" style="margin-top:18px">این گفتگو بسته شده است و امکان ارسال پیام جدید وجود ندارد.</div>
    @endif
</section>
@endsection
