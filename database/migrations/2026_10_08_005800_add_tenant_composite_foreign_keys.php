<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کلیدهای خارجی ترکیبی: تضمین می‌کند ردیف فرزند و والد همیشه به یک building_id تعلق دارند.
 * (نشت داده بین ساختمان‌ها حتی با کوئری اشتباه در سطح دیتابیس هم ممکن نیست.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->foreign(['building_id', 'block_id'])->references(['building_id', 'id'])->on('blocks')->restrictOnDelete();
        });
        Schema::table('parkings', function (Blueprint $table) {
            $table->foreign(['building_id', 'block_id'])->references(['building_id', 'id'])->on('blocks')->restrictOnDelete();
        });
        Schema::table('storages', function (Blueprint $table) {
            $table->foreign(['building_id', 'block_id'])->references(['building_id', 'id'])->on('blocks')->restrictOnDelete();
        });
        Schema::table('parking_assignments', function (Blueprint $table) {
            $table->foreign(['building_id', 'parking_id'])->references(['building_id', 'id'])->on('parkings')->restrictOnDelete();
        });
        Schema::table('parking_assignments', function (Blueprint $table) {
            $table->foreign(['building_id', 'unit_id'])->references(['building_id', 'id'])->on('units')->restrictOnDelete();
        });
        Schema::table('storage_assignments', function (Blueprint $table) {
            $table->foreign(['building_id', 'storage_id'])->references(['building_id', 'id'])->on('storages')->restrictOnDelete();
        });
        Schema::table('storage_assignments', function (Blueprint $table) {
            $table->foreign(['building_id', 'unit_id'])->references(['building_id', 'id'])->on('units')->restrictOnDelete();
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreign(['building_id', 'unit_id'])->references(['building_id', 'id'])->on('units')->restrictOnDelete();
        });
        Schema::table('unit_parties', function (Blueprint $table) {
            $table->foreign(['building_id', 'unit_id'])->references(['building_id', 'id'])->on('units')->restrictOnDelete();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign(['building_id', 'unit_id'])->references(['building_id', 'id'])->on('units')->restrictOnDelete();
        });
        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->foreign(['building_id', 'unit_id'])->references(['building_id', 'id'])->on('units')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'block_id']);
        });
        Schema::table('parkings', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'block_id']);
        });
        Schema::table('storages', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'block_id']);
        });
        Schema::table('parking_assignments', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'parking_id']);
        });
        Schema::table('parking_assignments', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'unit_id']);
        });
        Schema::table('storage_assignments', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'storage_id']);
        });
        Schema::table('storage_assignments', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'unit_id']);
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'unit_id']);
        });
        Schema::table('unit_parties', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'unit_id']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'unit_id']);
        });
        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->dropForeign(['building_id', 'unit_id']);
        });
    }
};
