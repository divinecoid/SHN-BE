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
        Schema::create('trx_sales_order', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_so')->unique();
            $table->date('tanggal_so');
            $table->date('tanggal_pengiriman');
            $table->string('syarat_pembayaran');
            $table->string('asal_gudang');
            $table->foreignId('id_pelanggan')->constrained('ref_pelanggan')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_sales_order');
    }
};