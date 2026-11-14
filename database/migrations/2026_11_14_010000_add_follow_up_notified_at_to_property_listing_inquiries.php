<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_listing_inquiries', function (Blueprint $table) {
            $table->timestamp('follow_up_notified_at')->nullable();
            $table->index(['follow_up_at', 'follow_up_notified_at', 'status'], 'pli_followup_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('property_listing_inquiries', function (Blueprint $table) {
            $table->dropIndex('pli_followup_due_idx');
            $table->dropColumn('follow_up_notified_at');
        });
    }
};
