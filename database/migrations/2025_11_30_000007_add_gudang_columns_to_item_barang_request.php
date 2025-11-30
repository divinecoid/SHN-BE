<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trx_item_barang_request', function (Blueprint $table) {
            if (!Schema::hasColumn('trx_item_barang_request', 'gudang_asal_id')) {
                $table->unsignedBigInteger('gudang_asal_id')->nullable()->after('quantity');
                $table->foreign('gudang_asal_id')->references('id')->on('ref_gudang')->onDelete('set null');
            }
            if (!Schema::hasColumn('trx_item_barang_request', 'gudang_tujuan_id')) {
                $table->unsignedBigInteger('gudang_tujuan_id')->nullable()->after('gudang_asal_id');
                $table->foreign('gudang_tujuan_id')->references('id')->on('ref_gudang')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trx_item_barang_request', function (Blueprint $table) {
            if (Schema::hasColumn('trx_item_barang_request', 'gudang_tujuan_id')) {
                $table->dropForeign(['gudang_tujuan_id']);
                $table->dropColumn('gudang_tujuan_id');
            }
            if (Schema::hasColumn('trx_item_barang_request', 'gudang_asal_id')) {
                $table->dropForeign(['gudang_asal_id']);
                $table->dropColumn('gudang_asal_id');
            }
        });
    }
};

