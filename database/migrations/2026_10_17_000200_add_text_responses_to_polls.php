<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('polls', 'response_type')) {
            Schema::table('polls', function (Blueprint $table) {
                $table->string('response_type', 12)->default('choice')->after('title');
            });
        }
        if (! Schema::hasColumn('poll_votes', 'answer')) {
            Schema::table('poll_votes', function (Blueprint $table) {
                $table->text('answer')->nullable()->after('option_id');
            });
        }
        Schema::table('poll_votes', function (Blueprint $table) {
            $table->unsignedBigInteger('option_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('poll_votes', 'answer')) {
            Schema::table('poll_votes', function (Blueprint $table) { $table->dropColumn('answer'); });
        }
        if (Schema::hasColumn('polls', 'response_type')) {
            Schema::table('polls', function (Blueprint $table) { $table->dropColumn('response_type'); });
        }
    }
};
