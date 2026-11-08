<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->boolean('allow_phone_call')->default(false)->after('show_phone');
            $table->boolean('allow_sms')->default(false)->after('allow_phone_call');
        });

        DB::table('property_listings')->where('show_phone', true)->update([
            'allow_phone_call' => true,
            'allow_sms' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->dropColumn(['allow_phone_call', 'allow_sms']);
        });
    }
};
