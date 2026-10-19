<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_samples', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('ok')->index();
            $table->json('metrics');
            $table->timestamp('sampled_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_samples');
    }
};
