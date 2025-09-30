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
        Schema::table('out_invoicepoditem', function (Blueprint $table) {
            $table->dropForeign(['work_order_planning_id']);
            $table->dropColumn('work_order_planning_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('out_invoicepoditem', function (Blueprint $table) {
            $table->foreignId('work_order_planning_id')->constrained('trx_work_order_planning')->onDelete('restrict');
        });
    }
};
