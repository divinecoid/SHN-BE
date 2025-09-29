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
        Schema::table('trx_work_order_planning', function (Blueprint $table) {
            $table->dateTime('close_wo_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_planning', function (Blueprint $table) {
            $table->dropColumn('close_wo_at');
        });
    }
};
