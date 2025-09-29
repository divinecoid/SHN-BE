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
            $table->dropColumn('jumlah_barang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_penerimaan_barang', function (Blueprint $table) {
            $table->decimal('jumlah_barang', 15, 2)->after('id_item_barang');
        });
    }
};
