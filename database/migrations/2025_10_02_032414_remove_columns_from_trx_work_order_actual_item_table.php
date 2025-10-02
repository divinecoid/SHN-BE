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
        Schema::table('trx_work_order_actual_item', function (Blueprint $table) {
            $table->dropColumn('harga');
            $table->dropColumn('sub_total');
            $table->dropColumn('diskon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_actual_item', function (Blueprint $table) {
            $table->decimal('harga', 10, 2)->after('berat_actual');
            $table->decimal('sub_total', 10, 2)->after('harga');
            $table->decimal('diskon', 5, 2)->after('sub_total');
        });
    }
};
