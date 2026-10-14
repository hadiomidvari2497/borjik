<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->unsignedTinyInteger('method')->default(1);
            $table->string('mode', 10)->default('rate');
            $table->unsignedBigInteger('fixed_amount')->default(0);
            $table->unsignedBigInteger('area_rate')->default(0);
            $table->unsignedBigInteger('person_rate')->default(0);
            $table->unsignedTinyInteger('person_ratio_pct')->default(20);
            $table->unsignedTinyInteger('area_ratio_pct')->default(80);
            $table->unsignedTinyInteger('calc_day')->default(25);
            $table->unsignedTinyInteger('warn_days_before')->default(5);
            $table->string('emergency_phone', 15)->nullable();
            $table->unsignedTinyInteger('due_days')->default(10);
            $table->boolean('late_fee_enabled')->default(false);
            $table->string('late_fee_type', 15)->nullable();
            $table->unsignedBigInteger('late_fee_value')->default(0);
            $table->unsignedBigInteger('late_fee_cap')->nullable();
            $table->unsignedTinyInteger('late_fee_start_day')->default(1);
            $table->timestamps();
            $table->unique(['building_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_settings');
    }
};
