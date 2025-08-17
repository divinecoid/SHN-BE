<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_penerimaan_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_item_barang')->constrained('ref_item_barang')->onDelete('cascade');
            $table->foreignId('id_gudang')->constrained('ref_gudang')->onDelete('cascade');
            $table->foreignId('id_rak')->constrained('ref_gudang')->onDelete('cascade');
            $table->decimal('jumlah_barang', 15, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_penerimaan_barang');
    }
};
