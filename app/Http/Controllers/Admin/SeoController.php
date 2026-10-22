<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\Article;
use App\\Models\\SiteSetting;
use Illuminate\\Http\\Request;
use Illuminate\\Validation\\Rule;

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
            && in_array(parse_url($configuredUrl, PHP_URL_SCHEME), ['https', 'http'], true)
            && ! in_array(strtolower((string) parse_url($configuredUrl, PHP_URL_HOST)), ['localhost', '127.0.0.1', '::1'], true);

        return view('admin.seo.index', compact('settings', 'publishedArticles', 'configuredUrl', 'isPublicUrl'));
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
            'site_url.url' => 'آدرس سایت باید یک URL معتبر و شامل http یا https باشد.',
            'title.max' => 'عنوان پیش‌فرض حداکثر ۷۰ نویسه باشد.',
            'description.max' => 'توضیحات پیش‌فرض حداکثر ۳۲۰ نویسه باشد.',
        ]);

        $url = trim((string) ($data['site_url'] ?? ''));
        if ($url !== '') {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return back()->withErrors(['site_url' => 'آدرس لوکال قابل استفاده برای سئوی عمومی نیست؛ دامنهٔ واقعی سایت را وارد کن.'])->withInput();
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
