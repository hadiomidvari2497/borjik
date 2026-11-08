<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_listing_chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('property_listing_inquiries')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('property_listing_chat_messages')->cascadeOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->index(['inquiry_id', 'created_at']);
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listing_chat_attachments');
    }
};
