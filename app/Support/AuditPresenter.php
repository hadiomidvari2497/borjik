<?php

namespace App\Support;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/** تبدیل رکورد خام AuditLog به متن فارسیِ قابل‌نمایش (برچسب ستون‌ها، مبلغ، تاریخ شمسی، وضعیت‌ها). */
final class AuditPresenter
{
    private const MAX_FIELDS = 8;
    private const TZ = 'Asia/Tehran';

    /** @return array{time:string,actor:string,event:string,event_label:string,entity:string,url:?string,changes:array<int,array{field:string,old:?string,new:?string}>,more:int} */
    public static function describe(AuditLog $log): array
    {
        $cfg = config('audit.models')[$log->auditable_type] ?? ['label' => class_basename((string) $log->auditable_type)];
        $old = (array) ($log->old_values ?? []);
        $new = (array) ($log->new_values ?? []);

        $changes = [];
        if ($log->event === 'updated') {
            foreach ($new as $field => $value) {
                $changes[] = ['field' => self::field($field), 'old' => self::value($field, $old[$field] ?? null), 'new' => self::value($field, $value)];
            }
        } else {
            $snapshot = $log->event === 'deleted' ? $old : $new;
            unset($snapshot['building_id']);
            foreach ($snapshot as $field => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $changes[] = ['field' => self::field($field), 'old' => $log->event === 'deleted' ? self::value($field, $value) : null, 'new' => $log->event === 'deleted' ? null : self::value($field, $value)];
            }
        }
        $more = max(0, count($changes) - self::MAX_FIELDS);

        return [
            'time' => self::time($log->created_at),
            'actor' => $log->user?->display_name ?? ($log->admin_id ? 'ادمین سامانه' : 'سیستم'),
            'event' => (string) $log->event,
            'event_label' => config('audit.events')[$log->event] ?? (string) $log->event,
            'entity' => $cfg['label'].' '.Fa::digits('#'.$log->auditable_id),
            'url' => (isset($cfg['route']) && $log->event !== 'deleted') ? route($cfg['route'], $log->auditable_id) : null,
            'changes' => array_slice($changes, 0, $log->event === 'updated' ? count($changes) : self::MAX_FIELDS),
            'more' => $log->event === 'updated' ? 0 : $more,
        ];
    }

    /** تاریخچه‌ی یک رکورد، تازه‌ترین اول. @return array<int, array> */
    public static function forModel(Model $model, int $limit = 20): array
    {
        return AuditLog::with('user')->where('auditable_type', $model::class)->where('auditable_id', $model->getKey())
            ->orderByDesc('id')->limit($limit)->get()->map(fn (AuditLog $l) => self::describe($l))->all();
    }

    public static function field(string $field): string
    {
        return config('audit.fields')[$field] ?? $field;
    }

    public static function value(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_array($value)) {
            return Fa::digits(json_encode($value, JSON_UNESCAPED_UNICODE));
        }
        if (in_array($field, config('audit.booleans'), true)) {
            return in_array($value, [1, '1', true, 'true'], true) ? 'بله' : 'خیر';
        }
        if (in_array($field, config('audit.money'), true) && is_numeric($value)) {
            return Fa::money((int) $value);
        }
        if (str_ends_with($field, '_id') && is_numeric($value)) {
            return Fa::digits('#'.$value);
        }
        if (is_string($value) && preg_match('/(^|_)(date|at|deadline)$/', $field)) {
            return self::dateValue($value);
        }
        if (is_string($value) && isset(config('audit.values')[$value])) {
            return config('audit.values')[$value];
        }

        return Fa::digits((string) $value);
    }

    private static function dateValue(string $value): string
    {
        try {
            $c = Carbon::parse($value);
        } catch (\Throwable) {
            return Fa::digits($value);
        }
        if ($c->format('H:i:s') === '00:00:00') {
            return Fa::date($c, 'Y/m/d');
        }

        return self::time($c);
    }

    private static function time(?Carbon $c): string
    {
        if (! $c) {
            return '—';
        }
        $t = $c->copy()->timezone(self::TZ);

        return Fa::date($t, 'Y/m/d').' '.Fa::digits($t->format('H:i'));
    }
}
