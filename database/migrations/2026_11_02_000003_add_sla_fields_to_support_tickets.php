<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dateTime('first_response_due_at')->nullable()->index();
            $table->dateTime('resolution_due_at')->nullable()->index();
            $table->dateTime('first_response_at')->nullable();
            $table->dateTime('first_response_warning_sent_at')->nullable();
            $table->dateTime('first_response_breached_at')->nullable();
            $table->dateTime('resolution_warning_sent_at')->nullable();
            $table->dateTime('resolution_breached_at')->nullable();
            $table->index(['status', 'resolution_due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['status', 'resolution_due_at']);
            $table->dropColumn([
                'first_response_due_at', 'resolution_due_at', 'first_response_at',
                'first_response_warning_sent_at', 'first_response_breached_at',
                'resolution_warning_sent_at', 'resolution_breached_at',
            ]);
        });
    }
};
