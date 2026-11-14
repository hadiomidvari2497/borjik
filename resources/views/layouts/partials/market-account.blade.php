{{-- لینک‌های حساب کاربری بازار؛ هم در منوی کشویی دسکتاپ و هم در شیت موبایل --}}
<div class="acc-head">
    <span class="uav lg">{{ \Illuminate\Support\Str::substr($name ?: '؟', 0, 1) }}</span>
    <div><b>{{ $name ?: 'کاربر صابخونه' }}</b>@if($user->mobile)<small dir="ltr">{{ $user->mobile }}</small>@endif</div>
</div>
<div class="acc-links">
    <a href="{{ route('profile.edit') }}"><svg class="icon" aria-hidden="true"><use href="#i-user"/></svg>پروفایل من</a>
    <a href="{{ route('properties.mine') }}"><svg class="icon" aria-hidden="true"><use href="#i-list"/></svg>آگهی‌های من</a>
    <a href="{{ route('properties.favorites.index') }}"><svg class="icon" aria-hidden="true"><use href="#i-bookmark"/></svg>نشان‌شده‌ها</a>
    <a href="{{ route('properties.inquiries.index') }}"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>پیام‌ها @if($unread > 0)<span class="nbadge">{{ \App\Support\Fa::num(min($unread, 99)) }}</span>@endif</a>
    <a href="{{ route('home') }}"><svg class="icon" aria-hidden="true"><use href="#i-home"/></svg>صفحه اصلی سایت</a>
    <a href="{{ route('dashboard') }}"><svg class="icon" aria-hidden="true"><use href="#i-building"/></svg>پنل مدیریت ساختمان</a>
</div>
<form method="POST" action="{{ route('logout') }}" class="acc-out">@csrf
    <button type="submit"><svg class="icon" aria-hidden="true"><use href="#i-logout"/></svg>خروج از حساب</button>
</form>
