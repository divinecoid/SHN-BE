<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->decimal('sisa_panjang', 15, 2)->nullable()->after('sisa_luas');
        });
    }

    public function down(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->dropColumn('sisa_panjang');
        });
    }
};