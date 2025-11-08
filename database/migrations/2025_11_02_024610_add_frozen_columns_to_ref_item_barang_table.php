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
            $table->dateTime('frozen_at')->nullable();
            $table->unsignedBigInteger('frozen_by')->nullable();
            $table->foreign('frozen_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->dropForeign(['frozen_by']);
            $table->dropColumn(['frozen_at', 'frozen_by']);
        });
    }
};
