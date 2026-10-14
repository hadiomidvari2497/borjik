<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->string('number', 30);
            $table->string('kind', 10)->default('task');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 20);
            $table->string('priority', 10)->default('medium');
            $table->dateTime('deadline')->nullable();
            $table->string('status', 20)->default('new');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('location', 150)->nullable();
            $table->foreignId('target_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['building_id', 'number']);
            $table->index(['building_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
