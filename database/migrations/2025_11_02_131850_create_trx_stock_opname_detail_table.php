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
        Schema::create('trx_stock_opname_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('trx_stock_opname')->onDelete('restrict');
            $table->foreignId('item_barang_id')->constrained('ref_item_barang')->onDelete('restrict');
            $table->integer('stok_sistem')->nullable()->default(0); // hanya diisi jika stok itembarang di freeze (frozen_at tidak null)
            $table->integer('stok_fisik')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['stock_opname_id', 'item_barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_stock_opname_detail');
    }
};
