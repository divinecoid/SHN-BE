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
        Schema::table('trx_work_order_planning_item', function (Blueprint $table) {
            //remove harga and diskon
            $table->dropColumn('harga');
            $table->dropColumn('diskon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_planning_item', function (Blueprint $table) {
            $table->decimal('harga', 10, 2)->after('satuan');
            $table->decimal('diskon', 5, 2)->after('harga');
        });
    }
};
