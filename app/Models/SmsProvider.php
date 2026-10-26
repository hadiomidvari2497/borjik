<?php

namespace App\Models;

use App\Services\Sms\SmsProviderConfig;

use Illuminate\Database\Eloquent\Model;

/**
 * اتصال به یک اپراتور پیامکی (درایور HTTP عمومی). کل پیکربندی — از جمله کلیدها و رمزها — رمزنگاری‌شده ذخیره می‌شود.
 *
 * ساختار config: method, url, number_format, headers[{name,value}], body_type, body,
 * params[{name,value,secret}], success{json_path,expected,contains}, ref_path, timeout
 */
class SmsProvider extends Model
{
    protected $table = 'sms_providers';
    protected $guarded = ['id'];
    protected $hidden = ['config'];

    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'last_test_at' => 'datetime',
            'last_test_ok' => 'boolean',
        ];
    }

    /** اتصال پیش‌فرض فعال؛ null یعنی از درایور config/sms.php (log) استفاده شود. */
    public static function current(): ?self
    {
        return static::query()->where('is_default', true)->where('is_active', true)->first();
    }

    /** پیکربندی با مقادیر پیش‌فرض برای کلیدهای غایب. */
    public function settings(): array
    {
        return ($this->config ?? []) + [
            'method' => 'POST', 'url' => '', 'number_format' => 'local', 'headers' => [], 'body_type' => 'none',
            'body' => '', 'params' => [], 'template_body_ids' => [], 'body_id_fallback_provider_id' => null, 'success' => [], 'ref_path' => '', 'timeout' => 15,
        ];
    }

    /** @return list<string> مقدار پارامترهای محرمانه (برای پنهان‌کردن در پیام خطا) */
    public function secretValues(): array
    {
        return SmsProviderConfig::secretValues($this->settings());
    }
}
