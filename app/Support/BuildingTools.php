<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Str;

/**
 * تنظیمات سراسری ابزارهای منوی مدیریت ساختمان.
 * کلید هر ابزار نام route اصلی آن است تا تنظیمات به تغییرات مسیرهای فرعی وابسته نباشد.
 */
final class BuildingTools
{
    private const SETTING_KEY = 'building_tools.enabled';

    /** @return array<string, array{key:string,label:string,group:string,route:string}> */
    public static function all(): array
    {
        $tools = [];

        foreach (config('menu', []) as $group) {
            foreach ($group['items'] as $item) {
                $route = $item['route'] ?? '';
                // داشبورد و اشتراک مسیرهای پایه هستند، نه ابزارهای قابل خاموش‌کردن.
                if (in_array($route, ['dashboard', 'subscription.show'], true)) {
                    continue;
                }

                $tools[$route] = [
                    'key' => $route,
                    'label' => $item['label'],
                    'group' => $group['title'],
                    'route' => $route,
                ];
            }
        }

        return $tools;
    }

    /** @return array<string, bool> */
    public static function states(): array
    {
        $saved = SiteSetting::get(self::SETTING_KEY, []);
        $saved = is_array($saved) ? $saved : [];
        $states = [];

        foreach (self::all() as $key => $tool) {
            $states[$key] = array_key_exists($key, $saved)
                ? (bool) $saved[$key]
                : $key !== 'sms-templates.index';
        }

        return $states;
    }

    public static function enabled(string $key): bool
    {
        $states = self::states();

        return $states[$key] ?? true;
    }

    public static function routeEnabled(?string $routeName): bool
    {
        if (! $routeName) {
            return true;
        }

        $matchedKey = null;
        foreach (self::all() as $key => $tool) {
            $prefix = str_ends_with($key, '.index') ? substr($key, 0, -6) : $key;
            $matches = $routeName === $key || Str::is($prefix.'.*', $routeName);

            // مسیرهای اختصاصی‌تر مثل requests.reports بر مسیر عمومی requests.index اولویت دارند.
            if ($matches && ($matchedKey === null || strlen($key) > strlen($matchedKey))) {
                $matchedKey = $key;
            }
        }

        return $matchedKey === null || self::enabled($matchedKey);
    }

    /** @param array<string, bool> $states */
    public static function save(array $states): void
    {
        SiteSetting::set(self::SETTING_KEY, $states);
    }
}
