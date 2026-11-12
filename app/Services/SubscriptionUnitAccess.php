<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Subscription;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class SubscriptionUnitAccess
{
    public function totalUnits(Building $building): int
    {
        return Unit::query()->where('building_id', $building->id)->count();
    }

    public function unitsForSelection(Building $building): Collection
    {
        return Unit::query()->with('block')->where('building_id', $building->id)
            ->orderBy('block_id')->orderByRaw('cast(number as unsigned), number')->get();
    }

    public function activeIds(Subscription $subscription): array
    {
        if (is_array($subscription->active_unit_ids)) {
            return array_values(array_unique(array_map('intval', $subscription->active_unit_ids)));
        }

        return Unit::query()->where('building_id', $subscription->building_id)
            ->orderBy('id')->limit((int) $subscription->units_limit)->pluck('id')
            ->map(fn ($id) => (int) $id)->all();
    }

    public function needsSelection(Subscription $subscription): bool
    {
        $building = $subscription->building;
        if (! $building) {
            return false;
        }

        $total = $this->totalUnits($building);
        if (is_array($subscription->active_unit_ids)) {
            $selectedCount = Unit::query()->where('building_id', $subscription->building_id)
                ->whereIn('id', $subscription->active_unit_ids)->count();

            return $selectedCount !== min($total, (int) $subscription->units_limit);
        }

        return $total > (int) $subscription->units_limit;
    }

    public function initializeForPlan(Subscription $subscription): void
    {
        $total = DB::table('units')->where('building_id', $subscription->building_id)->whereNull('deleted_at')->count();
        $subscription->forceFill([
            'active_unit_ids' => $total > (int) $subscription->units_limit ? [] : null,
        ])->save();
    }

    public function saveSelection(Subscription $subscription, array $ids): void
    {
        $building = $subscription->building;
        if (! $building) {
            throw ValidationException::withMessages(['unit_ids' => 'ساختمان این اشتراک در دسترس نیست.']);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $total = $this->totalUnits($building);
        $required = min($total, (int) $subscription->units_limit);

        if (count($ids) !== $required) {
            throw ValidationException::withMessages([
                'unit_ids' => 'باید دقیقاً '. $required .' واحد را برای این اشتراک انتخاب کنید.',
            ]);
        }

        $valid = Unit::query()->where('building_id', $building->id)->whereIn('id', $ids)->count();
        if ($valid !== count($ids)) {
            throw ValidationException::withMessages(['unit_ids' => 'یک یا چند واحد انتخاب‌شده متعلق به این ساختمان نیست.']);
        }

        $subscription->forceFill(['active_unit_ids' => $ids])->save();
    }
}
