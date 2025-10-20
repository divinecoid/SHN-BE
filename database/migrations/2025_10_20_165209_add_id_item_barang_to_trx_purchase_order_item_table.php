<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trx_purchase_order_item', function (Blueprint $table) {
            $table->foreignId('id_item_barang')->nullable()->constrained('ref_item_barang')->onDelete('set null')->after('purchase_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_purchase_order_item', function (Blueprint $table) {
            $table->dropForeign(['id_item_barang']);
            $table->dropColumn('id_item_barang');
        });
    }
};
