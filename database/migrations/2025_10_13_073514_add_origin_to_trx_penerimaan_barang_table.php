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
        Schema::table('trx_penerimaan_barang', function (Blueprint $table) {
            $table->enum('origin', ['purchaseorder', 'stockmutation'])->after('id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_penerimaan_barang', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
