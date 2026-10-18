<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task_name', 190)->index();
            $table->string('status', 20)->default('running')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->longText('output')->nullable();
            $table->longText('error')->nullable();
            $table->timestamps();
            $table->index(['task_name', 'started_at']);
            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_execution_logs');
    }
};
