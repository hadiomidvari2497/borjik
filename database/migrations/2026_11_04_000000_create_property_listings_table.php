<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('listing_type', 24)->index();
            $table->string('property_type', 24)->index();
            $table->string('title', 180);
            $table->text('description');
            $table->string('province', 100)->nullable()->index();
            $table->string('city', 100)->index();
            $table->string('neighborhood', 120)->nullable()->index();
            $table->string('address', 500)->nullable();
            $table->boolean('hide_address')->default(true);
            $table->decimal('area', 10, 2)->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->smallInteger('floor')->nullable();
            $table->smallInteger('total_floors')->nullable();
            $table->unsignedSmallInteger('built_year')->nullable();
            $table->string('document_type', 60)->nullable();
            $table->json('amenities')->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->unsignedBigInteger('deposit')->nullable();
            $table->unsignedBigInteger('monthly_rent')->nullable();
            $table->boolean('allow_exchange')->default(false);
            $table->json('photos')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->index(['status', 'listing_type', 'city']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listings');
    }
};
