<?php

namespace App\Models;

use App\Enums\ChargePayer;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Models\Concerns\BelongsToBuilding;
use App\Services\ContractService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use BelongsToBuilding, SoftDeletes;

    protected $table = 'contracts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'status' => ContractStatus::class,
            'signed_at' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'ended_at' => 'date',
            'charge_payer' => ChargePayer::class,
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Active->value);
    }

    public function isActiveRent(): bool
    {
        return $this->type === ContractType::Rent && $this->status === ContractStatus::Active;
    }

    /** فایل پیوست قرارداد (آخرین فایل بارگذاری‌شده) یا null. */
    public function attachedFile(): ?File
    {
        $link = FileLink::where('attachable_type', ContractService::FILE_TYPE)
            ->where('attachable_id', $this->id)->orderByDesc('id')->first();

        return $link?->file;
    }
}
