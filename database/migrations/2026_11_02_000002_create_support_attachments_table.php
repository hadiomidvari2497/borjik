<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('support_message_id')->nullable()->constrained('support_messages')->cascadeOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('uploader_type', 20);
            $table->unsignedBigInteger('uploader_id');
            $table->timestamps();
            $table->index(['support_ticket_id', 'created_at']);
            $table->index(['uploader_type', 'uploader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_attachments');
    }
};
