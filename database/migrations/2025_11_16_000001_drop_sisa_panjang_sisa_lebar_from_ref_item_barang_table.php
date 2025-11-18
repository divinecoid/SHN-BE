<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            if (Schema::hasColumn('ref_item_barang', 'sisa_panjang')) {
                $table->dropColumn('sisa_panjang');
            }
            if (Schema::hasColumn('ref_item_barang', 'sisa_lebar')) {
                $table->dropColumn('sisa_lebar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            if (!Schema::hasColumn('ref_item_barang', 'sisa_panjang')) {
                $table->decimal('sisa_panjang', 15, 2)->nullable()->after('sisa_luas');
            }
            if (!Schema::hasColumn('ref_item_barang', 'sisa_lebar')) {
                $table->decimal('sisa_lebar', 15, 2)->nullable()->after('sisa_panjang');
            }
        });
    }
};