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
        Schema::create('trx_payment', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            $table->foreignId('invoice_pod_id')->constrained('out_invoicepod')->onDelete('restrict');
            $table->decimal('jumlah_payment', 15, 2);
            $table->date('tanggal_payment');
            $table->string('catatan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_payment');
    }
};
