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
        Schema::create('trx_stock_mutation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_tujuan_id')->nullable()->constrained('ref_gudang')->onDelete('restrict');
            $table->foreignId('gudang_asal_id')->nullable()->constrained('ref_gudang')->onDelete('restrict');
            $table->enum('status', ['requested', 'accepted','lost','partial']);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('trx_stock_mutation_detail', function(Blueprint $table) {
            $table->id();
            $table->foreignId('stock_mutation_id')->constrained('trx_stock_mutation')->onDelete('restrict');
            $table->foreignId('item_barang_id')->constrained('ref_item_barang')->onDelete('restrict');
            $table->enum('unit', ['single', 'bulk']);
            $table->enum('status', ['on_progress', 'accepted']);
            $table->integer('quantity');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_stock_mutation_detail');
        Schema::dropIfExists('trx_stock_mutation');
    }
};
