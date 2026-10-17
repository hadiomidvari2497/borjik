<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('building_id')->nullable()->after('id')->constrained('buildings')->cascadeOnDelete();
            $table->boolean('is_default')->default(false)->after('is_staff');
            $table->boolean('is_active')->default(true)->after('is_default');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_key_unique');
            $table->unique(['building_id', 'key']);
        });

        // Keep the global rows as templates. Every existing building receives its own
        // staff-role records, and memberships/tickets are remapped without losing history.
        $templates = DB::table('roles')->whereNull('building_id')
            ->whereIn('key', ['security', 'cleaning', 'maintenance'])->get();

        foreach (DB::table('buildings')->pluck('id') as $buildingId) {
            foreach ($templates as $template) {
                $roleId = DB::table('roles')->insertGetId([
                    'building_id' => $buildingId,
                    'key' => $template->key,
                    'name_fa' => $template->name_fa,
                    'is_staff' => true,
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('role_permission')->where('role_id', $template->id)->get(['permission_id'])->each(
                    fn ($permission) => DB::table('role_permission')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $permission->permission_id,
                    ])
                );

                DB::table('building_user_roles')->where('building_id', $buildingId)->where('role_id', $template->id)
                    ->update(['role_id' => $roleId]);
                if (Schema::hasTable('invitations')) {
                    DB::table('invitations')->where('building_id', $buildingId)->where('role_id', $template->id)
                        ->update(['role_id' => $roleId]);
                }
                if (Schema::hasTable('tickets')) {
                    DB::table('tickets')->where('building_id', $buildingId)->where('target_role_id', $template->id)
                        ->update(['target_role_id' => $roleId]);
                }
            }
        }
    }

    public function down(): void
    {
        // Preserve referential integrity by moving references back to the global templates.
        foreach (DB::table('roles')->whereNotNull('building_id')->get() as $role) {
            $templateId = DB::table('roles')->whereNull('building_id')->where('key', $role->key)->value('id');
            if (! $templateId) {
                continue;
            }
            DB::table('building_user_roles')->where('role_id', $role->id)->update(['role_id' => $templateId]);
            if (Schema::hasTable('invitations')) {
                DB::table('invitations')->where('role_id', $role->id)->update(['role_id' => $templateId]);
            }
            if (Schema::hasTable('tickets')) {
                DB::table('tickets')->where('target_role_id', $role->id)->update(['target_role_id' => $templateId]);
            }
            DB::table('role_permission')->where('role_id', $role->id)->delete();
        }

        DB::table('roles')->whereNotNull('building_id')->delete();

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_building_id_key_unique');
            $table->dropConstrainedForeignId('building_id');
            $table->dropColumn(['is_default', 'is_active']);
            $table->unique('key');
        });
    }
};
