@extends('layouts.marketplace')
@section('title', 'گفتگو درباره آگهی')
@section('content')
<style>
.chat-thread{display:flex;flex-direction:column;gap:12px;max-height:62vh;overflow-y:auto;padding:4px 2px 14px;scroll-behavior:smooth}
.chat-message{max-width:min(85%,620px);padding:12px 16px;border-radius:16px;line-height:1.9;overflow-wrap:anywhere}
.chat-message.mine{background:#eaf5ef;align-self:flex-start;border-bottom-right-radius:5px}
.chat-message.theirs{background:#f2f5f3;align-self:flex-end;border-bottom-left-radius:5px}
.chat-message-meta{font-size:11px;color:#7b8d86;margin-bottom:5px}
.chat-composer{display:flex;align-items:flex-end;gap:10px;margin-top:18px}
.chat-composer .field{flex:1;margin:0}
.chat-composer textarea{min-height:52px;resize:vertical}
.chat-status{font-size:12px;color:#7b8d86;min-height:20px;margin-top:8px}
.chat-status.error{color:#b42318}
@media(max-width:600px){.chat-message{max-width:94%}.chat-composer{align-items:stretch;flex-direction:column}.chat-composer .btn{width:100%}}
</style>
<section class="hero">
    <h1>گفتگو درباره آگهی</h1>
    <p><a href="{{ route('properties.show', $inquiry->listing) }}">{{ $inquiry->listing->title }}</a></p>
    <div class="muted">طرف گفتگو: {{ (int) auth()->id() === (int) $inquiry->sender_user_id ? $inquiry->recipient->display_name : $inquiry->sender_name }}</div>
</section>
@if(session('status'))<div class="notice" style="margin-top:14px">{{ session('status') }}</div>@endif
<section class="panel" style="margin-top:18px">
    <div id="chat-thread" class="chat-thread" aria-live="polite" aria-relevant="additions">
        <article class="chat-message {{ (int) auth()->id() === (int) $inquiry->sender_user_id ? 'mine' : 'theirs' }}" data-message-id="0">
            <div class="chat-message-meta">{{ $inquiry->sender_name }} · {{ \App\Support\Fa::date($inquiry->created_at, 'Y/m/d H:i') }}</div>
            <div style="white-space:pre-wrap">{{ $inquiry->message }}</div>
            @if($inquiry->sender_mobile && (int) auth()->id() === (int) $inquiry->recipient_user_id)
                <div style="margin-top:8px"><a href="tel:{{ $inquiry->sender_mobile }}">تماس با فرستنده: {{ $inquiry->sender_mobile }}</a></div>
            @endif
        </article>
        @foreach($messages as $message)
            <article class="chat-message {{ (int) $message->sender_user_id === (int) auth()->id() ? 'mine' : 'theirs' }}" data-message-id="{{ $message->id }}">
                <div class="chat-message-meta">{{ $message->sender->display_name }} · {{ \App\Support\Fa::date($message->created_at, 'Y/m/d H:i') }}</div>
                <div style="white-space:pre-wrap">{{ $message->body }}</div>
            </article>
        @endforeach
    </div>
    @if($inquiry->status !== 'closed')
        <form id="chat-composer" method="POST" action="{{ route('properties.chat.send', $inquiry) }}" data-poll-url="{{ route('properties.chat.messages', $inquiry) }}">
            @csrf
            <div class="chat-composer">
                <label class="field">پیام جدید
                    <textarea name="body" rows="2" maxlength="2000" required placeholder="پیام خود را بنویسید...">{{ old('body') }}</textarea>
                </label>
                <button class="btn" type="submit">ارسال پیام</button>
            </div>
            @error('body')<p class="error">{{ $message }}</p>@enderror
            <div id="chat-status" class="chat-status" role="status"></div>
        </form>
    @else
        <div class="notice" style="margin-top:18px">این گفتگو بسته شده است و امکان ارسال پیام جدید وجود ندارد.</div>
    @endif
</section>
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
        body.style.whiteSpace = 'pre-wrap';
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
            // A temporary network issue should not interrupt composing a message.
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
        status.textContent = 'در حال ارسال...';
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
            status.textContent = 'پیام ارسال شد.';
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
