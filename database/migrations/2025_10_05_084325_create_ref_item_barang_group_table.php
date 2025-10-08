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
        Schema::create('ref_item_barang_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_barang_id')->constrained('ref_jenis_barang')->onDelete('restrict');
            $table->foreignId('bentuk_barang_id')->constrained('ref_bentuk_barang')->onDelete('restrict');
            $table->foreignId('grade_barang_id')->constrained('ref_grade_barang')->onDelete('restrict');
            $table->integer('panjang')->default(0);
            $table->integer('lebar')->nullable();
            $table->integer('tebal')->default(0);
            $table->integer('quantity_utuh')->default(0);
            $table->integer('quantity_potongan')->default(0);
            $table->integer('sequence')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_item_barang_group');
    }
};
