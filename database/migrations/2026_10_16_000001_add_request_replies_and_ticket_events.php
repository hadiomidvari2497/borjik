<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->foreignId('reply_to_id')->nullable()->after('user_id')
                ->constrained('ticket_comments')->nullOnDelete();
        });

        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->dateTime('created_at');
            $table->index(['building_id', 'ticket_id', 'created_at']);
        });

        // Preserve status history that existed before the event timeline was introduced.
        DB::table('ticket_status_logs')->orderBy('id')->get()->each(function ($log) {
            $from = $log->from_status ?: '—';
            $description = 'وضعیت درخواست از «'.$from.'» به «'.$log->to_status.'» تغییر کرد.';
            if ($log->note) {
                $description .= ' '.$log->note;
            }

            DB::table('ticket_events')->insert([
                'building_id' => $log->building_id,
                'ticket_id' => $log->ticket_id,
                'user_id' => $log->user_id,
                'type' => 'status_changed',
                'description' => $description,
                'metadata' => null,
                'created_at' => $log->created_at ?: now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_events');
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_id');
        });
    }
};
