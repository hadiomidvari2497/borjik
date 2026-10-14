<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained('blocks')->restrictOnDelete();
            $table->string('number', 20);
            $table->smallInteger('floor')->nullable();
            $table->string('status', 20)->default('free');
            $table->timestamps();
            $table->unique(['building_id', 'block_id', 'number']);
            $table->unique(['building_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storages');
    }
};
