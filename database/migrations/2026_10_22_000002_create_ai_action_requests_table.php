<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_action_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_access_token_id')->nullable()->index();
            $table->string('action', 60)->index();
            $table->json('payload')->nullable();
            $table->string('reason', 500);
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_requests');
    }
};
