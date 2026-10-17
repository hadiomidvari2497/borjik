<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class ChargePeriod extends Model
{
    use BelongsToBuilding, Auditable;

    /** تصویر کاملِ تنظیمات دوره حجیم است و در تاریخچه نمی‌آید. */
    protected array $auditExclude = ['settings_snapshot'];

    protected $table = 'charge_periods';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'calc_date' => 'date',
            'status' => PeriodStatus::class,
            'settings_snapshot' => 'array',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }

    public function expenses() { return $this->hasMany(Expense::class, 'period_id'); }
    public function invoices() { return $this->hasMany(Invoice::class, 'period_id'); }
}
