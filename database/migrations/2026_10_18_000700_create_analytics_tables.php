<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_key', 128)->unique();
            $table->string('visitor_id', 64)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            // DATETIME avoids legacy MySQL TIMESTAMP default-value restrictions.
            $table->dateTime('started_at')->index();
            $table->dateTime('last_seen_at')->index();
            $table->dateTime('ended_at')->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('exit_page', 500)->nullable();
            $table->unsignedInteger('pageviews')->default(0);
            $table->unsignedInteger('active_seconds')->default(0);
            $table->boolean('engaged')->default(false);
            $table->string('source', 190)->default('direct');
            $table->string('medium', 100)->default('none');
            $table->string('campaign', 190)->nullable();
            $table->string('term', 190)->nullable();
            $table->string('content', 190)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->string('device_type', 40)->default('unknown');
            $table->string('device_brand', 80)->default('unknown');
            $table->string('browser', 80)->default('unknown');
            $table->string('browser_version', 80)->nullable();
            $table->string('os', 80)->default('unknown');
            $table->string('os_version', 80)->nullable();
            $table->unsignedSmallInteger('screen_width')->nullable();
            $table->unsignedSmallInteger('screen_height')->nullable();
            $table->unsignedSmallInteger('viewport_width')->nullable();
            $table->unsignedSmallInteger('viewport_height')->nullable();
            $table->decimal('pixel_ratio', 4, 2)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['last_seen_at', 'user_id']);
            $table->index(['started_at', 'source', 'medium']);
        });

        Schema::create('analytics_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('session_key', 128)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('path', 500);
            $table->string('title', 255)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->dateTime('viewed_at')->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('active_seconds')->default(0);
            $table->boolean('is_engaged')->default(false);
            $table->timestamps();
            $table->index(['path', 'viewed_at']);
            $table->index(['session_key', 'path', 'viewed_at']);
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('session_key', 128)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event_name', 100)->index();
            $table->string('category', 100)->nullable();
            $table->string('label', 190)->nullable();
            $table->string('path', 500)->nullable();
            $table->dateTime('created_at')->index();
            $table->index(['event_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('analytics_page_views');
        Schema::dropIfExists('analytics_sessions');
    }
};
