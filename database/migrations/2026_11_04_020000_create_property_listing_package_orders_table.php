<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_listing_package_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('amount');
            $table->string('gateway', 30);
            $table->string('gateway_id', 100);
            $table->string('authority', 120)->nullable()->unique();
            $table->string('id_get', 120)->nullable()->unique();
            $table->string('ref_id', 120)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listing_package_orders');
    }
};