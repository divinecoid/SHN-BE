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
        Schema::create('trx_stock_opname', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pic_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('gudang_id')->constrained('ref_gudang')->onDelete('restrict');
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
        Schema::dropIfExists('trx_stock_opname');
    }
};
