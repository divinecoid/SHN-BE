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
        Schema::table('trx_sales_order_item', function (Blueprint $table) {
            $table->decimal('lebar', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_sales_order_item', function (Blueprint $table) {
            $table->decimal('lebar', 10, 2)->nullable(false)->change();
        });
    }
};
