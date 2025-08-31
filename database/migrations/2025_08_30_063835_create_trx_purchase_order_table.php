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
        Schema::create('trx_purchase_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_supplier')->constrained('ref_supplier')->onDelete('restrict');
            $table->string('nomor_po')->unique();
            $table->date('tanggal_po');
            $table->date('tanggal_penerimaan')->nullable();
            $table->foreignId('id_user_penerima')->constrained('users')->onDelete('restrict')->nullable();
            $table->date('tanggal_jatuh_tempo'); // default 7 hari dari tanggal po
            $table->string('tanggal_pembayaran')->nullable();
            $table->foreignId('id_gudang')->constrained('ref_gudang')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_purchase_order');
    }
};
