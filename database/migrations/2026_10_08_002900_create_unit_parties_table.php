<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('relation', 10);
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->timestamps();
            $table->index(['unit_id', 'to_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_parties');
    }
};
