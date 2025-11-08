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
        Schema::table('trx_stock_opname', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft')->after('gudang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_stock_opname', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

