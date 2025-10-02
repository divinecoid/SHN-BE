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
            $table->decimal('ppn', 15, 2);
            $table->decimal('grand_total', 15, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_actual_item', function (Blueprint $table) {
            $table->dropColumn('sub_total');
            $table->dropColumn('ppn');
            $table->dropColumn('grand_total');
        });
    }
};
