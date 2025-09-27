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
            $table->dropColumn('satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_actual_item', function (Blueprint $table) {
            $table->string('satuan')->after('plat_dasar_id');
        });
    }
};
