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
        Schema::table('trx_sales_order_item', function (Blueprint $table) {
            // Drop existing string columns
            $table->dropColumn([
                'jenis_barang',
                'bentuk_barang',
                'grade_barang'
            ]);
            
            // Add foreign key columns
            $table->foreignId('jenis_barang_id')->constrained('ref_jenis_barang')->onDelete('restrict');
            $table->foreignId('bentuk_barang_id')->constrained('ref_bentuk_barang')->onDelete('restrict');
            $table->foreignId('grade_barang_id')->constrained('ref_grade_barang')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_sales_order_item', function (Blueprint $table) {
            // Drop foreign key columns
            $table->dropForeign(['jenis_barang_id']);
            $table->dropForeign(['bentuk_barang_id']);
            $table->dropForeign(['grade_barang_id']);
            $table->dropColumn([
                'jenis_barang_id',
                'bentuk_barang_id',
                'grade_barang_id'
            ]);
            
            // Revert string columns
            $table->string('jenis_barang');
            $table->string('bentuk_barang');
            $table->string('grade_barang');
        });
    }
};
