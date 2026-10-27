<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class MarketingController extends Controller
{
    public function home()
    {
        $content = SiteSetting::get('marketing.content', []);

        $articles = Article::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return view('marketing.home', compact('content', 'articles'));
    }

    public function enamadImage()
    {
        $path = data_get(SiteSetting::get('marketing.content', []), 'enamad.image_path');

        abort_unless(is_string($path) && $path !== '' && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    public function articles()
    {
        $articles = Article::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9);

        return view('marketing.articles', compact('articles'));
    }

    public function article(string $slug)
    {
        $article = Article::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->firstOrFail();

        $relatedArticles = Article::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where('slug', '!=', $article->slug)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        return view('marketing.article', compact('article', 'relatedArticles'));
    }
}
