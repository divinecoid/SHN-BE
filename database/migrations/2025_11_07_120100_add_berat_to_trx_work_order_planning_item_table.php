<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trx_work_order_planning_item', function (Blueprint $table) {
            if (!Schema::hasColumn('trx_work_order_planning_item', 'berat')) {
                $table->decimal('berat', 10, 2)->nullable()->after('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trx_work_order_planning_item', function (Blueprint $table) {
            if (Schema::hasColumn('trx_work_order_planning_item', 'berat')) {
                $table->dropColumn('berat');
            }
        });
    }
};