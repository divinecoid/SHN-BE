<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE trx_sales_order MODIFY COLUMN process_status ENUM('submit','in_progress','partial_wo','complete','pending','cancel') NOT NULL DEFAULT 'submit'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trx_sales_order MODIFY COLUMN process_status ENUM('submit','in_progress','partial_wo','complete') NOT NULL DEFAULT 'submit'");
    }
};

