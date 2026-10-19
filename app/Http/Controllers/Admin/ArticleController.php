<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\ArticleHtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ZipArchive;

class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles.index', ['articles' => Article::query()->orderByDesc('updated_at')->paginate(15)]);
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        unset($data['remove_cover']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);
        $data['admin_id'] = $request->user('admin')->id;
        $data['body'] = ArticleHtmlSanitizer::sanitize($data['body']);
        $data['cover_image'] = $request->file('cover_image')?->store('article-covers', 'public');
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;
        Article::create($data);

        return redirect()->route('admin.articles.index')->with('status', 'مقاله ذخیره شد.');
    }

    public function edit(int $id)
    {
        return view('admin.articles.form', ['article' => Article::findOrFail($id)]);
    }

    public function update(Request $request, int $id)
    {
        $article = Article::findOrFail($id);
        $data = $this->validated($request, $article);
        $removeCover = $request->boolean('remove_cover');
        unset($data['remove_cover']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], $article->id);
        $data['body'] = ArticleHtmlSanitizer::sanitize($data['body']);
        if ($removeCover) {
            $this->deleteStoredCover($article->cover_image);
            $data['cover_image'] = null;
        } elseif ($request->hasFile('cover_image')) {
            $this->deleteStoredCover($article->cover_image);
            $data['cover_image'] = $request->file('cover_image')->store('article-covers', 'public');
        } else {
            unset($data['cover_image']);
        }
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? $article->published_at ?? now()) : null;
        $article->update($data);

        return redirect()->route('admin.articles.index')->with('status', 'تغییرات مقاله ذخیره شد.');
    }

    public function destroy(int $id)
    {
        $article = Article::findOrFail($id);
        $this->deleteStoredCover($article->cover_image);
        $article->delete();

        return redirect()->route('admin.articles.index')->with('status', 'مقاله حذف شد.');
    }

    public function downloadTemplate()
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'افزونه ZipArchive روی سرور فعال نیست.');

        $path = tempnam(sys_get_temp_dir(), 'borjik-articles-');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            abort(500, 'ساخت فایل قالب ممکن نشد.');
        }

        $examples = [];
        for ($i = 1; $i <= 5; $i++) {
            $examples[] = [
                'title' => "عنوان مقاله نمونه {$i}",
                'slug' => "sample-article-{$i}",
                'excerpt' => 'خلاصه کوتاه مقاله را اینجا بنویسید.',
                'body' => '<h2>عنوان بخش</h2><p>متن مقاله را اینجا بنویسید. می‌توانید از HTML ساده مانند تیتر، پاراگراف، فهرست و لینک استفاده کنید.</p><ul><li>نکته اول</li><li>نکته دوم</li></ul>',
                'status' => 'draft',
                'published_at' => null,
                'cover_image' => null,
            ];
        }
        $zip->addFromString('articles.json', json_encode($examples, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('README.txt', "قالب ورود گروهی مقالات برجیک\n\n۱) articles.json را با UTF-8 و بدون تغییر ساختار آرایه ویرایش کنید.\n۲) برای هر مقاله title و body لازم است؛ slug اختیاری است. status فقط draft یا published باشد.\n۳) تصویر جلد را در پوشه images قرار دهید و مقدار cover_image را مانند images/cover-1.jpg تنظیم کنید. اگر تصویر نمی‌خواهید مقدار cover_image را null بگذارید.\n۴) همه فایل‌ها را دوباره داخل ZIP قرار دهید و ZIP را در پنل مدیریت وارد کنید.\n۵) اگر slug مقاله‌ای از قبل وجود داشته باشد همان مقاله به‌روزرسانی می‌شود؛ در غیر این صورت مقاله جدید ساخته می‌شود.\n\nحداکثر ۱۰۰ مقاله، ZIP حداکثر ۱۰ مگابایت و هر تصویر حداکثر ۵ مگابایت است. تصاویر مجاز: JPG، PNG و WebP.\n");
        $zip->addFromString('images/README.txt', "تصاویر جلد را در همین پوشه قرار دهید. فایل‌های تصویر را با نام‌هایی مثل cover-1.jpg نام‌گذاری و مسیرشان را در cover_image وارد کنید. فایل README تصویر نیست و صرفاً راهنماست.\n");
        $zip->close();

        return response()->download($path, 'borjik-article-template.zip')->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate(['archive' => ['required', 'file', 'mimes:zip', 'max:10240']]);
        abort_unless(class_exists(ZipArchive::class), 503, 'افزونه ZipArchive روی سرور فعال نیست.');

        $zip = new ZipArchive();
        if ($zip->open($request->file('archive')->getRealPath()) !== true) {
            return back()->withErrors(['archive' => 'فایل ZIP معتبر نیست.']);
        }

        try {
            if ($zip->numFiles > 300) {
                return back()->withErrors(['archive' => 'تعداد فایل‌های ZIP بیش از حد مجاز است.']);
            }

            $totalBytes = 0;
            $entries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = str_replace('\\', '/', $stat['name'] ?? '');
                if ($name === '' || str_starts_with($name, '/') || preg_match('~(^|/)\.\.(?:/|$)~', $name)) {
                    return back()->withErrors(['archive' => 'مسیر یکی از فایل‌های داخل ZIP امن نیست.']);
                }
                $totalBytes += (int) ($stat['size'] ?? 0);
                if ($totalBytes > 30 * 1024 * 1024) {
                    return back()->withErrors(['archive' => 'حجم بازشده فایل ZIP از ۳۰ مگابایت بیشتر است.']);
                }
                $entries[$name] = $i;
            }

            $manifestIndex = $entries['articles.json'] ?? null;
            if ($manifestIndex === null) {
                return back()->withErrors(['archive' => 'فایل articles.json در ریشه ZIP پیدا نشد.']);
            }
            $raw = $zip->getFromIndex($manifestIndex);
            $records = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($records) || !array_is_list($records) || count($records) < 1 || count($records) > 100) {
                return back()->withErrors(['archive' => 'articles.json باید آرایه‌ای شامل ۱ تا ۱۰۰ مقاله باشد.']);
            }

            $created = 0;
            $updated = 0;
            DB::transaction(function () use ($records, $zip, $entries, $request, &$created, &$updated) {
                foreach ($records as $index => $record) {
                    if (!is_array($record)) {
                        throw new \RuntimeException('ساختار مقاله شماره '.($index + 1).' معتبر نیست.');
                    }
                    $validator = Validator::make($record, [
                        'title' => ['required', 'string', 'max:200'],
                        'slug' => ['nullable', 'string', 'max:220'],
                        'excerpt' => ['nullable', 'string', 'max:500'],
                        'body' => ['required', 'string', 'max:50000'],
                        'status' => ['nullable', Rule::in(['draft', 'published'])],
                        'published_at' => ['nullable', 'date'],
                        'cover_image' => ['nullable', 'string', 'max:240'],
                    ]);
                    if ($validator->fails()) {
                        throw new \RuntimeException('اطلاعات مقاله شماره '.($index + 1).' معتبر نیست: '.$validator->errors()->first());
                    }
                    $data = $validator->validated();
                    $slugSource = trim((string) ($data['slug'] ?? '')) ?: $data['title'];
                    $slug = Str::slug($slugSource) ?: 'article';
                    $existing = Article::query()->where('slug', $slug)->first();
                    $data['slug'] = $existing ? $existing->slug : $this->uniqueSlug($slug);
                    $data['excerpt'] = $data['excerpt'] ?? null;
                    $data['body'] = ArticleHtmlSanitizer::sanitize($data['body']);
                    $data['status'] = $data['status'] ?? 'draft';
                    $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;
                    $data['admin_id'] = $request->user('admin')->id;

                    $cover = $data['cover_image'] ?? null;
                    if ($cover && preg_match('~^https?://~i', $cover)) {
                        $data['cover_image'] = $cover;
                    } elseif ($cover) {
                        $cover = str_replace('\\', '/', $cover);
                        if (!str_starts_with($cover, 'images/') || str_contains($cover, '..') || !isset($entries[$cover])) {
                            throw new \RuntimeException('تصویر جلد مقاله شماره '.($index + 1).' در ZIP پیدا نشد یا مسیر آن معتبر نیست.');
                        }
                        $bytes = $zip->getFromIndex($entries[$cover]);
                        if (!is_string($bytes) || strlen($bytes) > 5 * 1024 * 1024) {
                            throw new \RuntimeException('حجم تصویر جلد مقاله شماره '.($index + 1).' بیش از حد مجاز است.');
                        }
                        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
                        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
                        if (!$extension) {
                            throw new \RuntimeException('فرمت تصویر جلد مقاله شماره '.($index + 1).' مجاز نیست.');
                        }
                        $stored = 'article-covers/'.Str::uuid().'.'.$extension;
                        Storage::disk('public')->put($stored, $bytes);
                        $data['cover_image'] = $stored;
                    } else {
                        $data['cover_image'] = $existing?->cover_image;
                    }

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        Article::create($data);
                        $created++;
                    }
                }
            });

            return redirect()->route('admin.articles.index')->with('status', "ورود گروهی انجام شد؛ {$created} مقاله جدید و {$updated} مقاله به‌روزرسانی شد.");
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['archive' => $exception instanceof \RuntimeException ? $exception->getMessage() : 'ورود ZIP ناموفق بود؛ ساختار فایل را بررسی کنید.']);
        } finally {
            $zip->close();
        }
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'regex:/^[\pL\pN-]+$/u', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'body' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'remove_cover' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => 'نشانی مقاله فقط می‌تواند شامل حروف، عدد و خط تیره باشد.',
            'cover_image.image' => 'فایل جلد باید یک تصویر معتبر باشد.',
            'cover_image.mimes' => 'فرمت تصویر باید JPG، PNG یا WebP باشد.',
            'cover_image.max' => 'حجم تصویر جلد نباید بیشتر از ۵ مگابایت باشد.',
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'article';
        $slug = $base;
        $suffix = 2;
        while (Article::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }
        return $slug;
    }

    private function deleteStoredCover(?string $value): void
    {
        if ($value && !preg_match('~^(https?://|/)~i', $value)) {
            Storage::disk('public')->delete($value);
        }
    }
}
