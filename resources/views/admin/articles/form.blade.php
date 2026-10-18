@extends('admin.layout')
@section('title', $article->exists ? 'ویرایش مقاله' : 'مقالهٔ جدید')
@section('content')
<style>.article-form{max-width:900px}.article-form .fld textarea{min-height:150px}.article-form .fld input,.article-form .fld textarea,.article-form .fld select{width:100%}.article-form .fgrid{align-items:start}.article-form .wide{grid-column:1/-1}</style>
<div class="card article-form"><div class="page-h"><h2>{{ $article->exists ? 'ویرایش مقاله' : 'ساخت مقالهٔ جدید' }}</h2><a class="btn secondary" href="{{ route('admin.articles.index') }}">بازگشت به مقالات</a></div>
<form method="POST" action="{{ $article->exists ? route('admin.articles.update',$article->id) : route('admin.articles.store') }}">@csrf @if($article->exists) @method('PUT') @endif
<div class="fgrid">
<div class="fld wide"><label for="title">عنوان مقاله *</label><input id="title" name="title" value="{{ old('title',$article->title) }}" required maxlength="200">@error('title')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld"><label for="slug">نشانی کوتاه (slug)</label><input id="slug" name="slug" dir="ltr" value="{{ old('slug',$article->slug) }}" placeholder="building-management-guide">@error('slug')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld"><label for="status">وضعیت</label><select id="status" name="status"><option value="draft" @selected(old('status',$article->status ?: 'draft')==='draft')>پیش‌نویس</option><option value="published" @selected(old('status',$article->status)==='published')>انتشار</option></select></div>
<div class="fld wide"><label for="excerpt">خلاصهٔ مقاله (نمایش در صفحهٔ اصلی)</label><textarea id="excerpt" name="excerpt" maxlength="500">{{ old('excerpt',$article->excerpt) }}</textarea>@error('excerpt')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld wide"><label for="cover_image">نشانی تصویر جلد (اختیاری)</label><input id="cover_image" name="cover_image" type="url" dir="ltr" value="{{ old('cover_image',$article->cover_image) }}" placeholder="https://...">@error('cover_image')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld wide"><label for="body">متن کامل مقاله *</label><textarea id="body" name="body" required style="min-height:320px">{{ old('body',$article->body) }}</textarea><small style="color:var(--muted)">متن ساده پذیرفته می‌شود؛ خط‌های جدید در صفحهٔ مقاله حفظ می‌شوند.</small>@error('body')<div class="e">{{ $message }}</div>@enderror</div>
<div class="fld"><label for="published_at">زمان انتشار (اختیاری)</label><input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at',$article->published_at?->format('Y-m-d\TH:i')) }}">@error('published_at')<div class="e">{{ $message }}</div>@enderror</div>
</div><div class="fbar"><button class="btn" type="submit">ذخیره مقاله</button></div></form></div>
@endsection
