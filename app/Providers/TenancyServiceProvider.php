<?php

namespace App\Providers;

use App\Support\CurrentBuilding;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentBuilding::class);
    }

    public function boot(): void
    {
        // ability هایی که شکل «module.action» دارند (مثل unit.manage) از جدول permissions بررسی می‌شوند.
        // اگر مجوز نبود null برمی‌گردد تا Policy ها هم بتوانند تصمیم بگیرند.
        Gate::before(function ($user, string $ability) {
            if (! str_contains($ability, '.') || ! method_exists($user, 'hasPermission')) {
                return null;
            }

            return $user->hasPermission($ability) ? true : null;
        });
    }
}
