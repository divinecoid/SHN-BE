<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama_pelanggan');
            $table->string('kota');
            $table->string('telepon_hp');
            $table->string('contact_person');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ref_pelanggan');
    }
}; 