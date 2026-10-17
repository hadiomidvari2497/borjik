<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Concerns\BelongsToBuilding;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Auditable;

class Invoice extends Model
{
    use BelongsToBuilding, Auditable;

    protected $table = 'invoices';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'issued_at' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'breakdown' => 'array',
        ];
    }

    public function building() { return $this->belongsTo(Building::class); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function period() { return $this->belongsTo(ChargePeriod::class, 'period_id'); }
    public function contract() { return $this->belongsTo(Contract::class, 'contract_id'); }
    public function payerUser() { return $this->belongsTo(User::class, 'payer_user_id'); }
    public function parentInvoice() { return $this->belongsTo(Invoice::class, 'parent_invoice_id'); }

    public function allocations() { return $this->hasMany(PaymentAllocation::class); }
}
