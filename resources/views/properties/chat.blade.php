@extends('layouts.marketplace')
@section('title', 'گفتگو درباره آگهی')
@section('content')
@php
    $isInitiator = (int) auth()->id() === (int) $inquiry->sender_user_id;
    $chatPartner = $isInitiator ? $inquiry->recipient->display_name : $inquiry->sender_name;
@endphp
<style>
.chat-page{max-width:980px;margin:0 auto;padding-bottom:35px}
.chat-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px;flex-wrap:wrap}
.chat-back{display:inline-flex;align-items:center;gap:7px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:700}
.chat-back:hover{color:#168653}
.chat-listing-card{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:17px 20px;border:1px solid var(--line);border-radius:18px;background:var(--panel,#fff);margin-bottom:14px;box-shadow:0 7px 24px rgba(18,50,36,.04)}
.chat-listing-label{font-size:11px;color:var(--muted);margin-bottom:4px}
.chat-listing-title{font-size:14px;font-weight:800;color:var(--text);text-decoration:none}
.chat-listing-meta{font-size:11px;color:var(--muted);margin-top:4px}
.chat-state-pill{font-size:11px;padding:7px 11px;border-radius:20px;background:#eaf5ee;color:#176343;white-space:nowrap}
.chat-state-pill.closed{background:#f1f2f2;color:#737d78}
.chat-shell{overflow:hidden;border:1px solid var(--line);border-radius:22px;background:var(--panel,#fff);box-shadow:0 16px 42px rgba(18,50,36,.07)}
.chat-header{display:flex;align-items:center;gap:13px;padding:18px 22px;background:linear-gradient(110deg,#f4faf6,#fff);border-bottom:1px solid var(--line)}
.chat-partner-avatar{width:46px;height:46px;flex:0 0 46px;border-radius:16px;display:grid;place-items:center;background:linear-gradient(145deg,#dff1e6,#bfe3cf);color:#176343;font-size:18px;font-weight:850}
.chat-partner-info{min-width:0;flex:1}
.chat-partner-name{font-size:15px;font-weight:850;color:var(--text)}
.chat-partner-sub{font-size:11px;color:var(--muted);margin-top:4px}
.chat-online-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#1b9b66;margin-left:5px}
.chat-thread{display:flex;flex-direction:column;gap:14px;height:min(58vh,620px);min-height:330px;overflow-y:auto;padding:24px clamp(14px,4vw,38px);scroll-behavior:smooth;background:radial-gradient(ellipse at top,#f7fbf8 0%,#fff 65%)}
.chat-message{position:relative;max-width:min(78%,620px);padding:12px 16px 10px;border-radius:18px;line-height:1.95;overflow-wrap:anywhere;box-shadow:0 3px 12px rgba(20,45,32,.045)}
.chat-message.mine{background:#e4f4ea;color:#173e2d;align-self:flex-start;border-bottom-right-radius:5px}
.chat-message.theirs{background:#f1f4f2;color:#25352d;align-self:flex-end;border-bottom-left-radius:5px}
.chat-message-meta{display:flex;align-items:center;gap:8px;font-size:10px;color:#71867b;margin-bottom:5px}
.chat-message-body{white-space:pre-wrap;font-size:13px}
.chat-message-time{font-size:10px;color:#81938a;text-align:left;margin-top:4px}
.chat-initial-message{border:1px dashed rgba(29,120,79,.24)}
.chat-composer-wrap{padding:15px 18px 17px;border-top:1px solid var(--line);background:#fff}
.chat-composer{display:flex;align-items:flex-end;gap:10px}
.chat-composer .field{flex:1;margin:0;min-width:0}
.chat-composer textarea{width:100%;min-height:52px;max-height:150px;resize:vertical;border:1px solid var(--line);border-radius:14px;padding:12px 14px;background:#fbfdfb;line-height:1.8}
.chat-composer textarea:focus{outline:none;border-color:#4a9c75;box-shadow:0 0 0 3px rgba(39,143,91,.1)}
.chat-send-btn{min-height:48px;padding:0 22px;border:0;border-radius:13px;background:linear-gradient(135deg,#16764e,#219565);color:#fff;font-weight:800;box-shadow:0 6px 16px rgba(24,126,79,.2);cursor:pointer}
.chat-send-btn:disabled{opacity:.55;cursor:wait}
.chat-composer-hint{font-size:10px;color:var(--muted);margin:8px 3px 0}
.chat-status{font-size:11px;color:#71867b;min-height:17px;margin:7px 3px 0}
.chat-status.error{color:#b42318}
.chat-closed{padding:17px 20px;margin:0;border-top:1px solid var(--line);background:#fafbfa;color:#68766f;font-size:13px}
.chat-notice{margin:0 0 14px}
@media(max-width:600px){.chat-listing-card{padding:14px;border-radius:15px}.chat-shell{border-radius:17px}.chat-header{padding:14px}.chat-thread{height:58vh;min-height:300px;padding:17px 12px}.chat-message{max-width:91%;padding:10px 13px}.chat-composer-wrap{padding:12px}.chat-composer{align-items:stretch;flex-direction:column}.chat-send-btn{width:100%}.chat-topbar{margin-bottom:10px}}
</style>
<div class="chat-page">
    <div class="chat-topbar">
        <a class="chat-back" href="{{ route('properties.inquiries.index') }}">→ بازگشت به همه گفتگوها</a>
        <span class="muted" style="font-size:11px">گفتگوی امن بازار املاک صابخونه</span>
    </div>
    @if(session('status'))<div class="notice chat-notice">{{ session('status') }}</div>@endif

    <section class="chat-listing-card">
        <div>
            <div class="chat-listing-label">موضوع گفتگو · آگهی موردنظر</div>
            <a class="chat-listing-title" href="{{ route('properties.show', $inquiry->listing) }}">{{ $inquiry->listing->title }}</a>
            <div class="chat-listing-meta">شروع گفتگو: {{ \App\Support\Fa::date($inquiry->created_at, 'Y/m/d') }}</div>
        </div>
        <span class="chat-state-pill {{ $inquiry->status === 'closed' ? 'closed' : '' }}">{{ ['new'=>'گفتگوی جدید','contacted'=>'در حال گفتگو','closed'=>'گفتگوی بسته‌شده'][$inquiry->status] ?? $inquiry->status }}</span>
    </section>

    <section class="chat-shell">
        <header class="chat-header">
            <div class="chat-partner-avatar">{{ mb_substr($chatPartner ?: '؟', 0, 1) }}</div>
            <div class="chat-partner-info">
                <div class="chat-partner-name">{{ $chatPartner }}</div>
                <div class="chat-partner-sub"><span class="chat-online-dot"></span>مکالمه درباره آگهی · پیام‌ها به‌صورت زنده به‌روزرسانی می‌شوند</div>
            </div>
            @if(!$isInitiator && $inquiry->sender_mobile)
                <a class="btn light" style="font-size:12px" href="tel:{{ $inquiry->sender_mobile }}">تماس</a>
            @endif
        </header>

        <div id="chat-thread" class="chat-thread" aria-live="polite" aria-relevant="additions">
            <article class="chat-message {{ $isInitiator ? 'mine' : 'theirs' }} chat-initial-message" data-message-id="0">
                <div class="chat-message-meta">{{ $inquiry->sender_name }} <span>·</span> پیام آغاز گفتگو</div>
                <div class="chat-message-body">{{ $inquiry->message }}</div>
                <div class="chat-message-time">{{ \App\Support\Fa::date($inquiry->created_at, 'Y/m/d H:i') }}</div>
                @if($inquiry->sender_mobile && !$isInitiator)
                    <div style="margin-top:9px;font-size:12px"><a href="tel:{{ $inquiry->sender_mobile }}">تماس با فرستنده: {{ $inquiry->sender_mobile }}</a></div>
                @endif
            </article>
            @foreach($messages as $message)
                <article class="chat-message {{ (int) $message->sender_user_id === (int) auth()->id() ? 'mine' : 'theirs' }}" data-message-id="{{ $message->id }}">
                    <div class="chat-message-meta">{{ $message->sender->display_name }} <span>·</span> {{ \App\Support\Fa::date($message->created_at, 'Y/m/d H:i') }}</div>
                    <div class="chat-message-body">{{ $message->body }}</div>
                    <div class="chat-message-time">{{ \App\Support\Fa::date($message->created_at, 'H:i') }}</div>
                </article>
            @endforeach
        </div>

        @if($inquiry->status !== 'closed')
            <div class="chat-composer-wrap">
                <form id="chat-composer" method="POST" action="{{ route('properties.chat.send', $inquiry) }}" data-poll-url="{{ route('properties.chat.messages', $inquiry) }}">
                    @csrf
                    <div class="chat-composer">
                        <label class="field">
                            <span class="sr-only">پیام جدید</span>
                            <textarea name="body" rows="2" maxlength="2000" required placeholder="پیام خود را اینجا بنویسید...">{{ old('body') }}</textarea>
                        </label>
                        <button class="chat-send-btn" type="submit">ارسال پیام <span aria-hidden="true">←</span></button>
                    </div>
                    @error('body')<p class="error">{{ $message }}</p>@enderror
                    <div class="chat-composer-hint">برای ارسال پیام، دکمه ارسال را بزنید. لطفاً اطلاعات حساس بانکی یا رمز عبور ارسال نکنید.</div>
                    <div id="chat-status" class="chat-status" role="status"></div>
                </form>
            </div>
        @else
            <div class="chat-closed">این گفتگو بسته شده است و امکان ارسال پیام جدید وجود ندارد.</div>
        @endif
    </section>
</div>
@if($inquiry->status !== 'closed')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('chat-composer');
    const thread = document.getElementById('chat-thread');
    const status = document.getElementById('chat-status');
    if (!form || !thread || !status) return;

    const textarea = form.querySelector('textarea[name="body"]');
    const submit = form.querySelector('button[type="submit"]');
    const csrf = form.querySelector('input[name="_token"]').value;
    const sendUrl = form.action;
    const pollUrl = form.dataset.pollUrl;
    let lastId = Math.max(0, ...Array.from(thread.querySelectorAll('[data-message-id]')).map(node => Number(node.dataset.messageId) || 0));
    let polling = false;

    function appendMessage(message) {
        if (thread.querySelector('[data-message-id="' + message.id + '"]')) return;
        const shouldStick = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 100;
        const article = document.createElement('article');
        article.className = 'chat-message ' + (Number(message.sender_user_id) === {{ (int) auth()->id() }} ? 'mine' : 'theirs');
        article.dataset.messageId = message.id;
        const meta = document.createElement('div');
        meta.className = 'chat-message-meta';
        meta.textContent = (message.sender_name || '') + ' · ' + (message.time || '');
        const body = document.createElement('div');
        body.className = 'chat-message-body';
        body.textContent = message.body || '';
        article.append(meta, body);
        thread.appendChild(article);
        lastId = Math.max(lastId, Number(message.id) || 0);
        if (shouldStick) thread.scrollTop = thread.scrollHeight;
    }

    async function pollMessages() {
        if (polling) return;
        polling = true;
        try {
            const response = await fetch(pollUrl + '?after_id=' + encodeURIComponent(lastId), {
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin'
            });
            if (!response.ok) return;
            const data = await response.json();
            (data.messages || []).forEach(appendMessage);
            if (data.closed) {
                status.textContent = 'این گفتگو بسته شده است.';
                textarea.disabled = true;
                submit.disabled = true;
            }
        } catch (error) {
            // Keep composing available during temporary network interruptions.
        } finally {
            polling = false;
        }
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const body = textarea.value.trim();
        if (!body || submit.disabled) return;
        submit.disabled = true;
        status.classList.remove('error');
        status.textContent = 'در حال ارسال پیام...';
        try {
            const response = await fetch(sendUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({body})
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = data.message || (data.errors && data.errors.body && data.errors.body[0]) || 'ارسال پیام انجام نشد. دوباره تلاش کنید.';
                throw new Error(message);
            }
            appendMessage(data.message);
            textarea.value = '';
            status.textContent = 'پیام با موفقیت ارسال شد.';
            textarea.focus();
        } catch (error) {
            status.classList.add('error');
            status.textContent = error.message || 'خطا در ارتباط با سرور.';
        } finally {
            submit.disabled = false;
        }
    });

    thread.scrollTop = thread.scrollHeight;
    pollMessages();
    window.setInterval(pollMessages, 4000);
});
</script>
@endif
@endsection
