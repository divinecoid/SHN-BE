<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE trx_sales_order MODIFY COLUMN is_wo_qty_matched TINYINT(1) NOT NULL DEFAULT 1");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trx_sales_order MODIFY COLUMN is_wo_qty_matched TINYINT(1) NOT NULL DEFAULT 0");
    }
};

