<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE trx_work_order_planning MODIFY COLUMN type_wo VARCHAR(20) NOT NULL");
        DB::statement("UPDATE trx_work_order_planning SET type_wo = 'pending' WHERE type_wo = 'Pending'");
        DB::statement("UPDATE trx_work_order_planning SET type_wo = 'cancel' WHERE type_wo = 'Batal'");
        DB::statement("UPDATE trx_work_order_planning SET type_wo = 'normal' WHERE type_wo = 'Normal'");
        DB::statement("ALTER TABLE trx_work_order_planning MODIFY COLUMN type_wo ENUM('normal','pending','cancel') NOT NULL DEFAULT 'normal'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trx_work_order_planning MODIFY COLUMN type_wo VARCHAR(20) NOT NULL");
        DB::statement("UPDATE trx_work_order_planning SET type_wo = 'Normal' WHERE type_wo = 'normal'");
        DB::statement("UPDATE trx_work_order_planning SET type_wo = 'Pending' WHERE type_wo = 'pending'");
        DB::statement("UPDATE trx_work_order_planning SET type_wo = 'Batal' WHERE type_wo = 'cancel'");
        DB::statement("ALTER TABLE trx_work_order_planning MODIFY COLUMN type_wo ENUM('Normal','Pending','Batal') NOT NULL DEFAULT 'Normal'");
    }
};
