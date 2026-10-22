<?php

namespace Tests\Feature;

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
    }
}
