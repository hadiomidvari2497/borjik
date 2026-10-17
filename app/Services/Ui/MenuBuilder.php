<?php

namespace App\Services\Ui;

use App\Models\Ticket;
use App\Models\PaymentReceipt;
use App\Models\User;
use App\Support\CurrentBuilding;
use Illuminate\Support\Facades\Route;

/** منوی کناری نقش‌محور: فقط آیتم‌هایی که کاربر در ساختمان فعال مجوزشان را دارد؛ گروه خالی نمایش داده نمی‌شود. */
class MenuBuilder
{
    private array $counts = [];

    /**
     * منوی «پیش‌نمایش» برای کاربر بدون ساختمان: امکانات مالک دیده می‌شود ولی قفل است،
     * به‌جز داشبورد و «اشتراک» که برای خرید/تمدید باید همیشه باز باشد.
     */
    public function preview(): array
    {
        $ownerPerms = \Illuminate\Support\Facades\DB::table('role_permission as rp')
            ->join('roles as r', 'r.id', '=', 'rp.role_id')->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('r.key', 'owner')->pluck('p.key')->all();

        $menu = [];
        foreach (config('menu') as $group) {
            $items = [];
            foreach ($group['items'] as $item) {
                $isOwnerItem = ! str_starts_with($item['route'], 'my.')
                    && ($item['perm'] === null || array_intersect((array) $item['perm'], $ownerPerms));
                if (! $isOwnerItem) {
                    continue;
                }
                $isHome = $item['route'] === 'dashboard';
                $isSubscription = str_starts_with($item['route'], 'subscription.');
                $routeName = $isSubscription && Route::has('subscription.show')
                    ? 'subscription.show'
                    : $item['route'];
                $exists = Route::has($routeName);
                $items[] = [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'url' => $isHome
                        ? route('dashboard')
                        : ($isSubscription && $exists
                            ? route($routeName)
                            : route('dashboard').'#features'),
                    'active' => $isHome
                        ? request()->routeIs('dashboard')
                        : ($isSubscription && request()->routeIs('subscription.*')),
                    'badge' => 0,
                    'locked' => ! $isHome && ! $isSubscription,
                ];
            }
            if ($items) {
                $menu[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return $menu;
    }

    public function build(User $user): array
    {
        $menu = [];

        foreach (config('menu') as $group) {
            $items = [];
            foreach ($group['items'] as $item) {
                // «واحد من» برای نقش‌های مالکیتی/مدیریتی نمایش داده نمی‌شود.
                if ($item['route'] === 'my.units' && app(CurrentBuilding::class)->hasRole('owner', 'manager')) {
                    continue;
                }

                // «درخواست‌های من» و «قرارداد من» مخصوص ساکنان هستند؛ پرسنل و نقش‌های مدیریتی از بخش‌های ساختمان استفاده می‌کنند.
                if (in_array($item['route'], ['my.requests', 'my.contracts'], true)
                    && app(CurrentBuilding::class)->hasRole('owner', 'manager', 'security', 'cleaning', 'maintenance')) {
                    continue;
                }

                if (! $this->allowed($user, $item['perm'])) {
                    continue;
                }
                $exists = Route::has($item['route']);
                $items[] = [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'url' => $exists ? route($item['route']) : '#',
                    'active' => $exists && request()->routeIs(str_ends_with($item['route'], '.index') ? substr($item['route'], 0, -6).'.*' : $item['route']),
                    'badge' => $item['badge'] ? $this->badge($user, $item['badge']) : 0,
                    'locked' => false,
                ];
            }
            if ($items) {
                $menu[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return $menu;
    }

    private function allowed(User $user, string|array|null $perm): bool
    {
        if ($perm === null) {
            return true;
        }
        foreach ((array) $perm as $p) {
            if ($user->hasPermission($p)) {
                return true;
            }
        }

        return false;
    }

    private function badge(User $user, string $key): int
    {
        return $this->counts[$key] ??= match ($key) {
            'payments' => PaymentReceipt::where('status', 'pending')->count(),
            'tickets' => $this->openTickets($user, 'task'),
            'requests' => $this->openTickets($user, 'request'),
            'notifications' => $user->unreadNotifications()->where('building_id', app(CurrentBuilding::class)->id())->count(),
            default => 0,
        };
    }

    private function openTickets(User $user, string $kind): int
    {
        $q = Ticket::where('kind', $kind)->whereNotIn('status', ['done', 'closed']);

        if (! $user->hasPermission($kind === 'task' ? 'ticket.view.all' : 'request.view.all')) {
            $q->whereHas('assignees', fn ($a) => $a->where('user_id', $user->id));
        }

        return $q->count();
    }
}
