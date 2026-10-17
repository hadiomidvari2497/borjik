<?php

namespace App\Models;

use App\Enums\BuildingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class Building extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'buildings';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => BuildingType::class,
            'built_at' => 'date',
        ];
    }

    public function province() { return $this->belongsTo(Province::class, 'province_id'); }
    public function city() { return $this->belongsTo(City::class, 'city_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function subscription() { return $this->belongsTo(Subscription::class, 'subscription_id'); }

    public function blocks() { return $this->hasMany(Block::class); }
    public function units() { return $this->hasMany(Unit::class); }
    public function chargeSettings() { return $this->hasOne(ChargeSetting::class); }
    public function members() { return $this->hasMany(BuildingUserRole::class); }
}
