<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->boolean('is_anonymous')->default(false)->after('audience');
        });

        Schema::table('poll_votes', function (Blueprint $table) {
            $table->dropUnique('poll_votes_poll_id_unit_id_unique');
            $table->unique(['poll_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('poll_votes', function (Blueprint $table) {
            $table->dropUnique('poll_votes_poll_id_user_id_unique');
            $table->unique(['poll_id', 'unit_id']);
        });

        Schema::table('polls', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_anonymous']);
        });
    }
};
