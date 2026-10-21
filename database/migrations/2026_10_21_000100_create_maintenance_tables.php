<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('maintenance_assets', function (Blueprint $table) {
   $table->id(); $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
   $table->string('name',150); $table->string('category',80)->nullable(); $table->string('location',150)->nullable();
   $table->string('manufacturer',120)->nullable(); $table->string('model',120)->nullable(); $table->string('serial_number',120)->nullable();
   $table->date('installed_at')->nullable(); $table->date('warranty_until')->nullable(); $table->text('notes')->nullable();
   $table->boolean('is_active')->default(true); $table->timestamps(); $table->index(['building_id','is_active']);
  });
  Schema::create('maintenance_plans', function (Blueprint $table) {
   $table->id(); $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
   $table->foreignId('asset_id')->constrained('maintenance_assets')->cascadeOnDelete(); $table->string('title',180);
   $table->text('description')->nullable(); $table->string('frequency',20)->default('monthly'); $table->unsignedSmallInteger('interval_days')->nullable();
   $table->date('next_due_at'); $table->unsignedBigInteger('estimated_cost')->nullable();
   $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); $table->boolean('is_active')->default(true);
   $table->timestamps(); $table->index(['building_id','is_active','next_due_at']);
  });
  Schema::create('maintenance_records', function (Blueprint $table) {
   $table->id(); $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
   $table->foreignId('asset_id')->constrained('maintenance_assets')->cascadeOnDelete();
   $table->foreignId('plan_id')->nullable()->constrained('maintenance_plans')->nullOnDelete();
   $table->string('title',180); $table->date('performed_at'); $table->unsignedBigInteger('cost')->default(0);
   $table->text('notes')->nullable(); $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
   $table->timestamps(); $table->index(['building_id','performed_at']);
  });
 }
};