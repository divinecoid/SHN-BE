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
        Schema::table('trx_purchase_order', function (Blueprint $table) {
            $table->enum('status', ['draft', 'received', 'paid'])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_purchase_order', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }
};
