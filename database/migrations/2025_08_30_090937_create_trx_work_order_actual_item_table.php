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
        Schema::create('trx_work_order_actual_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_actual_id')->constrained('trx_work_order_actual')->onDelete('cascade');
            $table->decimal('panjang_actual', 10, 2);
            $table->decimal('lebar_actual', 10, 2)->nullable();
            $table->decimal('tebal_actual', 10, 2);
            $table->integer('qty_actual');
            $table->foreignId('jenis_barang_id')->constrained('ref_jenis_barang')->onDelete('restrict');
            $table->foreignId('bentuk_barang_id')->constrained('ref_bentuk_barang')->onDelete('restrict');
            $table->foreignId('grade_barang_id')->constrained('ref_grade_barang')->onDelete('restrict');
            $table->foreignId('plat_dasar_id')->constrained('ref_item_barang')->onDelete('restrict');
            $table->string('satuan');
            $table->decimal('diskon', 5, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_work_order_actual_item');
    }
};
