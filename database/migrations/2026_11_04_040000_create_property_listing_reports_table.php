<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_listing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained('property_listings')->cascadeOnDelete();
            $table->foreignId('reporter_user_id')->constrained('users')->restrictOnDelete();
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->index(['property_listing_id', 'reporter_user_id', 'status'], 'listing_reports_lookup_idx');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listing_reports');
    }
};