<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trx_work_order_planning', function (Blueprint $table) {
            if (!Schema::hasColumn('trx_work_order_planning', 'type_wo')) {
                $table->enum('type_wo', ['Pending', 'Batal', 'Normal'])->default('Normal')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trx_work_order_planning', function (Blueprint $table) {
            if (Schema::hasColumn('trx_work_order_planning', 'type_wo')) {
                $table->dropColumn('type_wo');
            }
        });
    }
};

