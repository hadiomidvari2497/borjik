<?php

namespace App\Services\Sms;

/** توابع کمکی خالص برای پیکربندی اتصال پیامکی (بدون وابستگی به دیتابیس). */
final class SmsProviderConfig
{
    /** متغیرهای همیشه در دسترس قالب‌ها: {{mobile}} طبق «قالب شماره»، و سه شکل ثابت. */
    public const BUILTIN = ['mobile', 'mobile_local', 'mobile_intl', 'mobile_plus', 'message'];

    /** @return list<string> */
    public static function secretValues(array $settings): array
    {
        $out = [];
        foreach ($settings['params'] ?? [] as $p) {
            if (! empty($p['secret']) && ($p['value'] ?? '') !== '') {
                $out[] = (string) $p['value'];
            }
        }

        return $out;
    }

    /** @return list<string> نام متغیرهای {{...}} موجود در یک متن */
    public static function placeholdersIn(string $template): array
    {
        preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/', $template, $m);

        return array_values(array_unique($m[1]));
    }

    /** @return array{mobile:string, mobile_local:string, mobile_intl:string, mobile_plus:string} */
    public static function mobileForms(string $mobile, string $format = 'local'): array
    {
        $local = $mobile;                                   // 09123456789
        $intl = '98'.substr($local, 1);                     // 989123456789
        $plus = '+'.$intl;                                  // +989123456789

        return [
            'mobile' => ['intl' => $intl, 'plus' => $plus][$format] ?? $local,
            'mobile_local' => $local,
            'mobile_intl' => $intl,
            'mobile_plus' => $plus,
        ];
    }

    /**
     * جایگزینی {{var}} در یک متن. حالت‌ها:
     *  - url: مقدار با rawurlencode (برای مسیر و کوئری)
     *  - json: مقدار با escape رشتهٔ JSON (بدون کوتیشن) تا داخل "..." امن باشد
     *  - raw: بدون تغییر (هدر، فرم، متن خام)
     * متغیر ناشناخته به رشتهٔ خالی تبدیل می‌شود (اعتبارسنجی هنگام ذخیره آن را رد می‌کند).
     */
    public static function render(string $template, array $vars, string $mode = 'raw'): string
    {
        return preg_replace_callback('/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/', function (array $m) use ($vars, $mode) {
            $v = (string) ($vars[$m[1]] ?? '');

            return match ($mode) {
                'url' => rawurlencode($v),
                'json' => substr(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
                default => $v,
            };
        }, $template) ?? $template;
    }

    /** «name=value» در هر خط؛ خط خالی و خطی که با # شروع شود نادیده گرفته می‌شود. @return array<string,string> */
    public static function parseFormLines(string $body, array $vars): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $body) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $out[trim($k)] = self::render(trim($v), $vars, 'raw');
        }

        return $out;
    }
}
