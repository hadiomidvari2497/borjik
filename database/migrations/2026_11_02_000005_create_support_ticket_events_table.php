<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('resolved_by_admin_id')->nullable()->after('assigned_admin_id')->constrained('admin_users')->nullOnDelete();
        });

        Schema::create('support_ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('event_type', 40)->index();
            $table->foreignId('actor_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('from_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('to_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['support_ticket_id', 'created_at']);
            $table->index(['actor_admin_id', 'event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_events');
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by_admin_id');
        });
    }
};
