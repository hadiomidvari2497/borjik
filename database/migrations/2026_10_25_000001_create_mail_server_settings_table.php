<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mail_server_settings', function (Blueprint $table) {
            $table->id();
            $table->string('imap_host')->default('mail.sabkhoneh.ir');
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->string('smtp_host')->default('mail.sabkhoneh.ir');
            $table->unsignedSmallInteger('smtp_port')->default(587);
            $table->string('smtp_encryption')->default('tls');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_server_settings');
    }
};
