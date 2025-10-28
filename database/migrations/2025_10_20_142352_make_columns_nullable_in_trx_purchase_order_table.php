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
            // set nullable: tanggal_penerimaan, id_user_penerima, tanggal_pembayaran, catatan
            $table->date('tanggal_penerimaan')->nullable()->change();
            $table->unsignedBigInteger('id_user_penerima')->nullable()->change();
            $table->date('tanggal_pembayaran')->nullable()->change();
            $table->text('catatan')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_purchase_order', function (Blueprint $table) {
            // set not nullable: tanggal_penerimaan, id_user_penerima, tanggal_pembayaran, catatan
            $table->date('tanggal_penerimaan')->nullable(false)->change();
            $table->unsignedBigInteger('id_user_penerima')->nullable(false)->change();
            $table->date('tanggal_pembayaran')->nullable(false)->change();
            $table->text('catatan')->nullable(false)->change();
        });
    }
};
