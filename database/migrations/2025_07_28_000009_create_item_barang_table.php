<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_item_barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->unsignedBigInteger('jenis_barang_id');
            $table->unsignedBigInteger('bentuk_barang_id');
            $table->unsignedBigInteger('grade_barang_id');
            $table->string('nama_item_barang');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('jenis_barang_id')->references('id')->on('ref_jenis_barang');
            $table->foreign('bentuk_barang_id')->references('id')->on('ref_bentuk_barang');
            $table->foreign('grade_barang_id')->references('id')->on('ref_grade_barang');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ref_item_barang');
    }
}; 