<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('property_listing_tour_rooms', function (Blueprint $table) {
            $table->string('projection', 24)->default('equirectangular')->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('property_listing_tour_rooms', function (Blueprint $table) {
            $table->dropColumn('projection');
        });
    }
};
