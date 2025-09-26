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
            $table->unsignedBigInteger('wo_plan_item_id')->after('work_order_actual_id');
            $table->foreign('wo_plan_item_id')->references('id')->on('trx_work_order_planning_item')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_actual_item', function (Blueprint $table) {
            $table->dropForeign(['wo_plan_item_id']);
            $table->dropColumn('wo_plan_item_id');
        });
    }
};
