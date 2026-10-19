<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationsSetting extends Model
{
    protected $table = 'operations_settings';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['settings' => 'encrypted:array'];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['settings' => self::defaults()]);
    }

    public static function defaults(): array
    {
        return [
            'local_path' => storage_path('app/private/backups'),
            'backup_enabled' => false,
            'backup_frequency' => 'daily',
            'keep_local' => 14,
            'cloud_enabled' => false,
            'drive_client_id' => '',
            'drive_client_secret' => '',
            'drive_refresh_token' => '',
            'drive_folder_id' => '',
            'alert_enabled' => false,
            'alert_mobile' => '',
            'disk_warning_percent' => 85,
        ];
    }

    public function values(): array
    {
        return array_replace(self::defaults(), $this->settings ?? []);
    }
}
