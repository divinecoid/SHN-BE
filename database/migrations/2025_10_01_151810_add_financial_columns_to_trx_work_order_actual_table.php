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
        Schema::table('trx_work_order_actual', function (Blueprint $table) {
            $table->decimal('total_harga', 10, 2)->after('tanggal_actual');
            $table->decimal('diskon', 5, 2)->after('total_harga');
            $table->decimal('biaya_lain', 10, 2)->after('diskon');
            $table->decimal('ppn', 5, 2)->after('biaya_lain');
            $table->decimal('grand_total', 10, 2)->after('ppn');
            $table->decimal('uang_muka', 10, 2)->after('grand_total');
            $table->decimal('sisa_bayar', 10, 2)->after('uang_muka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_actual', function (Blueprint $table) {
            //
        });
    }
};
