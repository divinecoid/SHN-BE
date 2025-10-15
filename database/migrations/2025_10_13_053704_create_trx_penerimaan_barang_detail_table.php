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
        Schema::create('trx_penerimaan_barang_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_penerimaan_barang')->constrained('trx_penerimaan_barang')->onDelete('restrict');
            
            $table->foreignId('id_stock_mutation_detail')->nullable()->constrained('trx_stock_mutation_detail')->onDelete('restrict');
            $table->foreignId('id_purchase_order_item')->nullable()->constrained('trx_purchase_order_item')->onDelete('restrict');
            
            $table->foreignId('id_rak')->constrained('ref_gudang')->onDelete('restrict');
            $table->integer('qty')->default(0);
            $table->foreignId('id_item_barang')->constrained('ref_item_barang')->onDelete('restrict');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_penerimaan_barang_detail');
    }
};
