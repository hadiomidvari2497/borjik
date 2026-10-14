<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('location', 150)->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->date('shift_date')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
            $table->index(['building_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_shifts');
    }
};
