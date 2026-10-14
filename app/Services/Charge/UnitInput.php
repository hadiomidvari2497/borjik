<?php

namespace App\Services\Charge;

use App\Models\Unit;

/** ورودی خالص موتور برای یک واحد (بدون وابستگی به دیتابیس). متراژ به صدم متر ذخیره می‌شود تا محاسبات صحیح بمانند. */
final class UnitInput
{
    public function __construct(
        public readonly int $id,
        public readonly int $area100,
        public readonly int $persons,
        public readonly string $status = 'vacant',
        public readonly ?int $bedrooms = null,
        public readonly bool $hasParking = false,
        public readonly bool $hasStorage = false,
    ) {
    }

    public static function fromModel(Unit $unit, bool $hasParking, bool $hasStorage): self
    {
        return new self(
            $unit->id,
            (int) round(((float) $unit->area_m2) * 100),
            (int) $unit->residents_count,
            $unit->status->value,
            $unit->bedrooms,
            $hasParking,
            $hasStorage,
        );
    }
}
