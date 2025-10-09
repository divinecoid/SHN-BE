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
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->date('split_date')->nullable();
            $table->date('merge_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->dropColumn('split_date');
            $table->dropColumn('merge_date');
        });
    }
};
