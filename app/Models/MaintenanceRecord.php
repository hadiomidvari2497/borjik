<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
class MaintenanceRecord extends Model {
 use BelongsToBuilding;
 protected $guarded = ['id'];
 protected function casts(): array { return ['performed_at'=>'date','cost'=>'integer']; }
 public function asset(){ return $this->belongsTo(MaintenanceAsset::class,'asset_id'); }
 public function plan(){ return $this->belongsTo(MaintenancePlan::class,'plan_id'); }
 public function performer(){ return $this->belongsTo(User::class,'performed_by'); }
}