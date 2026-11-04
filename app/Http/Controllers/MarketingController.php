<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\PropertyListing;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Storage;

class MarketingController extends Controller
{
    public function home()
    {
        $content = SiteSetting::get('marketing.content', []);

        $featuredListings = PropertyListing::query()
            ->published()
            ->latest('updated_at')
            ->limit(4)
            ->get();

        $articles = Article::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return view('marketing.home', compact('content', 'articles', 'featuredListings'));
    }

    public function subscriptionPlans()
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->orderBy('units_limit')
            ->orderBy('duration_months')
            ->get();

        return view('marketing.subscription-plans', compact('plans'));
    }

    public function buildingCharges()
    {
        return view('marketing.building-charges');
    }

    public function chargeManagementGuide()
    {
        return view('marketing.charge-management-guide');
    }

    public function buildingManagement()
    {
        return view('marketing.building-management');
    }

    public function seoPage(string $slug)
    {
        $page = config('marketing_seo.pages.'.$slug);

        abort_unless(is_array($page), 404);

        $relatedLinks = collect($page['related'] ?? [])
            ->map(function (array $item) {
                return [
                    'label' => $item['label'],
                    'url' => route($item['route'], $item['params'] ?? []),
                ];
            })
            ->all();

        return view('marketing.seo-page', compact('page', 'relatedLinks', 'slug'));
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

        $relatedArticleSlugs = [
            'transparent-building-charges' => ['building-management-software-selection-guide', 'building-data-privacy-checklist'],
            'resident-requests-follow-up' => ['building-management-software-selection-guide', 'spreadsheet-messenger-or-building-software'],
            'clear-building-communication' => ['spreadsheet-messenger-or-building-software', 'building-management-software-selection-guide'],
            'building-management-software-selection-guide' => ['spreadsheet-messenger-or-building-software', 'building-data-privacy-checklist'],
            'spreadsheet-messenger-or-building-software' => ['building-management-software-selection-guide', 'building-data-privacy-checklist'],
            'building-data-privacy-checklist' => ['building-management-software-selection-guide', 'spreadsheet-messenger-or-building-software'],
        ];

        $relatedServiceMap = [
            'transparent-building-charges' => [
                ['label' => 'نرم‌افزار شارژ و هزینه‌های ساختمان', 'route' => 'marketing.building-charges'],
                ['label' => 'مدیریت مالی و پرداخت‌های ساختمان', 'route' => 'marketing.seo.finance'],
                ['label' => 'راهنمای انتخاب نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.seo.comparison'],
            ],
            'resident-requests-follow-up' => [
                ['label' => 'نرم‌افزار ثبت و پیگیری درخواست‌های ساکنان', 'route' => 'marketing.seo.requests'],
                ['label' => 'امکانات نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.building-management'],
                ['label' => 'مدیریت مالی و شارژ ساختمان', 'route' => 'marketing.building-charges'],
            ],
            'clear-building-communication' => [
                ['label' => 'ثبت و پیگیری درخواست‌های ساکنان', 'route' => 'marketing.seo.requests'],
                ['label' => 'امکانات نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.building-management'],
            ],
            'building-management-software-selection-guide' => [
                ['label' => 'مقایسه و انتخاب نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.seo.comparison'],
                ['label' => 'مدیریت مالی و شارژ ساختمان', 'route' => 'marketing.building-charges'],
                ['label' => 'مدیریت درخواست‌های ساکنان', 'route' => 'marketing.seo.requests'],
            ],
            'spreadsheet-messenger-or-building-software' => [
                ['label' => 'مقایسه روش‌های مدیریت ساختمان', 'route' => 'marketing.seo.comparison'],
                ['label' => 'ثبت و پیگیری درخواست‌های ساکنان', 'route' => 'marketing.seo.requests'],
                ['label' => 'مدیریت مالی و شارژ ساختمان', 'route' => 'marketing.building-charges'],
            ],
            'building-data-privacy-checklist' => [
                ['label' => 'امنیت و حریم خصوصی اطلاعات ساختمان', 'route' => 'marketing.seo.trust'],
                ['label' => 'مدیریت درخواست‌های ساکنان', 'route' => 'marketing.seo.requests'],
                ['label' => 'امکانات نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.building-management'],
            ],
        ];

        $serviceLinks = $relatedServiceMap[$article->slug] ?? [
            ['label' => 'نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.building-management'],
            ['label' => 'مدیریت شارژ و هزینه‌های ساختمان', 'route' => 'marketing.building-charges'],
        ];

        // Every published article should pass clear contextual relevance to the main building-management page.
        if (! collect($serviceLinks)->contains(fn (array $item) => ($item['route'] ?? null) === 'marketing.building-management')) {
            array_unshift($serviceLinks, [
                'label' => 'نرم‌افزار مدیریت ساختمان صابخونه',
                'route' => 'marketing.building-management',
            ]);
        }

        $relatedServiceLinks = collect($serviceLinks)->map(fn (array $item) => [
            'label' => $item['label'],
            'url' => route($item['route']),
        ])->all();

        $topicMap = [
            'transparent-building-charges' => [['label' => 'شارژ ساختمان', 'route' => 'marketing.building-charges'], ['label' => 'مدیریت مالی ساختمان', 'route' => 'marketing.seo.finance'], ['label' => 'نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.building-management']],
            'resident-requests-follow-up' => [['label' => 'درخواست ساکنان', 'route' => 'marketing.seo.requests'], ['label' => 'پیگیری تعمیرات ساختمان', 'route' => 'marketing.seo.requests'], ['label' => 'نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.building-management']],
            'clear-building-communication' => [['label' => 'ارتباط با ساکنان', 'route' => 'marketing.building-management'], ['label' => 'مدیریت مجتمع مسکونی', 'route' => 'marketing.building-management'], ['label' => 'درخواست‌های ساختمان', 'route' => 'marketing.seo.requests']],
            'building-management-software-selection-guide' => [['label' => 'انتخاب نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.seo.comparison'], ['label' => 'مقایسه نرم‌افزارها', 'route' => 'marketing.seo.comparison'], ['label' => 'مدیریت ساختمان', 'route' => 'marketing.building-management']],
            'spreadsheet-messenger-or-building-software' => [['label' => 'مقایسه نرم‌افزار مدیریت ساختمان', 'route' => 'marketing.seo.comparison'], ['label' => 'مدیریت ساختمان با اکسل', 'route' => 'marketing.seo.comparison'], ['label' => 'سامانه مدیریت ساختمان', 'route' => 'marketing.building-management']],
            'building-data-privacy-checklist' => [['label' => 'امنیت اطلاعات ساختمان', 'route' => 'marketing.seo.trust'], ['label' => 'حریم خصوصی ساکنان', 'route' => 'marketing.seo.trust'], ['label' => 'مدیریت ساختمان', 'route' => 'marketing.building-management']],
        ];

        $articleTopics = collect($topicMap[$article->slug] ?? [['label' => 'مدیریت ساختمان', 'route' => 'marketing.building-management'], ['label' => 'شارژ ساختمان', 'route' => 'marketing.building-charges']])
            ->map(fn (array $topic) => ['label' => $topic['label'], 'url' => route($topic['route'])])
            ->all();
        $preferredSlugs = $relatedArticleSlugs[$article->slug] ?? [];
        $relatedArticles = Article::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->whereIn('slug', $preferredSlugs)
            ->get()
            ->sortBy(fn (Article $related) => array_search($related->slug, $preferredSlugs, true))
            ->values();

        if ($relatedArticles->isEmpty()) {
            $relatedArticles = Article::query()
                ->where('status', 'published')
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->where('slug', '!=', $article->slug)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(2)
                ->get();
        }

        return view('marketing.article', compact('article', 'relatedArticles', 'relatedServiceLinks', 'articleTopics'));
    }
}
