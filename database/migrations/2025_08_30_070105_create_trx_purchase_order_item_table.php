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
        Schema::create('trx_purchase_order_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('trx_purchase_order')->onDelete('cascade');
            $table->decimal('panjang', 10, 2);
            $table->decimal('lebar', 10, 2);
            $table->decimal('tebal', 10, 2);
            $table->integer('qty');
            $table->foreignId('jenis_barang_id')->constrained('ref_jenis_barang')->onDelete('restrict');
            $table->foreignId('bentuk_barang_id')->constrained('ref_bentuk_barang')->onDelete('restrict');
            $table->foreignId('grade_barang_id')->constrained('ref_grade_barang')->onDelete('restrict');
            $table->decimal('harga', 15, 2);
            $table->string('satuan');
            $table->decimal('diskon', 5, 2)->default(0);
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
        Schema::dropIfExists('trx_purchase_order_item');
    }
};
