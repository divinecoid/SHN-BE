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
            $table->foreignId('sales_order_item_id')->constrained('trx_sales_order_item')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_planning_item', function (Blueprint $table) {
            $table->dropForeign(['sales_order_item_id']);
            $table->dropColumn('sales_order_item_id');
        });
    }
};
