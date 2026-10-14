<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->unsignedSmallInteger('jalali_year');
            $table->unsignedTinyInteger('jalali_month');
            $table->date('calc_date');
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('total_expenses')->default(0);
            $table->json('settings_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['building_id', 'jalali_year', 'jalali_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_periods');
    }
};
