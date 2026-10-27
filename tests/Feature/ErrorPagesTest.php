<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /** @return array<string, array{0:int,1:string}> */
    public static function statusPages(): array
    {
        return [
            '403' => [403, 'دسترسی به این'],
            '419' => [419, 'مدت اعتبار'],
            '429' => [429, 'شلوغ است'],
            '500' => [500, 'موتورخانه'],
            '503' => [503, 'در حال سرویس است'],
            'generic 4xx' => [418, 'قابل انجام'],
            'generic 5xx' => [502, 'موتورخانه'],
        ];
    }

    #[DataProvider('statusPages')]
    public function test_other_http_errors_use_the_branded_page(int $status, string $needle): void
    {
        Route::get('/_test-error/'.$status, fn () => abort($status));

        $this->get('/_test-error/'.$status)
            ->assertStatus($status)
            ->assertSee($needle, false)
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee((string) $status);
    }
}
