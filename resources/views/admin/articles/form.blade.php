@extends('admin.layout')
@section('title', $article->exists ? 'ویرایش مقاله' : 'مقالهٔ جدید')
@section('content')
<style>
.article-form{max-width:1040px}.article-form .fld input,.article-form .fld textarea,.article-form .fld select{width:100%}.article-form .fgrid{align-items:start}.article-form .wide{grid-column:1/-1}
.article-cover-preview{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:12px}.article-cover-preview img{width:180px;height:110px;object-fit:cover;border-radius:14px;border:1px solid var(--line)}.article-cover-preview small{color:var(--muted)}
.article-editor{border:1px solid var(--line);border-radius:14px;overflow:hidden;background:var(--card,var(--panel,#fff))}.article-editor-toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:10px;border-bottom:1px solid var(--line);background:var(--panel,#f7f9fa)}.article-editor-toolbar button{min-width:36px;height:34px;padding:0 9px;border:1px solid var(--line);border-radius:8px;background:var(--card,#fff);color:var(--text,#243746);font:inherit;cursor:pointer}.article-editor-toolbar button:hover,.article-editor-toolbar button:focus{border-color:var(--brand);color:var(--brand)}.article-editor-toolbar .tool-sep{width:1px;background:var(--line);margin:3px 2px}.article-editor-canvas{min-height:360px;padding:20px;outline:none;line-height:2;overflow-wrap:anywhere}.article-editor-canvas:empty:before{content:attr(data-placeholder);color:var(--muted);pointer-events:none}.article-editor-canvas h2{font-size:1.5rem}.article-editor-canvas h3{font-size:1.25rem}.article-editor-canvas blockquote{border-right:4px solid var(--brand);margin:1em 0;padding:.5em 1em;background:var(--panel,#f7f9fa);border-radius:8px}.article-editor-canvas ul,.article-editor-canvas ol{padding-right:26px}.article-editor-canvas a{color:var(--brand);text-decoration:underline}.article-editor-note{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;color:var(--muted);font-size:12px;padding:8px 12px;border-top:1px solid var(--line)}
</style>
<script src="{{ asset('js/article-editor.js') }}" defer></script>
<div class="card article-form">
<div class="page-h"><h2>{{ $article->exists ? 'ویرایش مقاله' : 'ساخت مقالهٔ جدید' }}</h2><a class="btn secondary" href="{{ route('admin.articles.index') }}">بازگشت به مقالات</a></div>
<form method="POST" enctype="multipart/form-data" action="{{ $article->exists ? route('admin.articles.update',$article->id) : route('admin.articles.store') }}">@csrf @if($article->exists) @method('PUT') @endif
<div class="fgrid">
<div class="fld wide"><label for="title">عنوان مقاله *</label><input id="title" name="title" value="{{ old('title',$article->title) }}" required maxlength="200">@error('title')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld"><label for="author_name">نام نویسنده</label><input id="author_name" name="author_name" value="{{ old('author_name',$article->author_name ?: 'تیم صابخونه') }}" maxlength="160" placeholder="تیم صابخونه">@error('author_name')<div class="e">{{ $message }}</div>@enderror<small style="display:block;color:var(--muted);margin-top:6px">اگر خالی بماند، «تیم صابخونه» ثبت می‌شود.</small></div>
<div class="fld"><label for="slug">نشانی کوتاه (slug)</label><input id="slug" name="slug" dir="ltr" value="{{ old('slug',$article->slug) }}" placeholder="building-management-guide">@error('slug')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld"><label for="status">وضعیت</label><select id="status" name="status"><option value="draft" @selected(old('status',$article->status ?: 'draft')==='draft')>پیش‌نویس</option><option value="published" @selected(old('status',$article->status)==='published')>انتشار</option></select>@error('status')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld wide"><label for="excerpt">خلاصهٔ مقاله (نمایش در صفحهٔ اصلی)</label><textarea id="excerpt" name="excerpt" maxlength="500">{{ old('excerpt',$article->excerpt) }}</textarea>@error('excerpt')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld wide"><label for="cover_image">تصویر جلد مقاله</label><input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp">@error('cover_image')<div class="e">{{ $message }}</div>@enderror
<div class="article-cover-preview" id="article-cover-preview">
@if($article->cover_image)
<img src="{{ preg_match('~^https?://|^/~i', $article->cover_image) ? $article->cover_image : \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}" alt="پیش‌نمایش جلد">
@endif
<small>فرمت‌های مجاز: JPG، PNG و WebP — حداکثر ۵ مگابایت.</small>
</div>
@if($article->exists && $article->cover_image)<label style="display:flex;align-items:center;gap:8px;margin-top:10px"><input style="width:auto" type="checkbox" name="remove_cover" value="1"> حذف تصویر جلد فعلی</label>@endif
</div>
<div class="fld wide"><label for="body">متن کامل مقاله *</label>
<textarea id="body" name="body" required hidden>{{ old('body', $article->body ? \App\Support\ArticleHtmlSanitizer::sanitize($article->body) : '') }}</textarea>
<div class="article-editor" id="article-editor">
<div class="article-editor-toolbar" role="toolbar" aria-label="ابزارهای ویرایش متن">
<button type="button" data-command="bold" title="ضخیم"><strong>B</strong></button><button type="button" data-command="italic" title="مورب"><em>I</em></button><button type="button" data-command="underline" title="زیرخط">U̲</button><button type="button" data-command="strikeThrough" title="خط‌خورده">S̶</button><span class="tool-sep"></span>
<button type="button" data-block="p" title="پاراگراف">متن</button><button type="button" data-block="h2" title="عنوان اصلی">عنوان ۲</button><button type="button" data-block="h3" title="عنوان فرعی">عنوان ۳</button><button type="button" data-block="blockquote" title="نقل‌قول">❝</button><span class="tool-sep"></span>
<button type="button" data-command="insertUnorderedList" title="فهرست نشانه‌دار">• فهرست</button><button type="button" data-command="insertOrderedList" title="فهرست شماره‌دار">۱. فهرست</button><button type="button" data-command="justifyRight" title="راست‌چین">≡ راست</button><button type="button" data-command="justifyCenter" title="وسط‌چین">≡ وسط</button><button type="button" data-command="justifyLeft" title="چپ‌چین">≡ چپ</button><span class="tool-sep"></span>
<button type="button" data-action="link" title="افزودن لینک">افزودن لینک</button><button type="button" data-command="undo" title="واگرد">↶</button><button type="button" data-command="redo" title="ازنو">↷</button><button type="button" data-action="clear" title="پاک‌کردن قالب‌بندی">پاک‌سازی قالب</button>
</div>
<div id="article-editor-canvas" class="article-editor-canvas" contenteditable="true" role="textbox" aria-multiline="true" aria-label="متن مقاله" data-placeholder="مقاله‌ات را اینجا بنویس؛ تیتر، فهرست، نقل‌قول و لینک اضافه کن..." dir="rtl"></div>
<div class="article-editor-note"><span>ویرایشگر دیداری راست‌چین</span><span id="article-editor-count">۰ نویسه</span></div>
</div>
<small style="display:block;color:var(--muted);margin-top:8px">محتوا هنگام ذخیره پاک‌سازی امنیتی می‌شود تا کدهای اجرایی ناخواسته منتشر نشوند.</small>@error('body')<div class="e">{{ $message }}</div>@enderror
</div>
<div class="fld"><label for="published_at">زمان انتشار (اختیاری)</label><input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at',$article->published_at?->format('Y-m-d\\TH:i')) }}">@error('published_at')<div class="e">{{ $message }}</div>@enderror</div>
</div><div class="fbar"><button class="btn" type="submit">ذخیره مقاله</button></div></form></div>
@endsection
