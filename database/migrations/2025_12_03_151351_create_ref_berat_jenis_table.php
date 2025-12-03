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
        Schema::create('ref_berat_jenis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_barang_id')->constrained('ref_jenis_barang')->onDelete('restrict');
            $table->foreignId('bentuk_barang_id')->constrained('ref_bentuk_barang')->onDelete('restrict');
            $table->foreignId('grade_barang_id')->constrained('ref_grade_barang')->onDelete('restrict');
            $table->decimal('berat_per_cm', 10, 4)->nullable()->comment('Berat (kg) per cm untuk barang 1D');
            $table->decimal('berat_per_luas', 10, 4)->nullable()->comment('Berat per luas untuk plat (2D)');
            $table->softDeletes();
            $table->timestamps();
            
            // Unique constraint untuk kombinasi jenis_barang_id, bentuk_barang_id, grade_barang_id
            $table->unique(['jenis_barang_id', 'bentuk_barang_id', 'grade_barang_id'], 'unique_berat_jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_berat_jenis');
    }
};
