<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unsignedTinyInteger('satisfaction_rating')->nullable();
            $table->text('satisfaction_comment')->nullable();
            $table->dateTime('satisfaction_submitted_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['satisfaction_submitted_at']);
            $table->dropColumn(['satisfaction_rating', 'satisfaction_comment', 'satisfaction_submitted_at']);
        });
    }
};
