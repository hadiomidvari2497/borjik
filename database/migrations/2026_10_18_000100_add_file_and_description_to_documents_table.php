<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('file_id')->nullable()->after('uploaded_by')->constrained('files')->nullOnDelete();
            $table->text('description')->nullable()->after('title');
            $table->index(['building_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['building_id', 'category']);
            $table->dropConstrainedForeignId('file_id');
            $table->dropColumn('description');
        });
    }
};
