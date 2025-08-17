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
        Schema::create('trx_sales_order_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('trx_sales_order')->onDelete('cascade');
            $table->decimal('panjang', 10, 2);
            $table->decimal('lebar', 10, 2);
            $table->integer('qty');
            $table->string('jenis_barang');
            $table->string('bentuk_barang');
            $table->string('grade_barang');
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
        Schema::dropIfExists('trx_sales_order_item');
    }
};