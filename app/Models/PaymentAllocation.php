<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use BelongsToBuilding;

    protected $table = 'payment_allocations';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function receipt() { return $this->belongsTo(PaymentReceipt::class, 'receipt_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class, 'invoice_id'); }
}
