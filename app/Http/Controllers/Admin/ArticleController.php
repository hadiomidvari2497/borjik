<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);
        $data['admin_id'] = $request->user('admin')->id;
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
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], $article->id);
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? $article->published_at ?? now()) : null;
        $article->update($data);

        return redirect()->route('admin.articles.index')->with('status', 'تغییرات مقاله ذخیره شد.');
    }

    public function destroy(int $id)
    {
        Article::findOrFail($id)->delete();

        return redirect()->route('admin.articles.index')->with('status', 'مقاله حذف شد.');
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'regex:/^[\pL\pN-]+$/u', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'url', 'max:1000'],
            'body' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ], [
            'slug.regex' => 'نشانی مقاله فقط می‌تواند شامل حروف، عدد و خط تیره باشد.',
            'cover_image.url' => 'نشانی تصویر باید یک URL معتبر باشد.',
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
}
