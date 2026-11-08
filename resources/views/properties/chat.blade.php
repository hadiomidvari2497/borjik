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
.chat-message-time{font-size:10px;color:#81938a;text-align:left;margin-top:4px;display:flex;align-items:center;justify-content:flex-start;gap:7px}
.chat-reply-preview{display:block;width:100%;text-align:right;padding:7px 10px;margin:0 0 8px;border:0;border-right:3px solid #279466;border-radius:8px;background:rgba(20,120,75,.07);font:inherit;font-size:11px;color:#587467;cursor:pointer;transition:background .2s,box-shadow .2s}
.chat-reply-preview:hover{background:rgba(20,120,75,.13)}
.chat-reply-preview strong{display:block;font-size:10px;margin-bottom:2px}
.chat-message.is-targeted{animation:chat-target-pulse 1.8s ease;border-radius:18px}
@keyframes chat-target-pulse{0%,100%{box-shadow:0 3px 12px rgba(20,45,32,.045)}25%,65%{box-shadow:0 0 0 4px rgba(33,149,101,.28),0 8px 24px rgba(20,120,75,.15)}}
.chat-reply-btn{border:0;background:transparent;color:#287c59;font-size:10px;font-weight:800;cursor:pointer;padding:3px 0;margin-top:3px}
.chat-read-receipt{font-size:13px;letter-spacing:-3px;color:#87968e;direction:ltr}
.chat-read-receipt.is-read{color:#168bce}
.chat-reply-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 12px;margin-bottom:10px;border-right:3px solid #21966a;border-radius:10px;background:#f1f8f4;font-size:11px;color:#315d48}
.chat-reply-bar button{flex:0 0 30px;width:30px;height:30px;display:grid;place-items:center;border:0;border-radius:8px;background:transparent;color:#68766f;font-size:20px;line-height:1;cursor:pointer}.chat-reply-bar button:hover{background:rgba(0,0,0,.06)}.chat-reply-bar[hidden]{display:none!important}
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

/* Messaging-app visual refresh */
.chat-page{max-width:1040px;padding-bottom:42px}
.chat-topbar{margin-bottom:18px}
.chat-back{padding:9px 13px;border:1px solid var(--line);border-radius:12px;background:var(--panel,#fff);transition:all .18s}
.chat-back:hover{background:#edf7f1;border-color:#c9e5d4}
.chat-topbar>.muted{display:inline-flex;align-items:center;gap:7px;padding:8px 12px;border-radius:30px;background:#edf7f1;color:#367456!important}
.chat-topbar>.muted:before{content:"";width:7px;height:7px;border-radius:50%;background:#249768}
.chat-listing-card{position:relative;padding:13px 17px;margin-bottom:12px;border-radius:16px;background:linear-gradient(110deg,#f4faf6,#fff 70%);box-shadow:none}
.chat-listing-label{display:flex;align-items:center;gap:7px;font-weight:700;letter-spacing:.1px}
.chat-listing-label:before{content:"";width:4px;height:14px;border-radius:4px;background:#279466}
.chat-listing-title{font-size:14px}
.chat-state-pill{padding:6px 10px;font-weight:750}
.chat-shell{border:1px solid #e2ebe5;border-radius:22px;background:#fff;box-shadow:0 18px 55px rgba(22,65,43,.09);display:flex;flex-direction:column}
.chat-header{position:relative;padding:16px 21px;background:#fff;border-bottom:1px solid #eaf0ec}
.chat-partner-avatar{width:48px;height:48px;flex-basis:48px;border-radius:50%;border:3px solid #f1f8f3;box-shadow:0 0 0 1px #dcece2;font-size:19px}
.chat-partner-name{font-size:15px;letter-spacing:-.2px}
.chat-partner-sub{display:flex;align-items:center;gap:6px;color:#788980}
.chat-online-dot{width:7px;height:7px;background:#269568;margin:0}
.chat-header>.btn{border-radius:11px;padding:9px 13px}
.chat-thread{height:min(62vh,680px);min-height:360px;gap:17px;padding:25px clamp(16px,5vw,54px);background-color:#f8fbf9;background-image:radial-gradient(#dce9e0 0.7px,transparent .7px);background-size:18px 18px;scrollbar-width:thin;scrollbar-color:#c8d9ce transparent}
.chat-message{max-width:min(76%,610px);padding:11px 15px 8px;border:1px solid transparent;border-radius:18px;box-shadow:0 3px 10px rgba(24,57,39,.055);transition:box-shadow .18s,transform .18s}
.chat-message:hover{box-shadow:0 7px 20px rgba(24,57,39,.09)}
.chat-message.mine{background:#e1f3e7;border-color:#d5eadc;border-bottom-right-radius:5px}
.chat-message.theirs{background:#fff;border-color:#e8eeea;border-bottom-left-radius:5px}
.chat-message-meta{font-size:10px;font-weight:750;color:#698174;margin-bottom:6px}
.chat-message-body{font-size:13px;line-height:2;color:#263c30}
.chat-message-time{font-variant-numeric:tabular-nums;font-size:10px;color:#84958b;margin-top:6px}
.chat-read-receipt{font-size:14px;font-weight:800;letter-spacing:-4px;margin-left:3px}
.chat-read-receipt.is-read{color:#1594d0}
.chat-reply-preview{border-right-width:3px;border-radius:7px 10px 10px 7px;background:rgba(28,132,83,.08);color:#456b57}
.chat-reply-btn{opacity:.72;border-radius:7px;padding:4px 8px;margin-top:5px;transition:background .18s,opacity .18s}
.chat-reply-btn:hover{opacity:1;background:rgba(25,126,78,.1)}
.chat-composer-wrap{position:relative;padding:14px 18px 12px;background:#fff;border-top:1px solid #eaf0ec;border-radius:0 0 22px 22px}
.chat-composer{gap:11px}
.chat-composer textarea{min-height:50px;max-height:140px;resize:none;border:1px solid #dfe9e2;border-radius:16px;background:#f8fbf9;padding:12px 15px;color:#233a2d;transition:border-color .18s,box-shadow .18s}
.chat-composer textarea::placeholder{color:#9aa9a0}
.chat-composer textarea:focus{background:#fff;border-color:#60a982;box-shadow:0 0 0 4px rgba(39,143,91,.09)}
.chat-send-btn{min-width:118px;min-height:49px;border-radius:14px;background:linear-gradient(135deg,#126b49,#249766);box-shadow:0 5px 14px rgba(24,126,79,.18);transition:transform .18s,box-shadow .18s}
.chat-send-btn:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 8px 19px rgba(24,126,79,.25)}
.chat-composer-hint{display:flex;align-items:center;gap:6px;margin:7px 3px 0;color:#87968d}
.chat-composer-hint:before{content:"";width:5px;height:5px;border-radius:50%;background:#9bb6a5}
.chat-status{min-height:15px}
.chat-reply-bar{border-radius:12px;background:#f0f8f3}
.chat-closed{border-radius:0 0 22px 22px;background:#f8faf8}
@media(min-width:900px){.chat-shell{min-height:590px}.chat-thread{height:min(62vh,680px)}}
@media(max-width:600px){
 .chat-page{padding-bottom:20px}
 .chat-listing-card{padding:12px 13px;border-radius:14px;align-items:flex-start}
 .chat-listing-meta{font-size:10px}
 .chat-shell{border-radius:17px}
 .chat-header{padding:13px}
 .chat-partner-avatar{width:42px;height:42px;flex-basis:42px}
 .chat-partner-sub{font-size:10px;line-height:1.6}
 .chat-thread{height:58vh;min-height:300px;padding:17px 12px;gap:14px}
 .chat-message{max-width:91%;padding:10px 12px}
 .chat-composer-wrap{padding:11px 12px;border-radius:0 0 17px 17px}
 .chat-composer{align-items:stretch;flex-direction:row}
 .chat-composer .field{flex:1}
 .chat-composer textarea{min-height:48px}
 .chat-send-btn{width:auto;min-width:78px;min-height:48px;padding:0 13px;white-space:nowrap}
 .chat-topbar{margin-bottom:10px}
 .chat-topbar>.muted{font-size:10px}
}
@media(max-width:380px){.chat-composer{flex-direction:column}.chat-send-btn{width:100%}.chat-listing-card{gap:8px}.chat-state-pill{font-size:10px}}
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
                <div class="chat-partner-sub"><span class="chat-online-dot"></span>مکالمه درباره آگهی · ارتباط امن در صابخونه</div>
            </div>
            @if(!$isInitiator && $inquiry->sender_mobile)
                <a class="btn light" style="font-size:12px" href="tel:{{ $inquiry->sender_mobile }}">تماس</a>
            @endif
        </header>

        <div id="chat-thread" class="chat-thread" aria-live="polite" aria-relevant="additions">
            <article class="chat-message {{ $isInitiator ? 'mine' : 'theirs' }} chat-initial-message" data-message-id="0">
                <div class="chat-message-meta">{{ $inquiry->sender_name }} <span>·</span> پیام آغاز گفتگو</div>
                <div class="chat-message-body">{{ $inquiry->message }}</div>
                <div class="chat-message-time">{{ \App\Support\Fa::date($inquiry->created_at, 'Y/m/d H:i') }} @if($isInitiator)<span class="chat-read-receipt {{ $inquiry->read_at ? 'is-read' : '' }}" id="initial-read-receipt" title="{{ $inquiry->read_at ? 'خوانده شده' : 'ارسال شده' }}">✓{{ $inquiry->read_at ? '✓' : '' }}</span>@endif</div>
                @if($inquiry->sender_mobile && !$isInitiator)
                    <div style="margin-top:9px;font-size:12px"><a href="tel:{{ $inquiry->sender_mobile }}">تماس با فرستنده: {{ $inquiry->sender_mobile }}</a></div>
                @endif
            </article>
            @foreach($messages as $message)
                <article class="chat-message {{ (int) $message->sender_user_id === (int) auth()->id() ? 'mine' : 'theirs' }}" data-message-id="{{ $message->id }}">
                    <div class="chat-message-meta">{{ $message->sender->display_name }} <span>·</span> {{ \App\Support\Fa::date($message->created_at, 'Y/m/d H:i') }}</div>
                    <div class="chat-message-body">{{ $message->body }}</div>
                    @if($message->replyTo)
                        <button type="button" class="chat-reply-preview" data-jump-to="{{ $message->replyTo->id }}" aria-label="رفتن به پیام اصلی">
                            <strong>در پاسخ به {{ $message->replyTo->sender?->display_name ?? 'کاربر' }}</strong>{{ \Illuminate\Support\Str::limit($message->replyTo->body, 100) }}
                        </button>
                    @endif
                    <div class="chat-message-time">{{ \App\Support\Fa::date($message->created_at, 'H:i') }} @if((int) $message->sender_user_id === (int) auth()->id())<span class="chat-read-receipt {{ $message->read_at ? 'is-read' : '' }}" data-receipt-id="{{ $message->id }}" title="{{ $message->read_at ? 'خوانده شده' : 'ارسال شده' }}">✓{{ $message->read_at ? '✓' : '' }}</span>@endif</div>
                    <button type="button" class="chat-reply-btn" data-reply-id="{{ $message->id }}" data-reply-sender="{{ $message->sender?->display_name ?? '' }}" data-reply-body="{{ $message->body }}">↩ پاسخ</button>
                </article>
            @endforeach
        </div>

        @if($inquiry->status !== 'closed')
            <div class="chat-composer-wrap">
                <form id="chat-composer" method="POST" action="{{ route('properties.chat.send', $inquiry) }}" data-poll-url="{{ route('properties.chat.messages', $inquiry) }}">
                    @csrf
                    <div id="chat-reply-bar" class="chat-reply-bar" hidden><span id="chat-reply-label"></span><button type="button" id="chat-reply-cancel" aria-label="لغو پاسخ">×</button></div>
                    <input type="hidden" name="reply_to_message_id" value="">
                    <div class="chat-composer">
                        <label class="field">
                            <span class="sr-only">پیام جدید</span>
                            <textarea name="body" rows="2" maxlength="2000" required placeholder="پیام خود را اینجا بنویسید...">{{ old('body') }}</textarea>
                        </label>
                        <button class="chat-send-btn" type="submit">ارسال پیام <span aria-hidden="true">←</span></button>
                    </div>
                    @error('body')<p class="error">{{ $message }}</p>@enderror
                    <div class="chat-composer-hint">پیام‌های شما فقط برای طرفین این گفتگو قابل مشاهده است. از ارسال اطلاعات حساس خودداری کنید.</div>
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
    const replyInput = form.querySelector('input[name="reply_to_message_id"]');
    const replyBar = document.getElementById('chat-reply-bar');
    const replyLabel = document.getElementById('chat-reply-label');

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
        article.append(meta);
        if (message.reply) {
            const quote = document.createElement('button');
            quote.type = 'button';
            quote.className = 'chat-reply-preview';
            quote.dataset.jumpTo = message.reply.id;
            quote.setAttribute('aria-label', 'رفتن به پیام اصلی');
            const quoteTitle = document.createElement('strong');
            quoteTitle.textContent = 'در پاسخ به ' + (message.reply.sender_name || 'کاربر');
            quote.append(quoteTitle, document.createTextNode(message.reply.body || ''));
            article.appendChild(quote);
        }
        article.appendChild(body);
        const time = document.createElement('div');
        time.className = 'chat-message-time';
        time.textContent = message.time || '';
        if (Number(message.sender_user_id) === {{ (int) auth()->id() }}) {
            const receipt = document.createElement('span');
            receipt.className = 'chat-read-receipt';
            receipt.dataset.receiptId = message.id;
            receipt.textContent = '✓';
            time.appendChild(receipt);
        }
        article.appendChild(time);
        const replyButton = document.createElement('button');
        replyButton.type = 'button';
        replyButton.className = 'chat-reply-btn';
        replyButton.dataset.replyId = message.id;
        replyButton.dataset.replySender = message.sender_name || '';
        replyButton.dataset.replyBody = message.body || '';
        replyButton.textContent = '↩ پاسخ';
        article.appendChild(replyButton);
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
            (data.read_message_ids || []).forEach(id => {
                const receipt = thread.querySelector('[data-receipt-id="' + id + '"]');
                if (receipt) { receipt.textContent = '✓✓'; receipt.classList.add('is-read'); receipt.title = 'خوانده شده'; }
            });
            const initialReceipt = document.getElementById('initial-read-receipt');
            if (initialReceipt && data.initial_message_read) { initialReceipt.textContent = '✓✓'; initialReceipt.classList.add('is-read'); initialReceipt.title = 'خوانده شده'; }
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

    function clearReplyTarget() {
        replyInput.value = '';
        replyBar.hidden = true;
        replyLabel.textContent = '';
    }

    thread.addEventListener('click', event => {
        const jump = event.target.closest('[data-jump-to]');
        if (jump) {
            const target = thread.querySelector('[data-message-id="' + jump.dataset.jumpTo + '"]');
            if (target) {
                target.scrollIntoView({behavior: 'smooth', block: 'center'});
                target.classList.remove('is-targeted');
                requestAnimationFrame(() => target.classList.add('is-targeted'));
                window.setTimeout(() => target.classList.remove('is-targeted'), 1900);
            }
            return;
        }

        const button = event.target.closest('[data-reply-id]');
        if (!button) return;
        replyInput.value = button.dataset.replyId;
        replyLabel.textContent = 'پاسخ به ' + (button.dataset.replySender || 'کاربر') + ': ' + (button.dataset.replyBody || '').slice(0, 110);
        replyBar.hidden = false;
        textarea.focus();
    });

    const cancelReplyButton = document.getElementById('chat-reply-cancel');
    cancelReplyButton?.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        clearReplyTarget();
        textarea.focus();
    });

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
                body: JSON.stringify({body, reply_to_message_id: replyInput.value || null})
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = data.message || (data.errors && data.errors.body && data.errors.body[0]) || 'ارسال پیام انجام نشد. دوباره تلاش کنید.';
                throw new Error(message);
            }
            appendMessage(data.message);
            textarea.value = '';
            clearReplyTarget();
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
