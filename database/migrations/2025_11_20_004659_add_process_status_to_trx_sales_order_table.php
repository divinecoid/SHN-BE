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
        Schema::table('trx_sales_order', function (Blueprint $table) {
            $table->enum('process_status', ['submit', 'in_progress', 'partial_wo', 'complete'])->default('submit')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_sales_order', function (Blueprint $table) {
            $table->dropColumn('process_status');
        });
    }
};
