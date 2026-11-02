<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->foreignId('payment_receipt_id')->nullable()->after('building_id')
                ->constrained('payment_receipts')->restrictOnDelete();
            $table->unique('payment_receipt_id');
        });

        DB::table('payment_receipts as receipts')
            ->join('units', 'units.id', '=', 'receipts.unit_id')
            ->join('blocks', 'blocks.id', '=', 'units.block_id')
            ->where('receipts.status', 'approved')
            ->select([
                'receipts.id',
                'receipts.building_id',
                'receipts.amount',
                'receipts.paid_at',
                'receipts.reviewed_by',
                'blocks.name as block_name',
                'units.number as unit_number',
            ])
            ->orderBy('receipts.id')
            ->chunk(500, function ($receipts): void {
                foreach ($receipts as $receipt) {
                    DB::table('treasury_transactions')->insertOrIgnore([
                        'building_id' => $receipt->building_id,
                        'payment_receipt_id' => $receipt->id,
                        'type' => 'income',
                        'category' => 'دریافت فیش پرداخت',
                        'title' => 'دریافت فیش واحد '.$receipt->block_name.' / '.$receipt->unit_number,
                        'amount' => $receipt->amount,
                        'transaction_date' => $receipt->paid_at,
                        'payment_method' => 'transfer',
                        'reference' => 'فیش #'.$receipt->id,
                        'notes' => 'ثبت خودکار سوابق فیش تأییدشده هنگام یکپارچه‌سازی دفتر خزانه.',
                        'recorded_by' => $receipt->reviewed_by,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->dropUnique(['payment_receipt_id']);
            $table->dropConstrainedForeignId('payment_receipt_id');
        });
    }
};
