<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_jenis_biaya', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('jenis_biaya');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ref_jenis_biaya');
    }
}; 