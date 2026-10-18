@php
  $F = \App\Support\Fa::class;
  $sizeLabel = static function ($bytes) use ($F) {
    if ($bytes >= 1048576) return $F::digits(number_format($bytes / 1048576, 1)).' مگابایت';
    return $F::digits(number_format(max(1, $bytes / 1024), 0)).' کیلوبایت';
  };
@endphp
@extends('layouts.app')
@section('title', 'اسناد')
@section('content')
<style>
.docs-page{display:grid;gap:16px}.docs-hero{border-radius:20px;padding:24px;background:linear-gradient(115deg,#173f50,#287d83);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:20px}.docs-hero h2{font-size:21px;margin:0 0 7px}.docs-hero p{font-size:12px;line-height:1.9;opacity:.88;margin:0;max-width:700px}.docs-stats{display:flex;gap:9px;flex-wrap:wrap}.docs-stat{border:1px solid #ffffff35;border-radius:13px;padding:11px 14px;min-width:100px;background:#ffffff0d}.docs-stat b{display:block;font-size:20px}.docs-stat span{font-size:10px;opacity:.85}.docs-cats{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.docs-cat{padding:7px 10px;border-radius:10px;background:var(--surface-2,var(--bg));font-size:11px;color:var(--muted)}.docs-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.docs-card{padding:16px;min-width:0}.docs-card-top{display:flex;gap:12px;align-items:flex-start}.docs-file-icon{display:grid;place-items:center;width:44px;height:48px;flex:none;border-radius:12px;background:var(--brand-soft);color:var(--brand-d);font-weight:900;font-size:11px}.docs-card h3{font-size:14px;line-height:1.8;margin:0 0 4px;overflow-wrap:anywhere}.docs-meta{display:flex;gap:7px;flex-wrap:wrap;color:var(--muted);font-size:10px;line-height:1.9}.docs-desc{font-size:11px;line-height:1.9;color:var(--muted);margin:12px 0}.docs-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:12px}.docs-actions form{margin:0}.docs-tag{display:inline-flex;border-radius:8px;padding:4px 8px;background:var(--brand-soft);color:var(--brand-d);font-size:10px}.docs-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.docs-full{grid-column:1/-1}.docs-roles{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}.docs-role{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--muted)}.docs-role input{width:auto}.docs-empty{padding:30px;text-align:center;color:var(--muted);font-size:12px;line-height:2}.docs-empty strong{display:block;color:var(--text,var(--ink));font-size:14px;margin-bottom:6px}.docs-pager{margin-top:16px}
@media(max-width:760px){.docs-hero{align-items:flex-start;flex-direction:column}.docs-grid,.docs-form-grid{grid-template-columns:1fr}.docs-full{grid-column:auto}.docs-stat{min-width:85px}}
</style>
<div class="docs-page">
  <section class="docs-hero">
    <div><h2>بایگانی اسناد ساختمان</h2><p>آیین‌نامه‌ها، صورت‌جلسه‌ها، اسناد مالی و فایل‌های فنی را در یک محل امن نگه‌دار. فایل‌ها خصوصی هستند و دسترسی به آن‌ها بر اساس ساختمان و نقش کاربر کنترل می‌شود.</p></div>
    <div class="docs-stats"><div class="docs-stat"><b>{{ $F::num($totalDocuments) }}</b><span>سند فعال</span></div><div class="docs-stat"><b>{{ $F::num(count($categories)) }}</b><span>دسته‌بندی</span></div></div>
  </section>

  <section class="card">
    <div class="page-h"><div><h2>فهرست اسناد</h2><p class="muted">فقط اسناد ساختمان فعال و اسناد قابل مشاهده برای نقش فعلی نمایش داده می‌شوند.</p></div></div>
    <div class="docs-cats">
      @foreach($categories as $key => $label)
        <span class="docs-cat">{{ $label }} <b>{{ $F::num($categoryCounts[$key] ?? 0) }}</b></span>
      @endforeach
    </div>
    @if($documents->isEmpty())
      <div class="docs-empty"><strong>هنوز سندی در بایگانی نیست</strong>اولین سند ساختمان را اضافه کن یا از مدیر بخواه سند موردنیازت را در دسترس قرار دهد.</div>
    @else
      <div class="docs-grid" style="margin-top:14px">
        @foreach($documents as $document)
          <article class="card docs-card">
            <div class="docs-card-top">
              <div class="docs-file-icon">{{ strtoupper(pathinfo($document->file?->original_name ?? '', PATHINFO_EXTENSION) ?: 'FILE') }}</div>
              <div style="min-width:0;flex:1">
                <h3>{{ $document->title }}</h3>
                <div class="docs-meta"><span class="docs-tag">{{ $categories[$document->category] ?? 'سایر اسناد' }}</span><span>{{ $document->file ? $sizeLabel($document->file->size) : 'فایل موجود نیست' }}</span></div>
              </div>
            </div>
            @if($document->description)<p class="docs-desc">{{ $document->description }}</p>@endif
            <div class="docs-meta"><span>ثبت‌کننده: {{ $document->uploadedBy?->display_name ?: 'کاربر حذف‌شده' }}</span><span>تاریخ: {{ $document->created_at ? $F::date($document->created_at, 'Y/m/d') : '—' }}</span></div>
            <div class="docs-meta" style="margin-top:7px"><span>دسترسی: {{ $document->roles->isEmpty() ? 'تمام نقش‌های دارای مجوز مشاهده' : $document->roles->pluck('name_fa')->join('، ') }}</span></div>
            <div class="docs-actions">
              @if($document->file)<a class="btn sm" href="{{ route('documents.download', $document->id) }}">دریافت فایل</a>@endif
              @if($canManage)
                <form method="POST" action="{{ route('documents.destroy', $document->id) }}" data-confirm="این سند از فهرست فعال خارج شود؟" data-confirm-title="بایگانی سند" data-confirm-icon="warning">@csrf @method('DELETE')<button class="btn dng sm" type="submit">حذف از فهرست</button></form>
              @endif
            </div>
          </article>
        @endforeach
      </div>
      <div class="docs-pager">{{ $documents->links() }}</div>
    @endif
  </section>

  @if($canManage)
    <section class="card">
      <div class="page-h"><div><h2>افزودن سند جدید</h2><p class="muted">حداکثر حجم فایل ۲۰ مگابایت است. فایل‌ها در فضای خصوصی ذخیره می‌شوند.</p></div></div>
      <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" style="margin-top:14px">@csrf
        <div class="docs-form-grid">
          <div class="fld"><label for="doc-title">عنوان سند *</label><input id="doc-title" name="title" maxlength="200" value="{{ old('title') }}" required placeholder="مثلاً آیین‌نامه استفاده از پارکینگ">@error('title')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld"><label for="doc-category">دسته‌بندی *</label><select id="doc-category" name="category" required><option value="">انتخاب دسته‌بندی</option>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>@endforeach</select>@error('category')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld docs-full"><label for="doc-description">توضیحات</label><textarea id="doc-description" name="description" rows="3" maxlength="2000" placeholder="خلاصه یا توضیحی درباره سند (اختیاری)">{{ old('description') }}</textarea>@error('description')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld docs-full"><label for="doc-file">فایل سند * (PDF، Office، تصویر یا متن)</label><input id="doc-file" type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png" required>@error('file')<div class="e">{{ $message }}</div>@enderror</div>
          <div class="fld docs-full"><label>محدودیت مشاهده بر اساس نقش</label><p class="muted" style="font-size:11px;line-height:1.9;margin:4px 0">اگر هیچ نقشی انتخاب نشود، سند برای همه نقش‌های دارای مجوز مشاهده قابل دسترسی است. در غیر این صورت فقط نقش‌های انتخاب‌شده فایل را می‌بینند.</p>
            <div class="docs-roles">@foreach($roles as $role)<label class="docs-role"><input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array((string) $role->id, old('role_ids', []), true))>{{ $role->name_fa }}</label>@endforeach</div>
            @error('role_ids')<div class="e">{{ $message }}</div>@enderror @error('role_ids.*')<div class="e">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="fbar"><button class="btn" type="submit">ثبت و بایگانی سند</button></div>
      </form>
    </section>
  @endif
</div>
@endsection
