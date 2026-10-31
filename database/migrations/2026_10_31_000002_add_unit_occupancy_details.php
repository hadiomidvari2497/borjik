<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('occupancy_type', 20)->nullable()->after('residents_count');
            $table->string('representative_name', 150)->nullable()->after('occupancy_type');
            $table->string('representative_mobile', 15)->nullable()->after('representative_name');
            $table->unsignedTinyInteger('family_count')->default(0)->after('representative_mobile');
            $table->date('lease_start_date')->nullable()->after('family_count');
            $table->date('lease_end_date')->nullable()->after('lease_start_date');
            $table->index(['building_id', 'occupancy_type']);
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['building_id', 'occupancy_type']);
            $table->dropColumn(['occupancy_type', 'representative_name', 'representative_mobile', 'family_count', 'lease_start_date', 'lease_end_date']);
        });
    }
};
