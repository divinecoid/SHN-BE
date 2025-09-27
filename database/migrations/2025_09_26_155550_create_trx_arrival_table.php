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
        Schema::create('trx_arrival', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_purchase_order')->nullable()->constrained('trx_purchase_order')->onDelete('restrict');
            $table->foreignId('id_gudang_asal')->nullable()->constrained('ref_gudang')->onDelete('restrict');
            $table->foreignId('id_gudang_tujuan')->nullable()->constrained('ref_gudang')->onDelete('restrict');
            $table->foreignId('id_item_barang')->constrained('ref_item_barang')->onDelete('restrict');
            $table->integer('jumlah_barang');
            $table->enum('unit', ['bulk', 'pcs']);
            $table->date('tanggal_arrival');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_arrival');
    }
};
