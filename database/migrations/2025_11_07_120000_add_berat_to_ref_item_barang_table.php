<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            // Tambah kolom berat sebagai decimal(10,2), opsional
            if (!Schema::hasColumn('ref_item_barang', 'berat')) {
                $table->decimal('berat', 10, 2)->nullable()->after('tebal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            if (Schema::hasColumn('ref_item_barang', 'berat')) {
                $table->dropColumn('berat');
            }
        });
    }
};