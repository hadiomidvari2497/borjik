<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('driver', 20)->default('http');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            // آدرس، هدرها و پارامترها (شامل کلیدهای محرمانه) — رمزنگاری‌شده با APP_KEY
            $table->text('config')->nullable();
            $table->dateTime('last_test_at')->nullable();
            $table->boolean('last_test_ok')->nullable();
            $table->string('last_test_note', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('sms_logs', function (Blueprint $table) {
            $table->string('gateway', 60)->nullable()->after('provider_ref');
            $table->string('error', 255)->nullable()->after('gateway');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropColumn(['gateway', 'error']);
        });
        Schema::dropIfExists('sms_providers');
    }
};
