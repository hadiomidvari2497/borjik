<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('treasury_transactions', function (Blueprint $table) {
   $table->id(); $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
   $table->string('type',10); $table->string('category',100); $table->string('title',180);
   $table->unsignedBigInteger('amount'); $table->date('transaction_date'); $table->string('payment_method',30)->default('transfer');
   $table->string('reference',120)->nullable(); $table->text('notes')->nullable();
   $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
   $table->softDeletes(); $table->timestamps(); $table->index(['building_id','transaction_date','type']);
  });
 }
};