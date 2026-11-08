<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_listing_chat_messages', function (Blueprint $table) {
            $table->foreignId('reply_to_message_id')
                ->nullable()
                ->after('body')
                ->constrained('property_listing_chat_messages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('property_listing_chat_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_message_id']);
            $table->dropColumn('reply_to_message_id');
        });
    }
};
