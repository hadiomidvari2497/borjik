<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('block_id')->constrained('blocks')->restrictOnDelete();
            $table->string('number', 20);
            $table->string('postal_code', 15)->nullable();
            $table->smallInteger('floor')->default(0);
            $table->decimal('area_m2', 8,2)->default(0);
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->string('direction', 10)->nullable();
            $table->string('status', 20)->default('vacant');
            $table->unsignedSmallInteger('residents_count')->default(0);
            $table->bigInteger('opening_balance')->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['building_id', 'block_id', 'number']);
            $table->unique(['building_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
