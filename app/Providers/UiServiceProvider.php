<?php

namespace App\Providers;

use App\View\Composers\LayoutComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class UiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.app', LayoutComposer::class);
        Paginator::defaultView('vendor.pagination.borjik');
        Paginator::defaultSimpleView('vendor.pagination.borjik');
    }
}
