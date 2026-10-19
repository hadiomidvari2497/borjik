<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Building;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/** نوشتن رکورد تاریخچه‌ی تغییرات؛ خطای ثبت لاگ هرگز عملیات اصلی را خراب نمی‌کند. */
final class Audit
{
    /** ستون‌هایی که هرگز ثبت نمی‌شوند (اسرار و ستون‌های پرنوسانِ بی‌اهمیت). */
    private const EXCLUDED = ['created_at', 'updated_at', 'password', 'remember_token', 'two_factor_secret', 'token_hash', 'last_login_at', 'api_token', 'access_token', 'refresh_token', 'client_secret', 'private_key', 'secret', 'authorization', 'otp', 'verification_code'];

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public static function filter(Model $model, array $attributes): array
    {
        $excluded = self::EXCLUDED;
        if (method_exists($model, 'auditExcluded')) {
            $excluded = array_merge($excluded, $model->auditExcluded());
        }

        $filtered = array_diff_key($attributes, array_flip($excluded));
        foreach (array_keys($filtered) as $key) {
            if (preg_match('/(?:password|secret|token|private.?key|authorization|otp|verification.?code|api.?key)/i', (string) $key)) {
                unset($filtered[$key]);
            }
        }

        return $filtered;
    }

    public static function record(Model $model, string $event, array $old, array $new): void
    {
        try {
            $console = app()->runningInConsole();
            $agent = $console ? null : mb_substr((string) request()->userAgent(), 0, 255);

            AuditLog::create([
                'building_id' => $model instanceof Building ? $model->getKey() : ($model->getAttribute('building_id') ?: null),
                'user_id' => Auth::guard('web')->id(),
                'admin_id' => config('auth.guards.admin') ? Auth::guard('admin')->id() : null,
                'auditable_type' => $model::class,
                'auditable_id' => $model->getKey(),
                'event' => $event,
                'old_values' => $old === [] ? null : $old,
                'new_values' => $new === [] ? null : $new,
                'ip' => $console ? null : request()->ip(),
                'user_agent' => $agent !== '' ? $agent : null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
