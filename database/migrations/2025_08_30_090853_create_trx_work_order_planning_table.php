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
        Schema::create('trx_work_order_planning', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_wo');
            $table->date('tanggal_wo');
            $table->foreignId('id_sales_order')->constrained('trx_sales_order')->onDelete('restrict');
            $table->foreignId('id_pelanggan')->constrained('ref_pelanggan')->onDelete('restrict');
            $table->foreignId('id_gudang')->constrained('ref_gudang')->onDelete('restrict')->nullable();
            $table->foreignId('id_pelaksana')->constrained('ref_pelaksana')->onDelete('restrict');
            $table->string('prioritas');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_work_order_planning');
    }
};
