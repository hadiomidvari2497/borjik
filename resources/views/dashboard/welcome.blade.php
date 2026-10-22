@extends('layouts.app')
@section('title', 'خوش آمدید')
@section('content')
@php
  $features = [
    ['bld', 'مدیریت ساختمان و واحدها', 'بلوک، واحد، پارکینگ و انباری، همراه با مالک و مستأجر هر واحد و تاریخچه‌ی کامل.'],
    ['coin', 'محاسبه‌ی خودکار شارژ', '۱۰ روش استاندارد (واحدی، متراژی، نفری، ترکیبی و هزینه‌ی واقعی) با صدور صورت‌حساب و یادآوری پیامکی.'],
    ['card', 'پرداخت و تأیید فیش', 'ساکنین فیش را بارگذاری می‌کنند، مدیر تأیید می‌کند و وضعیت مالی هر واحد خودکار به‌روز می‌شود.'],
    ['kan', 'تیکت و تسک', 'ارجاع کار به نظافت، حراست و تعمیرات با نمای کانبان، مهلت و امتیازدهی.'],
    ['inbox', 'درخواست‌های ساکنین', 'ساکنین مستقیم از پنل درخواست ثبت می‌کنند و پیگیری وضعیت را می‌بینند.'],
    ['cal', 'تقویم، اعلان و نظرسنجی', 'رویدادهای ساختمان، اعلان درون‌سامانه‌ای و پیامک، و نظرسنجی بین مالکین و ساکنین.'],
    ['folder', 'اسناد و دفترچه تلفن', 'بایگانی اسناد با سطح دسترسی هر نقش و دفترچه‌ی تلفن ساختمان.'],
    ['poll', 'گزارش‌ها و تاریخچه', 'گزارش وصول، بدهی و عملکرد پرسنل، به‌همراه ثبت کامل تغییرات.'],
  ];
@endphp

<div class="card hero">
  <h2>به صابخونه خوش آمدید{{ auth()->user()->display_name ? '، '.auth()->user()->display_name : '' }}</h2>
  <p>
    @if($pendingInvites)
      دعوت‌نامه‌ی یک ساختمان برای شما ثبت شده است. از صفحه‌ی دعوت‌نامه‌ها می‌توانید آن را بپذیرید یا رد کنید.
    @else
      شما هنوز عضو هیچ ساختمانی نیستید. می‌توانید امکانات زیر را ببینید؛ برای کار با آن‌ها ابتدا اشتراک تهیه کنید و ساختمان بسازید، یا دعوت‌نامه‌ی مدیر ساختمان را بپذیرید.
    @endif
  </p>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
    <a class="btn sec" href="{{ route('invitations.index') }}">مشاهده‌ی دعوت‌نامه‌ها</a>
    @if($subscription)
      <a class="btn" href="{{ route('buildings.create') }}">ساخت ساختمان</a>
      <a class="btn sec" href="{{ route('subscription.show') }}">مدیریت اشتراک</a>
      <span class="btn sec" style="cursor:default">اشتراک فعال: {{ \App\Support\Fa::num($subscription->units_limit) }} واحد تا {{ \App\Support\Fa::date($subscription->expires_at, 'Y/m/d') }}</span>
    @else
      <a class="btn" href="{{ route('subscription.show') }}">تهیه‌ی اشتراک</a>
      <span class="btn sec" style="cursor:default">برای ساخت ساختمان اشتراک لازم است</span>
    @endif
  </div>
</div>

<div id="features">
  <div class="page-h" style="margin:4px 0 12px"><h2>امکانات صابخونه</h2><span class="pill">با اشتراک فعال می‌شود</span></div>
  <div class="feat">
    @foreach($features as [$icon, $title, $text])
      <div class="card"><div class="ic"><svg class="i"><use href="#{{ $icon }}"/></svg></div><b>{{ $title }}</b><p>{{ $text }}</p></div>
    @endforeach
  </div>
</div>
@endsection
