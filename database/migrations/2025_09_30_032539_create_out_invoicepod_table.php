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
        Schema::create('out_invoicepod', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_planning_id')->constrained('trx_work_order_planning')->onDelete('restrict');
            $table->foreignId('sales_order_id')->constrained('trx_sales_order')->onDelete('restrict');
            $table->string('nomor_invoice');
            $table->datetime('tanggal_cetak_invoice')->nullable();
            $table->string('nomor_pod');
            $table->datetime('tanggal_cetak_pod')->nullable();
            $table->decimal('total_harga_invoice', 15, 2);
            $table->decimal('diskon_invoice', 5, 2);
            $table->decimal('discount_invoice', 5, 2);
            $table->decimal('biaya_lain', 15, 2);
            $table->decimal('ppn_invoice', 5, 2);
            $table->decimal('grand_total', 15, 2);
            $table->decimal('uang_muka', 15, 2);
            $table->decimal('sisa_bayar', 15, 2);
            $table->enum('status_bayar', ['pending', 'partial', 'paid'])->default('pending');
            $table->enum('status_pod', ['pending', 'partial', 'fullfilled'])->default('pending');
            $table->enum('handover_method', ['pickup', 'delivery'])->default('pickup');
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
        Schema::dropIfExists('out_invoicepod');
    }
};
