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
        Schema::create('trx_saran_plat_shaft_dasar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wo_planning_item_id')->constrained('trx_work_order_planning_item')->onDelete('restrict');
            $table->foreignId('item_barang_id')->constrained('ref_item_barang')->onDelete('restrict');
            $table->boolean('is_selected')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_saran_plat_shaft_dasar');
    }
};
