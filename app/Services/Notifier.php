<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\BuildingNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ارسال اعلان درون‌سامانه‌ای (بند ۴.۱۸). خطای ارسال هرگز عملیات اصلی را خراب نمی‌کند.
 * گیرنده: User، شناسه‌ی کاربر، یا آرایه/مجموعه‌ای از آن‌ها. آدرس (url) باید مطلق باشد (route() مطلق می‌دهد).
 * گزینه‌ها: level (info|success|warning)، category، key (اعلان تکراری با همین کلید برای همان کاربر/ساختمان ارسال نمی‌شود)، label (متن لینک).
 */
class Notifier
{
    /** @param User|int|iterable<User|int>|null $recipients @param array{level?:string,category?:string,key?:string,label?:string} $options تعداد اعلان‌های ارسال‌شده را برمی‌گرداند */
    public function send(User|int|iterable|null $recipients, int $buildingId, string $event, string $title, string $message, ?string $url = null, array $options = []): int
    {
        $key = $options['key'] ?? null;
        $sent = 0;

        foreach ($this->resolve($recipients) as $user) {
            try {
                if ($key !== null && $user->notifications()->where('data->building_id', $buildingId)->where('data->key', $key)->exists()) {
                    continue;
                }
                $user->notify(new BuildingNotification(
                    $buildingId, $event, $title, $message, $url,
                    $options['level'] ?? 'info', $options['category'] ?? 'system', $key, $options['label'] ?? null,
                ));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }

    /** مالک و مدیرهای فعال یک ساختمان. @return Collection<int, User> */
    public function managers(int $buildingId): Collection
    {
        $ids = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.building_id', $buildingId)->where('bur.status', 'active')->whereIn('r.key', ['owner', 'manager'])
            ->pluck('bur.user_id')->unique();

        return User::whereIn('id', $ids)->get();
    }

    /** @return Collection<int, User> */
    private function resolve(User|int|iterable|null $recipients): Collection
    {
        if ($recipients === null) {
            return collect();
        }
        $items = collect($recipients instanceof User || is_int($recipients) ? [$recipients] : $recipients);

        $users = $items->filter(fn ($r) => $r instanceof User);
        $ids = $items->reject(fn ($r) => $r instanceof User)->filter()->map(fn ($r) => (int) $r)->diff($users->pluck('id'))->unique();
        if ($ids->isNotEmpty()) {
            $users = $users->merge(User::whereIn('id', $ids)->get());
        }

        return $users->unique('id')->values();
    }
}
