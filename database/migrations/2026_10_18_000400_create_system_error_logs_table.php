<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('exception_class', 255);
            $table->text('message');
            $table->string('file', 1000)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('route_name', 190)->nullable();
            $table->unsignedSmallInteger('status_code')->default(500);
            $table->string('actor_type', 20)->default('guest');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('admin_user_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['created_at', 'status_code']);
            $table->index(['exception_class', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_logs');
    }
};
