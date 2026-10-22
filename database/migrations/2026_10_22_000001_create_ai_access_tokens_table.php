<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('token_hash', 64)->unique();
            $table->string('token_prefix', 24);
            $table->json('abilities');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_access_tokens');
    }
};
