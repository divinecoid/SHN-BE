<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trx_sales_order', function (Blueprint $table) {
            if (!Schema::hasColumn('trx_sales_order', 'is_wo_qty_matched')) {
                $table->boolean('is_wo_qty_matched')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trx_sales_order', function (Blueprint $table) {
            if (Schema::hasColumn('trx_sales_order', 'is_wo_qty_matched')) {
                $table->dropColumn('is_wo_qty_matched');
            }
        });
    }
};

