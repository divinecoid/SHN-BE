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
            $table->unsignedBigInteger('id_user_penerima')->nullable()->change();
            $table->unsignedBigInteger('id_gudang')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_purchase_order', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user_penerima')->nullable(false)->change();
            $table->unsignedBigInteger('id_gudang')->nullable(false)->change();
        });
    }
};
