<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('support_tickets',function(Blueprint $table){
            $table->string('priority_suggestion',20)->nullable()->index();
            $table->string('priority_suggestion_reason',255)->nullable();
            $table->foreignId('duplicate_of_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $table->unsignedTinyInteger('duplicate_match_score')->nullable();
        });
    }
    public function down(): void {
        Schema::table('support_tickets',function(Blueprint $table){
            $table->dropForeign(['duplicate_of_ticket_id']);
            $table->dropIndex(['priority_suggestion']);
            $table->dropColumn(['priority_suggestion','priority_suggestion_reason','duplicate_of_ticket_id','duplicate_match_score']);
        });
    }
};
