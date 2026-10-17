<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            // سوپرادمین همهٔ مجوزها را دارد و به نقش وابسته نیست
            $table->boolean('is_super')->default(false)->after('status');
            $table->dateTime('last_login_at')->nullable()->after('is_super');
        });

        // پیش از وجود سیستم مجوزها همهٔ ادمین‌ها دسترسی کامل داشتند؛ برای جلوگیری از قفل‌شدن، همان‌ها سوپرادمین می‌شوند.
        DB::table('admin_users')->update(['is_super' => true]);
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn(['is_super', 'last_login_at']);
        });
    }
};
