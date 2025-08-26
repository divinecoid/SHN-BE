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
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->decimal('panjang', 10, 2)->nullable()->after('grade_barang_id');
            $table->decimal('lebar', 10, 2)->nullable()->after('panjang');
            $table->decimal('tebal', 10, 2)->nullable()->after('lebar');
            $table->decimal('quantity', 10, 2)->nullable()->after('tebal');
            $table->decimal('quantity_tebal_sama', 10, 2)->nullable()->after('quantity');
            $table->string('jenis_potongan')->nullable()->after('quantity_tebal_sama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->dropColumn([
                'panjang',
                'lebar', 
                'tebal',
                'quantity',
                'quantity_tebal_sama',
                'jenis_potongan'
            ]);
        });
    }
};
