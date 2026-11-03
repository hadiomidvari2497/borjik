<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_environment_disallows_crawling_and_keeps_sitemap_empty(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: *')
            ->assertSee('Disallow: /');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertDontSee('<url><loc>', false);
    }

    public function test_public_marketing_pages_are_noindex_until_production_domain_is_configured(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);

        $this->get('/articles')
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);

        $this->get('/login')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);

        foreach (['marketing.seo.requests', 'marketing.seo.finance', 'marketing.seo.comparison', 'marketing.seo.trust'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('name="robots" content="noindex,nofollow"', false);
        }
    }

    public function test_configured_production_robots_and_sitemap_include_only_public_entry_points(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/')
            ->assertOk()
            ->assertSee('"@type":"SoftwareApplication"', false)
            ->assertSee('"@type":"WebSite"', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Allow: /')
            ->assertDontSee('Disallow: /profile')
            ->assertDontSee('Disallow: /invite/')
            ->assertSee('Sitemap: https://sabkhoneh.ir/sitemap.xml');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('https://sabkhoneh.ir/')
            ->assertSee('https://sabkhoneh.ir/articles');
    }

    public function test_http_or_non_root_site_urls_cannot_enable_public_indexing(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'http://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<url><loc>', false);

        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir/subdirectory');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<url><loc>', false);
    }

    public function test_published_article_has_canonical_open_graph_and_structured_data_on_https_production_domain(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $article = Article::query()->where('slug', 'transparent-building-charges')->firstOrFail();

        $response = $this->get(route('marketing.articles.show', $article->slug));

        $response
            ->assertOk()
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('rel="canonical" href="https://sabkhoneh.ir/articles/transparent-building-charges"', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('property="og:url" content="https://sabkhoneh.ir/articles/transparent-building-charges"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('"headline":"چطور محاسبهٔ شارژ را شفاف‌تر کنیم؟"', false)
            ->assertSee('"author":{"@type":"Organization","name":"تیم صابخونه"', false)
            ->assertSee('نویسنده: تیم صابخونه', false);

        if ($article->cover_image) {
            $response->assertSee('rel="preload" as="image"', false)
                ->assertSee('fetchpriority="high"', false)
                ->assertSee('width="1200" height="675"', false)
                ->assertSee('height:clamp(220px,42vw,460px)', false);
        }
    }

    public function test_article_listing_has_unique_metadata_and_collection_structured_data_on_public_production_domain(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/articles')
            ->assertOk()
            ->assertSee('<title>مقالات و راهنماها | صابخونه</title>', false)
            ->assertSee('name="description" content="مقالات و راهنماهای صابخونه دربارهٔ مدیریت ساختمان، شارژ و پرداخت‌ها، رسیدگی به درخواست‌های ساکنان و بهبود ارتباط میان مدیران و ساکنان."', false)
            ->assertSee('rel="canonical" href="https://sabkhoneh.ir/articles"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('https://sabkhoneh.ir/articles/transparent-building-charges', false);
    }

    public function test_new_seo_landing_pages_are_indexable_with_canonical_and_faq_schema_on_public_production_domain(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $pages = [
            'marketing.seo.requests' => 'نرم‌افزار مدیریت درخواست‌های ساختمان',
            'marketing.seo.finance' => 'نرم‌افزار مدیریت مالی ساختمان',
            'marketing.seo.comparison' => 'بهترین نرم‌افزار مدیریت ساختمان را چطور مقایسه و انتخاب کنیم؟',
            'marketing.seo.trust' => 'مرکز اعتماد و شفافیت',
        ];

        foreach ($pages as $routeName => $heading) {
            $path = parse_url(route($routeName), PHP_URL_PATH);

            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('name="robots" content="index,follow"', false)
                ->assertSee('rel="canonical" href="https://sabkhoneh.ir'.$path.'"', false)
                ->assertSee($heading)
                ->assertSee('"@type":"WebPage"', false)
                ->assertSee('"@type":"BreadcrumbList"', false)
                ->assertSee('"@type":"FAQPage"', false);

            if ($routeName === 'marketing.seo.comparison') {
                $response = $this->get(route($routeName));
                $response->assertSee('شارژانه')
                    ->assertSee('هم‌خونه')
                    ->assertSee('شارژلند')
                    ->assertSee('https://sharjane.ir/', false)
                    ->assertSee('https://p-soft.ir/product/hamkhoneh/', false)
                    ->assertSee('https://sharjland.ir/', false);
            }
        }
    }

    public function test_building_charges_page_covers_charge_workflow_and_links_to_related_guides(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/software-building-charges')
            ->assertOk()
            ->assertSee('<title>نرم‌افزار شارژ ساختمان و مدیریت هزینه‌ها | صابخونه</title>', false)
            ->assertSee('نرم‌افزار شارژ ساختمان صابخونه روند تعریف واحدها', false)
            ->assertSee('امکانات مهم در نرم‌افزار شارژ و حسابداری ساختمان')
            ->assertSee('صدور صورت‌حساب و پیگیری پرداخت')
            ->assertSee('rel="canonical" href="https://sabkhoneh.ir/software-building-charges"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('/software-building-requests', false)
            ->assertSee('/articles/transparent-building-charges', false);
    }

    public function test_resident_request_page_targets_request_tracking_and_explains_triage(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/software-building-requests')
            ->assertOk()
            ->assertSee('<title>نرم‌افزار ثبت و پیگیری درخواست‌های ساکنان ساختمان | صابخونه</title>', false)
            ->assertSee('نرم‌افزار مدیریت درخواست‌های ساختمان صابخونه')
            ->assertSee('درخواست‌های پرتکرار ساختمان را دسته‌بندی و اولویت‌بندی کنید')
            ->assertSee('برای ثبت درخواست تعمیرات ساختمان چه اطلاعاتی لازم است؟')
            ->assertSee('/articles/resident-requests-follow-up', false)
            ->assertSee('/software-building-charges', false)
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_comparison_page_renders_a_fair_method_matrix_and_cost_guidance(): void
    {
        $response = $this->get(route('marketing.seo.comparison'));

        $response->assertOk()
            ->assertSee('جدول مقایسهٔ روش‌ها و امکانات')
            ->assertSee('هزینهٔ واقعی نرم‌افزار را چگونه مقایسه کنیم؟')
            ->assertSee('ساختمان کوچک با امور محدود')
            ->assertSee('aria-label="جدول مقایسه روش‌های مدیریت ساختمان"', false)
            ->assertSee('مدیریت اسناد و سوابق مرتبط با ساختمان');
    }

    public function test_articles_link_to_relevant_service_pages_by_topic(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/articles/transparent-building-charges')
            ->assertOk()
            ->assertSee('/software-building-charges', false)
            ->assertSee('/software-building-finance', false)
            ->assertSee('/building-management-software-comparison', false);

        $this->get('/articles/resident-requests-follow-up')
            ->assertOk()
            ->assertSee('/software-building-requests', false)
            ->assertSee('/software-building-charges', false);

        $this->get('/articles/building-data-privacy-checklist')
            ->assertOk()
            ->assertSee('/trust-and-security', false)
            ->assertSee('/software-building-requests', false);
    }

    public function test_article_related_links_follow_their_content_cluster(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/articles/transparent-building-charges')
            ->assertOk()
            ->assertSee('/articles/building-management-software-selection-guide', false)
            ->assertSee('/articles/building-data-privacy-checklist', false);

        $this->get('/articles/resident-requests-follow-up')
            ->assertOk()
            ->assertSee('/articles/building-management-software-selection-guide', false)
            ->assertSee('/articles/spreadsheet-messenger-or-building-software', false);
    }

    public function test_new_seo_pages_are_in_sitemap_and_llms_guide_is_available(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('https://sabkhoneh.ir/software-building-requests')
            ->assertSee('https://sabkhoneh.ir/software-building-finance')
            ->assertSee('https://sabkhoneh.ir/building-management-software-comparison')
            ->assertSee('https://sabkhoneh.ir/trust-and-security')
            ->assertSee('https://sabkhoneh.ir/articles/building-management-software-selection-guide')
            ->assertSee('https://sabkhoneh.ir/articles/spreadsheet-messenger-or-building-software')
            ->assertSee('https://sabkhoneh.ir/articles/building-data-privacy-checklist');

        $this->assertFileExists(public_path('llms.txt'));
        $this->assertStringContainsString('صابخونه', file_get_contents(public_path('llms.txt')));
    }

    public function test_sitemap_includes_published_articles_but_not_drafts_or_future_articles(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

        Article::create([
            'title' => 'پیش‌نویس خصوصی',
            'slug' => 'private-draft-for-seo-test',
            'body' => 'این محتوا نباید در نقشه سایت باشد.',
            'status' => 'draft',
        ]);
        Article::create([
            'title' => 'مقاله آینده',
            'slug' => 'future-article-for-seo-test',
            'body' => 'این محتوا هنوز منتشر نشده است.',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('https://sabkhoneh.ir/articles/transparent-building-charges')
            ->assertDontSee('private-draft-for-seo-test')
            ->assertDontSee('future-article-for-seo-test');
    }
}
