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
            $table->foreignId('id_stock_mutation')->nullable()->after('id_purchase_order')->constrained('trx_stock_mutation')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_penerimaan_barang', function (Blueprint $table) {
            $table->dropForeign(['id_stock_mutation']);
            $table->dropColumn('id_stock_mutation');
        });
    }
};
