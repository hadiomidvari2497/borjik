<?php

namespace App\Support;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Route;

/** منوی پنل مدیریت سیستم را از config/admin_menu.php و مجوزهای ادمین می‌سازد. */
final class AdminMenu
{
    /**
     * @return list<array{title:string, items:list<array{label:string, icon:string, url:string, active:bool, desc:?string, route:string}>}>
     */
    public static function for(AdminUser $admin): array
    {
        $groups = [];

        foreach (config('admin_menu', []) as $group) {
            $items = [];
            foreach ($group['items'] as $item) {
                if (! Route::has($item['route'])) {
                    continue;
                }
                if (($item['perm'] ?? null) !== null && ! $admin->hasPermission($item['perm'])) {
                    continue;
                }
                $items[] = [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'url' => route($item['route']),
                    'active' => request()->routeIs($item['active'] ?? $item['route']),
                    'desc' => $item['desc'] ?? null,
                    'route' => $item['route'],
                ];
            }
            if ($items !== []) {
                $groups[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return $groups;
    }
}
