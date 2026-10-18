<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\SiteSetting;

class MarketingController extends Controller
{
    public function home()
    {
        $content = SiteSetting::get('marketing.content', []);

        // The standard eNamad seal URL points to a page, not an image.
        // Convert it to the official logo endpoint when an image URL was
        // entered in the CMS using the usual id/Code parameters.
        $enamadImageUrl = data_get($content, 'enamad.image_url');
        if (is_string($enamadImageUrl) && $enamadImageUrl !== '') {
            $urlParts = parse_url($enamadImageUrl);
            if (
                ($urlParts['host'] ?? null) === 'trustseal.enamad.ir'
                && in_array($urlParts['path'] ?? '/', ['', '/'], true)
            ) {
                parse_str($urlParts['query'] ?? '', $query);
                if (!empty($query['id']) && !empty($query['Code'])) {
                    data_set(
                        $content,
                        'enamad.image_url',
                        'https://trustseal.enamad.ir/logo.aspx?'.http_build_query([
                            'id' => $query['id'],
                            'Code' => $query['Code'],
                        ])
                    );
                }
            }
        }
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
