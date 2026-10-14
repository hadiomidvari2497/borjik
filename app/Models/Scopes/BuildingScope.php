<?php

namespace App\Models\Scopes;

use App\Support\CurrentBuilding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * همه‌ی کوئری‌های جدول‌های Tenant را به building_id فعلی محدود می‌کند.
 * اگر ساختمان فعالی تنظیم نشده باشد «بسته» عمل می‌کند و هیچ ردیفی برنمی‌گردد (Fail Closed).
 */
class BuildingScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $current = app(CurrentBuilding::class);

        if ($current->bypassing()) {
            return;
        }

        $builder->where($model->getTable().'.building_id', $current->id() ?? 0);
    }
}
