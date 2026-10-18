<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\SiteSetting;

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

        return view('marketing.article', compact('article'));
    }
}
