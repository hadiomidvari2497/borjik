@extends('admin.layout')
@section('title', 'مرکز سئو')
@section('content')
<div class="card">
  <div class="page-h"><h2>مرکز سئو و آمادگی انتشار</h2><a class="btn secondary" href="{{ route('admin.settings.edit') }}">تنظیمات سایت</a></div>
  <p style="color:var(--muted);line-height:1.9">این بخش تنظیمات پایهٔ سئوی صفحات عمومی صابخونه را مدیریت می‌کند. تا زمانی که دامنهٔ واقعی و HTTPS آماده نشده‌اند، ایندکس عمومی را فعال نکن.</p>
  <div class="fgrid" style="margin:18px 0">
    <div class="card"><strong>دامنهٔ عمومی</strong><p style="margin-top:8px;overflow-wrap:anywhere">{{ $configuredUrl }}</p><span class="badge {{ $isPublicUrl ? 'success' : 'warning' }}">{{ $isPublicUrl ? 'آدرس عمومی شناسایی شد' : 'هنوز دامنهٔ عمومی تنظیم نشده' }}</span></div>
    <div class="card"><strong>مقالات منتشرشده</strong><p style="font-size:28px;font-weight:800;margin-top:8px">{{ \App\Support\Fa::num($publishedArticles) }}</p><small style="color:var(--muted)">مقاله در نقشهٔ سایت عمومی</small></div>
    <div class="card"><strong>وضعیت ایندکس</strong><p style="margin-top:8px">{{ $settings['indexing_enabled'] && $isPublicUrl && app()->environment('production') ? 'آمادهٔ اجازهٔ ایندکس' : 'فعلاً محافظت‌شده' }}</p><small style="color:var(--muted)">در محیط لوکال، ربات‌ها مسدود می‌مانند.</small></div>
  </div>
  <h3 style="margin:18px 0 8px">چک‌لیست انتشار</h3>
  <ul style="line-height:2.1;padding-right:22px;color:var(--muted)">
    <li>دامنهٔ واقعی و گواهی HTTPS را روی هاست فعال کن.</li>
    <li>آدرس نهایی دامنه را در فیلد زیر ثبت کن؛ از localhost استفاده نکن.</li>
    <li>پس از انتشار، ایندکس را فعال و مالکیت دامنه را در Google Search Console تأیید کن.</li>
    <li>نقشهٔ سایت در <code>/sitemap.xml</code> و دستورهای خزنده در <code>/robots.txt</code> ارائه می‌شوند.</li>
    <li>این ابزار تنظیمات فنی پایه را فراهم می‌کند؛ رتبهٔ گوگل تضمین‌شدنی نیست و به محتوا، رقابت و تجربهٔ کاربری هم بستگی دارد.</li>
  </ul>
  <form method="POST" action="{{ route('admin.seo.update') }}" style="margin-top:22px">
    @csrf @method('PUT')
    <div class="fgrid">
      <div class="fld"><label for="site_url">آدرس اصلی سایت (Canonical Base URL)</label><input id="site_url" name="site_url" type="url" dir="ltr" placeholder="https://example.com" value="{{ old('site_url', $settings['site_url']) }}"><small style="color:var(--muted)">بعد از خرید دامنه وارد کن؛ بدون اسلش انتهایی بهتر است.</small>@error('site_url')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="title">عنوان پیش‌فرض سئو *</label><input id="title" name="title" maxlength="70" required value="{{ old('title', $settings['title']) }}">@error('title')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld" style="grid-column:1/-1"><label for="description">توضیحات پیش‌فرض متا *</label><textarea id="description" name="description" rows="3" maxlength="320" required>{{ old('description', $settings['description']) }}</textarea><small style="color:var(--muted)">برای صفحهٔ اصلی و صفحات عمومی؛ توضیح هر مقاله از متن همان مقاله ساخته می‌شود.</small>@error('description')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld" style="grid-column:1/-1"><label for="google_verification">کد تأیید Google Search Console (اختیاری)</label><input id="google_verification" name="google_verification" dir="ltr" maxlength="200" value="{{ old('google_verification', $settings['google_verification']) }}" placeholder="content value فقط، نه کل تگ meta"></div>
    </div>
    <label style="display:flex;gap:10px;align-items:flex-start;margin-top:18px"><input type="checkbox" name="indexing_enabled" value="1" style="width:auto;margin-top:5px" @checked(old('indexing_enabled', $settings['indexing_enabled']))><span><strong>اجازهٔ ایندکس سایت عمومی</strong><small style="display:block;color:var(--muted)">فقط وقتی فعال کن که سایت روی دامنهٔ واقعی و محیط production منتشر شده باشد.</small></span></label>
    <div class="fbar"><button class="btn" type="submit">ذخیره تنظیمات سئو</button></div>
  </form>
</div>
@endsection
