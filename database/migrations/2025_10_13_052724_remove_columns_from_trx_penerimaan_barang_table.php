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
        Schema::table('trx_penerimaan_barang', function (Blueprint $table) {
            // Drop foreign keys first (if they exist)
            $table->dropForeign(['id_item_barang']);
            $table->dropForeign(['id_rak']);

            // Now drop the columns
            $table->dropColumn(['id_item_barang', 'qty', 'id_rak', 'unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_penerimaan_barang', function (Blueprint $table) {
            $table->foreignId('id_item_barang')->constrained('ref_item_barang')->onDelete('restrict');
            $table->integer('qty')->after('id_item_barang')->default(0);
            $table->foreignId('id_rak')->constrained('ref_gudang')->onDelete('restrict');
            $table->enum('unit', ['bulk', 'pcs'])->default('pcs')->after('qty');
        });
    }
};
