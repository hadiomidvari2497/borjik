<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_listing_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained('property_listings')->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('phone_clicks_count')->default(0);
            $table->timestamps();
            $table->unique(['property_listing_id', 'stat_date'], 'pl_daily_listing_date_unique');
            $table->index(['stat_date', 'property_listing_id'], 'pl_daily_date_listing_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listing_daily_stats');
    }
};
