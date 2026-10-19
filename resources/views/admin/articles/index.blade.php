@extends('admin.layout')
@section('title', 'مدیریت مقالات')
@section('content')
<div class="card">
<div class="page-h"><div><h2>مقالات سایت</h2><p style="color:var(--muted);margin:4px 0 0">مقاله‌های منتشرشده در صفحهٔ اصلی نمایش داده می‌شوند و هرکدام صفحهٔ مطالعهٔ جداگانه دارند.</p></div><a class="btn" href="{{ route('admin.articles.create') }}">+ مقالهٔ جدید</a></div>
@if(session('status'))<div class="alert success" style="margin-top:14px">{{ session('status') }}</div>@endif
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:20px">
<section style="padding:18px;border:1px solid var(--line);border-radius:16px;background:var(--panel,#f8fafb)">
<h3 style="margin:0 0 6px">ورود گروهی مقاله‌ها</h3><p style="color:var(--muted);font-size:13px;margin:0 0 14px">قالب ZIP را بگیر، چند مقاله و تصویر جلد را داخلش آماده کن و فقط یک‌بار آپلود کن.</p>
<a class="btn secondary" href="{{ route('admin.articles.template') }}">دانلود قالب ZIP آماده</a>
<form method="POST" action="{{ route('admin.articles.import') }}" enctype="multipart/form-data" style="margin-top:14px">@csrf
<label for="article-archive" style="display:block;margin-bottom:7px">فایل ZIP مقاله‌ها</label><input id="article-archive" type="file" name="archive" accept=".zip,application/zip" required style="max-width:100%">
@error('archive')<div class="e" style="margin-top:8px">{{ $message }}</div>@enderror
<button class="btn" type="submit" style="margin-top:12px" onclick="return confirm('مقاله‌های داخل ZIP وارد شوند؟ مقاله‌هایی که slug یکسان دارند به‌روزرسانی می‌شوند.')">آپلود و ایمپورت همه</button>
</form></section>
<section style="padding:18px;border:1px solid var(--line);border-radius:16px">
<h3 style="margin:0 0 6px">راهنمای سریع</h3><ul style="color:var(--muted);font-size:13px;line-height:2;margin:0;padding-right:18px"><li>فایل ZIP باید شامل articles.json باشد.</li><li>قالب آماده با ۵ مقاله نمونه ارائه می‌شود.</li><li>تصاویر JPG، PNG و WebP پذیرفته می‌شوند.</li><li>حداکثر ۱۰۰ مقاله و حجم ZIP تا ۱۰ مگابایت.</li><li>slug تکراری باعث به‌روزرسانی مقاله موجود می‌شود.</li></ul></section>
</div>
@if($errors->has('archive'))<div class="e" style="margin-top:10px">{{ $errors->first('archive') }}</div>@endif
<div style="overflow-x:auto;margin-top:22px"><table class="tbl"><thead><tr><th>تصویر</th><th>عنوان</th><th>نشانی</th><th>وضعیت</th><th>آخرین تغییر</th><th>عملیات</th></tr></thead><tbody>
@forelse($articles as $article)
<tr>
<td>@if($article->cover_image)<img src="{{ preg_match('~^https?://|^/~i', $article->cover_image) ? $article->cover_image : \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}" alt="" style="width:64px;height:46px;object-fit:cover;border-radius:8px">@else<span style="color:var(--muted)">—</span>@endif</td>
<td><strong>{{ $article->title }}</strong><div style="font-size:12px;color:var(--muted)">{{ \Illuminate\Support\Str::limit($article->excerpt ?: strip_tags($article->body),90) }}</div></td>
<td dir="ltr">/articles/{{ $article->slug }}</td><td><span class="tag-adm">{{ $article->status==='published'?'منتشرشده':'پیش‌نویس' }}</span></td><td>{{ $article->updated_at?->format('Y/m/d') }}</td>
<td style="white-space:nowrap"><a class="btn secondary" href="{{ route('admin.articles.edit',$article->id) }}">ویرایش</a> @if($article->status==='published')<a class="btn secondary" href="{{ route('marketing.articles.show',$article->slug) }}" target="_blank" rel="noopener">مشاهده</a>@endif<form method="POST" action="{{ route('admin.articles.destroy',$article->id) }}" style="display:inline" data-confirm="این مقاله حذف شود؟">@csrf @method('DELETE')<button class="btn danger" type="submit">حذف</button></form></td>
</tr>
@empty<tr><td colspan="6">هنوز مقاله‌ای ثبت نشده است.</td></tr>@endforelse
</tbody></table></div><div style="margin-top:16px">{{ $articles->links() }}</div></div>
@endsection
