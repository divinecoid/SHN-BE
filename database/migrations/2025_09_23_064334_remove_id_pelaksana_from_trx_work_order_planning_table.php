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
        Schema::table('trx_work_order_planning', function (Blueprint $table) {
            $table->dropForeign(['id_pelaksana']);
            $table->dropColumn('id_pelaksana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_work_order_planning', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pelaksana')->nullable();
            $table->foreign('id_pelaksana')
                  ->references('id')
                  ->on('ref_pelaksana')
                  ->onDelete('restrict');
        });
    }
};
