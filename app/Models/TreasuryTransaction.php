<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBuilding;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class TreasuryTransaction extends Model {
 use BelongsToBuilding, Auditable, SoftDeletes;
 protected $guarded = ['id'];
 protected function casts(): array { return ['transaction_date'=>'date','amount'=>'integer']; }
 public function recorder(){ return $this->belongsTo(User::class,'recorded_by'); }
}