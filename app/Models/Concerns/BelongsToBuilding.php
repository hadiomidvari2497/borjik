<?php

namespace App\Models\Concerns;

use App\Exceptions\TenantException;
use App\Models\Scopes\BuildingScope;
use App\Support\CurrentBuilding;
use Illuminate\Database\Eloquent\Model;

/** هر مدلی که این Trait را بگیرد به یک ساختمان تعلق دارد (building_id). */
trait BelongsToBuilding
{
    public static function bootBelongsToBuilding(): void
    {
        static::addGlobalScope(new BuildingScope());

        static::creating(function (Model $model) {
            $current = app(CurrentBuilding::class);

            if ($current->bypassing()) {
                if (! $model->building_id) {
                    throw new TenantException('building_id باید هنگام نوشتن بدون scope صریحاً داده شود.');
                }

                return;
            }

            if ($current->id() === null) {
                throw new TenantException('ساختمان فعالی تنظیم نشده است.');
            }

            if ($model->building_id && (int) $model->building_id !== $current->id()) {
                throw new TenantException('نوشتن در ساختمان دیگر مجاز نیست.');
            }

            $model->building_id = $current->id();
        });

        static::updating(function (Model $model) {
            $current = app(CurrentBuilding::class);

            if ($current->bypassing()) {
                return;
            }

            if ($current->id() === null) {
                throw new TenantException('ساختمان فعالی تنظیم نشده است.');
            }

            if ((int) $model->getRawOriginal('building_id') !== $current->id()
                || (int) $model->building_id !== $current->id()) {
                throw new TenantException('تغییر ساختمان رکورد یا ویرایش رکورد ساختمان دیگر مجاز نیست.');
            }
        });
    }
}
