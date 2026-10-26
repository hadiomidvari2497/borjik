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
    }

    public function test_configured_production_robots_and_sitemap_include_only_public_entry_points(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://sabkhoneh.ir');
        SiteSetting::set('seo.indexing_enabled', true);

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

        $this->get(route('marketing.articles.show', $article->slug))
            ->assertOk()
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('rel="canonical" href="https://sabkhoneh.ir/articles/transparent-building-charges"', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('property="og:url" content="https://sabkhoneh.ir/articles/transparent-building-charges"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('"headline":"چطور محاسبهٔ شارژ را شفاف‌تر کنیم؟"', false);
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
