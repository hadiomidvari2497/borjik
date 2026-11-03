<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->string('mobile', 15)->nullable()->unique();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->boolean('two_factor_sms_enabled')->default(false);
        });
    }
    public function down(): void {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropUnique(['mobile']);
            $table->dropColumn(['mobile', 'mobile_verified_at', 'two_factor_sms_enabled']);
        });
    }
};
