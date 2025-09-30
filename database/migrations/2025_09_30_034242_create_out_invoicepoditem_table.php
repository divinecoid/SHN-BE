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
        Schema::create('out_invoicepoditem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoicepod_id')->constrained('out_invoicepod')->onDelete('restrict');
            $table->foreignId('work_order_planning_id')->constrained('trx_work_order_planning')->onDelete('restrict');
            $table->string('nama_item');
            $table->enum('unit', ['utuh', 'potongan']);
            $table->string('dimensi_potong');
            $table->integer('qty');
            $table->decimal('total_kg', 10, 2);
            $table->decimal('harga_per_unit', 15, 2);
            $table->decimal('total_harga', 15, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('out_invoicepoditem');
    }
};
