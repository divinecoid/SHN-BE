<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_gudang', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama_gudang');
            $table->string('telepon_hp');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ref_gudang');
    }
}; 