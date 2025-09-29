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
            $table->foreignId('id_purchase_order')
                ->nullable()
                ->after('id')
                ->constrained('trx_purchase_order')
                ->onDelete('restrict');
            // nanti tambah optional column id_mutasi
            $table->enum('unit', ['single', 'bulk'])->default('single');
            $table->string('url_foto')->nullable()->after('catatan');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_penerimaan_barang', function (Blueprint $table) {
            $table->dropForeign(['id_purchase_order']);
            $table->dropColumn('id_purchase_order');
            $table->dropColumn('unit');
            $table->dropColumn('url_foto');
        });
    }
};
