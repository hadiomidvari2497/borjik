<?php

namespace Tests\Feature;

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

    public function test_configured_production_robots_and_sitemap_exclude_private_pages(): void
    {
        $this->app['env'] = 'production';
        SiteSetting::set('seo.site_url', 'https://borjik.example');
        SiteSetting::set('seo.indexing_enabled', true);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Allow: /')
            ->assertSee('Disallow: /profile')
            ->assertSee('Disallow: /invite/')
            ->assertSee('Sitemap: https://borjik.example/sitemap.xml');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('https://borjik.example/')
            ->assertSee('https://borjik.example/articles');
    }
}
