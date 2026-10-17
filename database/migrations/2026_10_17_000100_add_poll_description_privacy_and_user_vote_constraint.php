<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('polls', 'description')) {
            Schema::table('polls', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }

        if (! Schema::hasColumn('polls', 'is_anonymous')) {
            Schema::table('polls', function (Blueprint $table) {
                $table->boolean('is_anonymous')->default(false)->after('audience');
            });
        }

        // Add the replacement index first: MySQL may need the old poll_id-leading
        // index to support the poll_id foreign key until the new index exists.
        if (! Schema::hasIndex('poll_votes', 'poll_votes_poll_id_user_id_unique')) {
            Schema::table('poll_votes', function (Blueprint $table) {
                $table->unique(['poll_id', 'user_id'], 'poll_votes_poll_id_user_id_unique');
            });
        }

        if (Schema::hasIndex('poll_votes', 'poll_votes_poll_id_unit_id_unique')) {
            Schema::table('poll_votes', function (Blueprint $table) {
                $table->dropUnique('poll_votes_poll_id_unit_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('poll_votes', 'poll_votes_poll_id_user_id_unique')) {
            Schema::table('poll_votes', function (Blueprint $table) {
                $table->dropUnique('poll_votes_poll_id_user_id_unique');
            });
        }

        if (! Schema::hasIndex('poll_votes', 'poll_votes_poll_id_unit_id_unique')) {
            Schema::table('poll_votes', function (Blueprint $table) {
                $table->unique(['poll_id', 'unit_id'], 'poll_votes_poll_id_unit_id_unique');
            });
        }

        if (Schema::hasColumn('polls', 'is_anonymous')) {
            Schema::table('polls', function (Blueprint $table) {
                $table->dropColumn('is_anonymous');
            });
        }

        if (Schema::hasColumn('polls', 'description')) {
            Schema::table('polls', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
