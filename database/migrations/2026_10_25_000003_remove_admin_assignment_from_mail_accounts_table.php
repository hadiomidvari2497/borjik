<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table) {
            $table->dropIndex('mail_accounts_admin_user_id_is_active_index');
            $table->dropConstrainedForeignId('admin_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table) {
            $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->index(['admin_user_id', 'is_active']);
        });
    }
};
