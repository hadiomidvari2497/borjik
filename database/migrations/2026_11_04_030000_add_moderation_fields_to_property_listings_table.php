<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->text('moderation_note')->nullable()->after('expires_at');
            $table->foreignId('moderated_by')->nullable()->after('moderation_note')
                ->constrained('admin_users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
        });
    }

    public function down(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['moderation_note', 'moderated_at']);
        });
    }
};