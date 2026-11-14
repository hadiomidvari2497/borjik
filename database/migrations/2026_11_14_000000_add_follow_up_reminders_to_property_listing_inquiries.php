<?php

use Illuminate\Database\\Migrations\\Migration;
use Illuminate\Database\\Schema\\Blueprint;
use Illuminate\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('property_listing_inquiries', function (Blueprint $table) {
            $table->timestamp('follow_up_at')->nullable()->index();
            $table->string('follow_up_note', 1000)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('property_listing_inquiries', function (Blueprint $table) {
            $table->dropIndex(['follow_up_at']);
            $table->dropColumn(['follow_up_at', 'follow_up_note']);
        });
    }
};
