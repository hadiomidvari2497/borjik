<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('building_id')->nullable()->after('user_id')->constrained('buildings')->nullOnDelete();
            $table->index(['building_id', 'status']);
        });

        // Keep existing building access intact while splitting any shared legacy subscription
        // into one independent subscription record per building.
        DB::transaction(function () {
            $buildings = DB::table('buildings')->whereNotNull('subscription_id')->orderBy('id')->get();
            $grouped = $buildings->groupBy('subscription_id');

            foreach ($grouped as $subscriptionId => $items) {
                $source = DB::table('subscriptions')->where('id', $subscriptionId)->first();
                if (! $source) {
                    continue;
                }

                foreach ($items->values() as $index => $building) {
                    if ($index === 0) {
                        DB::table('subscriptions')->where('id', $subscriptionId)->update([
                            'building_id' => $building->id,
                            'updated_at' => now(),
                        ]);
                        continue;
                    }

                    $copy = (array) $source;
                    unset($copy['id']);
                    $copy['building_id'] = $building->id;
                    $copy['created_at'] = now();
                    $copy['updated_at'] = now();
                    $newId = DB::table('subscriptions')->insertGetId($copy);

                    DB::table('buildings')->where('id', $building->id)->update([
                        'subscription_id' => $newId,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['building_id', 'status']);
            $table->dropConstrainedForeignId('building_id');
        });
    }
};
