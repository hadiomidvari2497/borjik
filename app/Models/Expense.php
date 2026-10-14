<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use BelongsToBuilding, SoftDeletes;

    protected $table = 'expenses';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function period() { return $this->belongsTo(ChargePeriod::class, 'period_id'); }
    public function category() { return $this->belongsTo(ExpenseCategory::class, 'category_id'); }
    public function enteredBy() { return $this->belongsTo(User::class, 'entered_by'); }
}
