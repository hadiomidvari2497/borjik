<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('name', 100);
            $table->unsignedTinyInteger('floors_count')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['building_id', 'number']);
            $table->unique(['building_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
