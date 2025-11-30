<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trx_item_barang_request', function (Blueprint $table) {
            if (!Schema::hasColumn('trx_item_barang_request', 'item_barang_id')) {
                $table->unsignedBigInteger('item_barang_id')->nullable()->after('nomor_request');
                $table->foreign('item_barang_id')->references('id')->on('ref_item_barang')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trx_item_barang_request', function (Blueprint $table) {
            if (Schema::hasColumn('trx_item_barang_request', 'item_barang_id')) {
                $table->dropForeign(['item_barang_id']);
                $table->dropColumn('item_barang_id');
            }
        });
    }
};

