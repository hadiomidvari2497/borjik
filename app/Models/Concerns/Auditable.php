<?php

namespace App\Models\Concerns;

use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * تاریخچه‌ی تغییرات (بند ۲.۳ سند): ایجاد، ویرایش، حذف و بازیابی هر رکورد با «چه کسی، چه زمانی، چه تغییری».
 * فقط تغییرهای انجام‌شده از طریق مدل ثبت می‌شود؛ پس به‌جای ->update()/->delete() روی Query Builder از ->get()->each(...) استفاده کنید.
 * ستون‌های اضافه برای نادیده‌گرفتن: protected array $auditExclude = ['col'];
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $m) {
            Audit::record($m, 'created', [], Audit::filter($m, $m->getAttributes()));
        });

        static::updated(function (Model $m) {
            $changes = Audit::filter($m, $m->getChanges());
            if ($changes === []) {
                return;
            }
            // بازیابی از حذف نرم را رویداد «restored» ثبت می‌کند، نه «updated»
            if (array_keys($changes) === ['deleted_at'] && $m->getAttribute('deleted_at') === null) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $m->getRawOriginal($key);
            }
            Audit::record($m, 'updated', $old, $changes);
        });

        static::deleted(function (Model $m) {
            // deleted_at در حذف نرم «مقدار جدید» است، نه مقدار قبلی
            Audit::record($m, 'deleted', Audit::filter($m, array_diff_key($m->getAttributes(), ['deleted_at' => 1])), []);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $m) {
                Audit::record($m, 'restored', [], ['deleted_at' => null]);
            });
        }
    }

    /** @return string[] */
    public function auditExcluded(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : [];
    }
}
