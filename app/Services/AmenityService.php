<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * اختصاص و آزادسازی پارکینگ و انباری. $cfg همان آرایه‌ی تنظیم AmenityController است
 * (model، assignment، fk، open، title). هر واحد می‌تواند چند پارکینگ یا انباری داشته باشد،
 * ولی هر پارکینگ هم‌زمان فقط یک اختصاص باز دارد (ستون open_*_id یکتا).
 */
class AmenityService
{
    public function assign(array $cfg, Model $item, Unit $unit, User $by): void
    {
        $taken = new DomainException("این {$cfg['title']} هم‌اکنون به واحد دیگری اختصاص دارد.");

        try {
            DB::transaction(function () use ($cfg, $item, $unit, $by, $taken) {
                $locked = $cfg['model']::lockForUpdate()->findOrFail($item->id);

                if ($cfg['assignment']::where($cfg['fk'], $locked->id)->whereNull('released_at')->exists()) {
                    throw $taken;
                }

                $cfg['assignment']::create([
                    $cfg['fk'] => $locked->id,
                    'unit_id' => $unit->id,
                    'assigned_by' => $by->id,
                    'assigned_at' => now(),
                    $cfg['open'] => $locked->id,
                ]);
                $locked->update(['status' => 'assigned']);
            });
        } catch (UniqueConstraintViolationException) {
            throw $taken; // دو درخواست هم‌زمان؛ دومی توسط ایندکس یکتا رد شد
        }
    }

    public function release(array $cfg, Model $item): void
    {
        DB::transaction(function () use ($cfg, $item) {
            $locked = $cfg['model']::lockForUpdate()->findOrFail($item->id);
            $open = $cfg['assignment']::where($cfg['fk'], $locked->id)->whereNull('released_at')->first();

            if (! $open) {
                throw new DomainException("این {$cfg['title']} اختصاص بازی ندارد.");
            }

            $open->update(['released_at' => now(), $cfg['open'] => null]);
            $locked->update(['status' => 'free']);
        });
    }
}
