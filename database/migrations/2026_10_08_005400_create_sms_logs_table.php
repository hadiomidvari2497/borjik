<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable()->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mobile', 15);
            $table->string('template_key', 50)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('queued');
            $table->string('provider_ref', 100)->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->index(['sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
