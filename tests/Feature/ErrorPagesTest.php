<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_unknown_url_shows_the_branded_404_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('این', false)
            ->assertSee('در ساختمان ما وجود ندارد', false)
            ->assertSee('بازگشت به صفحه اصلی')
            ->assertSee('name="robots" content="noindex,nofollow"', false);
    }
}
