<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('person_type', 10)->default('real');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('company_name', 200)->nullable();
            $table->string('national_code', 20)->nullable()->index();
            $table->date('birth_date')->nullable();
            $table->string('mobile', 15)->unique();
            $table->dateTime('mobile_verified_at')->nullable();
            $table->string('password', 255)->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('avatar_file_id')->nullable();
            $table->dateTime('profile_completed_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('remember_token', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
