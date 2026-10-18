@extends('admin.layout')
@section('title', 'مدیریت مقالات')
@section('content')
<div class="card"><div class="page-h"><div><h2>مقالات سایت</h2><p style="color:var(--muted);margin:4px 0 0">مقاله‌های منتشرشده در صفحهٔ اصلی نمایش داده می‌شوند و هرکدام صفحهٔ مطالعهٔ جداگانه دارند.</p></div><a class="btn" href="{{ route('admin.articles.create') }}">+ مقالهٔ جدید</a></div>
<div style="overflow-x:auto;margin-top:18px"><table class="tbl"><thead><tr><th>عنوان</th><th>نشانی</th><th>وضعیت</th><th>آخرین تغییر</th><th>عملیات</th></tr></thead><tbody>
@forelse($articles as $article)<tr><td><strong>{{ $article->title }}</strong><div style="font-size:12px;color:var(--muted)">{{ \Illuminate\Support\Str::limit($article->excerpt ?: strip_tags($article->body),90) }}</div></td><td dir="ltr">/articles/{{ $article->slug }}</td><td><span class="tag-adm">{{ $article->status==='published'?'منتشرشده':'پیش‌نویس' }}</span></td><td>{{ $article->updated_at?->format('Y/m/d') }}</td><td style="white-space:nowrap"><a class="btn secondary" href="{{ route('admin.articles.edit',$article->id) }}">ویرایش</a> @if($article->status==='published')<a class="btn secondary" href="{{ route('marketing.articles.show',$article->slug) }}" target="_blank" rel="noopener">مشاهده</a>@endif<form method="POST" action="{{ route('admin.articles.destroy',$article->id) }}" style="display:inline" onsubmit="return confirm('این مقاله حذف شود؟')">@csrf @method('DELETE')<button class="btn danger" type="submit">حذف</button></form></td></tr>
@empty<tr><td colspan="5">هنوز مقاله‌ای ثبت نشده است.</td></tr>@endforelse
</tbody></table></div><div style="margin-top:16px">{{ $articles->links() }}</div></div>
@endsection
