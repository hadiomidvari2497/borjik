<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_destination_mode', 10)->default('last');
            $table->unsignedBigInteger('preferred_building_user_role_id')->nullable()->index();
            $table->unsignedBigInteger('last_building_user_role_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['preferred_building_user_role_id']);
            $table->dropIndex(['last_building_user_role_id']);
            $table->dropColumn(['login_destination_mode', 'preferred_building_user_role_id', 'last_building_user_role_id']);
        });
    }
};
