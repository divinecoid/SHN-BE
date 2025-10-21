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
        Schema::table('trx_saran_plat_shaft_dasar', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->nullable()->after('item_barang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_saran_plat_shaft_dasar', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};


