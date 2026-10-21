<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBuilding;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
class MaintenancePlan extends Model {
 use BelongsToBuilding, Auditable;
 protected $guarded = ['id'];
 protected function casts(): array { return ['next_due_at'=>'date','estimated_cost'=>'integer','is_active'=>'boolean']; }
 public function asset(){ return $this->belongsTo(MaintenanceAsset::class,'asset_id'); }
 public function assignee(){ return $this->belongsTo(User::class,'assigned_to'); }
 public function records(){ return $this->hasMany(MaintenanceRecord::class,'plan_id'); }
}