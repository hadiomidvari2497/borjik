<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialPriceAdjustment extends Model
{
    protected $table = 'financial_price_adjustments';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }
}
