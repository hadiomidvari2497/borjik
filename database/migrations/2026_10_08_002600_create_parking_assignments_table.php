<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('parking_id')->constrained('parkings')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at');
            $table->dateTime('released_at')->nullable();
            $table->unsignedBigInteger('open_parking_id')->nullable()->unique();
            $table->timestamps();
            $table->index(['parking_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_assignments');
    }
};
