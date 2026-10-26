<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('discount_type', 20)->nullable()->after('price');
            $table->unsignedBigInteger('discount_value')->default(0)->after('discount_type');
            $table->timestamp('discount_starts_at')->nullable()->after('discount_value');
            $table->timestamp('discount_ends_at')->nullable()->after('discount_starts_at');
        });

        Schema::create('discount_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 120)->nullable();
            $table->string('discount_type', 20);
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('min_order_amount')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_user')->default(1);
            $table->json('plan_ids')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('discount_coupons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('payment_id')->nullable()->unique();
            $table->unsignedBigInteger('discount_amount');
            $table->string('status', 20)->default('reserved');
            $table->timestamps();
            $table->index(['coupon_id', 'user_id', 'status']);
        });

        Schema::create('financial_price_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_type', 20);
            $table->decimal('adjustment_value', 12, 2);
            $table->unsignedInteger('affected_plans')->default(0);
            $table->json('details')->nullable();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_price_adjustments');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('discount_coupons');
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_starts_at', 'discount_ends_at']);
        });
    }
};
