<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeoController extends Controller
{
    public function index()
    {
        $settings = [
            'site_url' => (string) SiteSetting::get('seo.site_url', ''),
            'title' => (string) SiteSetting::get('seo.title', 'برجیک | مدیریت هوشمند ساختمان'),
            'description' => (string) SiteSetting::get('seo.description', 'سامانه فارسی مدیریت ساختمان برای شارژ، پرداخت‌ها، درخواست‌ها و ارتباط بهتر میان مدیران و ساکنان.'),
            'google_verification' => (string) SiteSetting::get('seo.google_verification', ''),
            'indexing_enabled' => (bool) SiteSetting::get('seo.indexing_enabled', false),
        ];

        $publishedArticles = Article::query()->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->count();

        $configuredUrl = $settings['site_url'] ?: (string) config('app.url');
        $isPublicUrl = filter_var($configuredUrl, FILTER_VALIDATE_URL)
            && strtolower((string) parse_url($configuredUrl, PHP_URL_SCHEME)) === 'https'
            && ! in_array(strtolower((string) parse_url($configuredUrl, PHP_URL_HOST)), ['localhost', '127.0.0.1', '::1'], true)
            && empty(parse_url($configuredUrl, PHP_URL_USER))
            && empty(parse_url($configuredUrl, PHP_URL_PASS))
            && in_array((string) parse_url($configuredUrl, PHP_URL_PATH), ['', '/'], true)
            && parse_url($configuredUrl, PHP_URL_QUERY) === null
            && parse_url($configuredUrl, PHP_URL_FRAGMENT) === null;

        return view('admin.seo.index', compact('settings', 'publishedArticles', 'configuredUrl', 'isPublicUrl'));
    }

    public function sitemap()
    {
        $base = rtrim((string) SiteSetting::get('seo.site_url', config('app.url')), '/');
        $enabled = (bool) SiteSetting::get('seo.indexing_enabled', false)
            && app()->environment('production')
            && filter_var($base, FILTER_VALIDATE_URL)
            && strtolower((string) parse_url($base, PHP_URL_SCHEME)) === 'https'
            && ! in_array(strtolower((string) parse_url($base, PHP_URL_HOST)), ['localhost', '127.0.0.1', '::1'], true)
            && empty(parse_url($base, PHP_URL_USER))
            && empty(parse_url($base, PHP_URL_PASS))
            && in_array((string) parse_url($base, PHP_URL_PATH), ['', '/'], true)
            && parse_url($base, PHP_URL_QUERY) === null
            && parse_url($base, PHP_URL_FRAGMENT) === null;

        $urls = [];
        if ($enabled) {
            $urls[] = ['loc' => $base.'/', 'lastmod' => null];
            $urls[] = ['loc' => $base.'/articles', 'lastmod' => null];
            Article::query()->where('status', 'published')
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->each(function ($article) use (&$urls, $base) {
                    $urls[] = ['loc' => $base.'/articles/'.rawurlencode($article->slug), 'lastmod' => optional($article->updated_at)->toAtomString()];
                });
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'. "\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url><loc>'.e($url['loc']).'</loc>';
            if ($url['lastmod']) $xml .= '<lastmod>'.e($url['lastmod']).'</lastmod>';
            $xml .= '</url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $base = rtrim((string) SiteSetting::get('seo.site_url', config('app.url')), '/');
        $enabled = (bool) SiteSetting::get('seo.indexing_enabled', false)
            && app()->environment('production')
            && filter_var($base, FILTER_VALIDATE_URL)
            && strtolower((string) parse_url($base, PHP_URL_SCHEME)) === 'https'
            && ! in_array(strtolower((string) parse_url($base, PHP_URL_HOST)), ['localhost', '127.0.0.1', '::1'], true)
            && empty(parse_url($base, PHP_URL_USER))
            && empty(parse_url($base, PHP_URL_PASS))
            && in_array((string) parse_url($base, PHP_URL_PATH), ['', '/'], true)
            && parse_url($base, PHP_URL_QUERY) === null
            && parse_url($base, PHP_URL_FRAGMENT) === null;

        $body = $enabled
            ? "User-agent: *\nAllow: /\nSitemap: ".$base."/sitemap.xml\n"
            : "User-agent: *\nDisallow: /\n";
        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_url' => ['nullable', 'url', 'max:255'],
            'title' => ['required', 'string', 'max:70'],
            'description' => ['required', 'string', 'max:320'],
            'google_verification' => ['nullable', 'string', 'max:200'],
            'indexing_enabled' => ['nullable', Rule::in(['1'])],
        ], [
            'site_url.url' => 'آدرس سایت باید یک URL معتبر باشد.',
            'title.max' => 'عنوان پیش‌فرض حداکثر ۷۰ نویسه باشد.',
            'description.max' => 'توضیحات پیش‌فرض حداکثر ۳۲۰ نویسه باشد.',
        ]);

        $url = trim((string) ($data['site_url'] ?? ''));
        if ($url !== '') {
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $path = (string) parse_url($url, PHP_URL_PATH);
            if ($scheme !== 'https' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)
                || empty($host) || ! empty(parse_url($url, PHP_URL_USER)) || ! empty(parse_url($url, PHP_URL_PASS))
                || ! in_array($path, ['', '/'], true) || parse_url($url, PHP_URL_QUERY) !== null || parse_url($url, PHP_URL_FRAGMENT) !== null) {
                return back()->withErrors(['site_url' => 'آدرس اصلی باید دامنهٔ عمومی با HTTPS باشد؛ مسیر، پارامتر یا اطلاعات ورود در آدرس وارد نکن.'])->withInput();
            }
            $url = rtrim($url, '/');
        }

        SiteSetting::set('seo.site_url', $url);
        SiteSetting::set('seo.title', trim($data['title']));
        SiteSetting::set('seo.description', trim($data['description']));
        SiteSetting::set('seo.google_verification', trim((string) ($data['google_verification'] ?? '')));
        SiteSetting::set('seo.indexing_enabled', $request->boolean('indexing_enabled'));

        return redirect()->route('admin.seo.index')->with('status', 'تنظیمات سئو ذخیره شد.');
    }
}
