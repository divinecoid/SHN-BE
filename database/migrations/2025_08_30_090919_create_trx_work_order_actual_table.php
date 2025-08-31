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
        Schema::create('trx_work_order_actual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_planning_id')->constrained('trx_work_order_planning')->onDelete('cascade');
            $table->date('tanggal_actual');
            $table->string('status');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_work_order_actual');
    }
};
