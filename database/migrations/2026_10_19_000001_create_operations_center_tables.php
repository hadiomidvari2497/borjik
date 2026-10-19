<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('operations_settings', function (Blueprint $table) {
            $table->id();
            $table->text('settings');
            $table->timestamps();
        });

        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->index();
            $table->string('filename')->nullable();
            $table->string('local_path', 1000)->nullable();
            $table->string('cloud_file_id')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('operations_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->index();
            $table->json('checks');
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_health_checks');
        Schema::dropIfExists('backup_runs');
        Schema::dropIfExists('operations_settings');
    }
};
