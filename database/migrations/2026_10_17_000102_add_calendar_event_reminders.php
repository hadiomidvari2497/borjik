<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->unsignedSmallInteger('reminder_minutes')->nullable()->after('color');
            $table->timestamp('reminder_sent_at')->nullable()->after('reminder_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn(['reminder_minutes', 'reminder_sent_at']);
        });
    }
};
