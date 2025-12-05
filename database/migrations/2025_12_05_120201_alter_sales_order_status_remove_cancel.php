<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("UPDATE trx_sales_order SET status='closed' WHERE status='cancel'");
        DB::statement("ALTER TABLE trx_sales_order MODIFY COLUMN status ENUM('active','delete_requested','deleted','closed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trx_sales_order MODIFY COLUMN status ENUM('active','delete_requested','deleted','closed','cancel') NOT NULL DEFAULT 'active'");
    }
};

