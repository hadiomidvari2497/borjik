<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** تنظیمات سراسری سایت (key/value). مقدارها با کش نگهداری می‌شوند و با هر ذخیره پاک می‌شوند. */
class SiteSetting extends Model
{
    private const CACHE_KEY = 'site_settings.all';

    protected $table = 'site_settings';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_secret' => 'boolean',
        ];
    }

    /** @return array<string, mixed> */
    private static function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::cached();

        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }

        return $default ?? (config('site.defaults', [])[$key] ?? null);
    }

    public static function set(string $key, mixed $value, bool $secret = false): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'is_secret' => $secret]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function otpLoginEnabled(): bool
    {
        return (bool) self::get('auth.otp_enabled');
    }

    public static function passwordLoginEnabled(): bool
    {
        return (bool) self::get('auth.password_enabled');
    }
}
