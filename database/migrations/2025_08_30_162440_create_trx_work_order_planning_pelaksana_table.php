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
        Schema::create('trx_work_order_planning_pelaksana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wo_plan_item_id')
                ->constrained('trx_work_order_planning_item')
                ->onDelete('restrict');
            $table->foreignId('pelaksana_id')
                ->constrained('ref_pelaksana')
                ->onDelete('restrict');
            $table->date('tanggal')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_work_order_planning_pelaksana');
    }
};
