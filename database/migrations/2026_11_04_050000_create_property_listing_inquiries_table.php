<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_listing_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained('property_listings')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_name', 180);
            $table->string('sender_mobile', 30)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_user_id', 'created_at']);
            $table->index(['sender_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listing_inquiries');
    }
};
