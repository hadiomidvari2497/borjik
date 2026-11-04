<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_listing_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_order_id')->nullable()->constrained('property_listing_package_orders')->restrictOnDelete();
            $table->foreignId('property_listing_id')->nullable()->constrained('property_listings')->nullOnDelete();
            $table->integer('quantity');
            $table->string('type', 20);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'id']);
            $table->unique(['package_order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listing_credit_transactions');
    }
};