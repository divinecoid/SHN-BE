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
        Schema::create('ref_jenis_transaksi_kas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_biaya_id')->constrained('ref_jenis_biaya')->onDelete('restrict');
            $table->text('keterangan')->nullable();
            $table->decimal('jumlah', 15, 2)->default(0); // untuk rupiah dengan 2 desimal
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_jenis_transaksi_kas');
    }
};
