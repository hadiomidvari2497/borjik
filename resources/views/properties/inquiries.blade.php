@extends('layouts.marketplace')
@section('title', 'پیام‌ها و گفتگوهای آگهی')
@section('content')
<style>
.inbox-page{max-width:1080px;margin:0 auto;padding-bottom:36px}
.inbox-hero{position:relative;overflow:hidden;padding:30px;border-radius:24px;background:linear-gradient(125deg,#123f32 0%,#17664b 58%,#24845d 100%);color:#fff;box-shadow:0 18px 42px rgba(20,83,59,.16)}
.inbox-hero:after{content:"";position:absolute;width:260px;height:260px;border:1px solid rgba(255,255,255,.13);border-radius:50%;left:-65px;top:-145px;box-shadow:0 0 0 28px rgba(255,255,255,.035),0 0 0 58px rgba(255,255,255,.025)}
.inbox-hero-inner{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.inbox-kicker{font-size:12px;letter-spacing:.04em;color:#c7e8d8;margin-bottom:8px}
.inbox-hero h1{font-size:clamp(23px,3vw,32px);margin:0 0 8px;font-weight:850;color:#fff}
.inbox-hero p{margin:0;color:#d7eee3;line-height:1.9}
.inbox-hero .btn{background:#fff;color:#175b43;border:0}
.inbox-summary{display:flex;gap:10px;margin:18px 0}
.inbox-stat{background:var(--panel,#fff);border:1px solid var(--line);border-radius:16px;padding:13px 18px;min-width:130px;box-shadow:0 5px 18px rgba(15,40,30,.035)}
.inbox-stat strong{display:block;font-size:22px;color:var(--text);line-height:1.4}
.inbox-stat span{font-size:12px;color:var(--muted)}
.inbox-panel{border:1px solid var(--line);border-radius:22px;background:var(--panel,#fff);padding:18px;box-shadow:0 12px 34px rgba(18,50,36,.055)}
.inbox-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.inbox-toolbar h2{font-size:17px;margin:0}
.inbox-search{position:relative;flex:1;max-width:340px}
.inbox-search input{width:100%;border:1px solid var(--line);border-radius:12px;padding:11px 40px 11px 13px;background:var(--surface,#fff);color:var(--text)}
.inbox-search:before{content:"⌕";position:absolute;right:14px;top:3px;font-size:25px;color:var(--muted)}
.inbox-item{display:flex;align-items:center;gap:14px;padding:17px 12px;border-top:1px solid var(--line);text-decoration:none;color:inherit;border-radius:13px;transition:background .18s,transform .18s}
.inbox-item:hover{background:rgba(31,128,88,.055);transform:translateY(-1px)}
.inbox-item.is-unread{background:rgba(31,128,88,.055);border-right:3px solid #21966a;padding-right:9px}
.inbox-avatar{width:48px;height:48px;flex:0 0 48px;border-radius:16px;display:grid;place-items:center;background:linear-gradient(145deg,#e4f4eb,#cce9d9);color:#176343;font-weight:850;font-size:17px}
.inbox-main{min-width:0;flex:1}
.inbox-line{display:flex;align-items:center;justify-content:space-between;gap:10px}
.inbox-person{font-weight:800;color:var(--text);font-size:14px}
.inbox-time{font-size:11px;color:var(--muted);white-space:nowrap}
.inbox-listing{font-size:12px;color:var(--muted);margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.inbox-preview{font-size:13px;color:var(--muted);margin-top:7px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.inbox-item.is-unread .inbox-person,.inbox-item.is-unread .inbox-preview{color:var(--text);font-weight:750}
.inbox-trailing{display:flex;align-items:center;gap:9px;flex-direction:column}
.inbox-unread{min-width:23px;height:23px;padding:0 7px;border-radius:20px;background:#168653;color:white;font-size:11px;font-weight:800;display:grid;place-items:center;box-shadow:0 3px 9px rgba(22,134,83,.2)}
.inbox-state{font-size:10px;padding:5px 9px;border-radius:20px;background:var(--surface,#f5f7f6);color:var(--muted);white-space:nowrap}
.inbox-state.state-new{background:#fff4d8;color:#946200}
.inbox-empty{text-align:center;padding:52px 18px;color:var(--muted)}
.inbox-empty-icon{width:64px;height:64px;border-radius:22px;margin:0 auto 14px;display:grid;place-items:center;background:#eaf5ee;color:#176343;font-size:28px}
.inbox-empty strong{display:block;color:var(--text);font-size:16px;margin-bottom:7px}
.inbox-pagination{padding-top:18px}
@media(max-width:600px){.inbox-hero{padding:23px 20px;border-radius:19px}.inbox-summary{gap:8px}.inbox-stat{min-width:0;flex:1;padding:11px 13px}.inbox-panel{padding:12px}.inbox-item{gap:10px;padding:14px 5px}.inbox-avatar{width:42px;height:42px;flex-basis:42px;border-radius:14px}.inbox-time{font-size:10px}.inbox-state{display:none}.inbox-search{max-width:none;flex-basis:100%}}
</style>
<div class="inbox-page">
    <section class="inbox-hero">
        <div class="inbox-hero-inner">
            <div>
                <div class="inbox-kicker">مرکز ارتباطات بازار املاک صابخونه</div>
                <h1>پیام‌ها و گفتگوها</h1>
                <p>همه مکالمه‌های شما با خریداران و فروشندگان، یک‌جا و مرتب.</p>
            </div>
            <a class="btn" href="{{ route('properties.mine') }}">مدیریت آگهی‌های من</a>
        </div>
    </section>

    @php
        $unreadTotal = $inquiries->getCollection()->sum(fn ($item) => (int) $item->unread_messages_count + ((int) auth()->id() === (int) $item->recipient_user_id && !$item->read_at ? 1 : 0));
    @endphp
    <div class="inbox-summary">
        <div class="inbox-stat"><strong>{{ $inquiries->total() }}</strong><span>کل گفتگوها</span></div>
        <div class="inbox-stat"><strong>{{ $unreadTotal }}</strong><span>پیام خوانده‌نشده در این صفحه</span></div>
    </div>

    @if(session('status'))<div class="notice" style="margin:0 0 16px">{{ session('status') }}</div>@endif
    <section class="inbox-panel">
        <div class="inbox-toolbar">
            <h2>صندوق گفتگوها</h2>
            <label class="inbox-search"><input id="inbox-search" type="search" placeholder="جستجو در نام یا عنوان آگهی..." autocomplete="off"></label>
        </div>
        @forelse($inquiries as $inquiry)
            @php
                $isOwner = (int) auth()->id() === (int) $inquiry->recipient_user_id;
                $unreadCount = (int) $inquiry->unread_messages_count + ($isOwner && !$inquiry->read_at ? 1 : 0);
                $person = $isOwner ? $inquiry->sender_name : ($inquiry->recipient->display_name ?? 'فروشنده');
                $preview = $inquiry->messages_max_created_at ? 'آخرین پیام در گفتگو' : $inquiry->message;
                $searchText = mb_strtolower($person.' '.$inquiry->listing->title);
            @endphp
            <a class="inbox-item {{ $unreadCount ? 'is-unread' : '' }}" href="{{ route('properties.chat.show', $inquiry) }}" data-search="{{ $searchText }}">
                <div class="inbox-avatar">{{ mb_substr($person ?: '؟', 0, 1) }}</div>
                <div class="inbox-main">
                    <div class="inbox-line">
                        <span class="inbox-person">{{ $person }}</span>
                        <span class="inbox-time">{{ \\App\\Support\\Fa::date($inquiry->updated_at, 'Y/m/d H:i') }}</span>
                    </div>
                    <div class="inbox-listing">درباره آگهی: {{ $inquiry->listing->title }}</div>
                    <div class="inbox-preview">{{ \\Illuminate\\Support\\Str::limit($preview, 105) }}</div>
                </div>
                <div class="inbox-trailing">
                    @if($unreadCount)
                        <span class="inbox-unread" title="{{ $unreadCount }} پیام خوانده‌نشده">{{ $unreadCount > 99 ? '۹۹+' : $unreadCount }}</span>
                    @endif
                    <span class="inbox-state {{ $inquiry->status === 'new' ? 'state-new' : '' }}">{{ ['new'=>'جدید','contacted'=>'در حال گفتگو','closed'=>'بسته‌شده'][$inquiry->status] ?? $inquiry->status }}</span>
                </div>
            </a>
            @if($isOwner && $inquiry->sender_mobile)
                <div style="text-align:left;margin:-5px 12px 9px"><a class="muted" style="font-size:11px" href="tel:{{ $inquiry->sender_mobile }}">تماس با فرستنده · {{ $inquiry->sender_mobile }}</a></div>
            @endif
        @empty
            <div class="inbox-empty">
                <div class="inbox-empty-icon">✉</div>
                <strong>هنوز گفتگویی ندارید</strong>
                <span>از صفحه هر آگهی می‌توانید با خریدار یا فروشنده وارد گفتگو شوید.</span>
                <div style="margin-top:18px"><a class="btn" href="{{ route('properties.index') }}">مشاهده آگهی‌ها</a></div>
            </div>
        @endforelse
        <div id="inbox-no-results" class="inbox-empty" hidden>گفتگویی با این مشخصات پیدا نشد.</div>
        <div class="inbox-pagination">{{ $inquiries->links() }}</div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('inbox-search');
    const items = Array.from(document.querySelectorAll('.inbox-item'));
    const empty = document.getElementById('inbox-no-results');
    if (!search) return;
    search.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        items.forEach(item => {
            const match = (item.dataset.search || '').toLocaleLowerCase().includes(query);
            item.hidden = !match;
            if (match) visible++;
        });
        if (empty) empty.hidden = visible > 0 || items.length === 0;
    });
});
</script>
@endsection
