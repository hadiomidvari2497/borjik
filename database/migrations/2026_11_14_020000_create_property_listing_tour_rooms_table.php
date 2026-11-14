<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_listing_tour_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained('property_listings')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('image_path', 500);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['property_listing_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listing_tour_rooms');
    }
};
