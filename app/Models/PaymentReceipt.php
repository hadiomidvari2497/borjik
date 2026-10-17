<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class PaymentReceipt extends Model
{
    use BelongsToBuilding, Auditable;

    protected $table = 'payment_receipts';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'status' => ReceiptStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function reviewedBy() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function allocations() { return $this->hasMany(PaymentAllocation::class, 'receipt_id'); }
}
