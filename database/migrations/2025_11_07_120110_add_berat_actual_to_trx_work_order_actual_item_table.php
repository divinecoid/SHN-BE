<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trx_work_order_actual_item', function (Blueprint $table) {
            if (!Schema::hasColumn('trx_work_order_actual_item', 'berat_actual')) {
                $table->decimal('berat_actual', 10, 2)->nullable()->after('qty_actual');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trx_work_order_actual_item', function (Blueprint $table) {
            if (Schema::hasColumn('trx_work_order_actual_item', 'berat_actual')) {
                $table->dropColumn('berat_actual');
            }
        });
    }
};