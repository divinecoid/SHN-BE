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
        Schema::table('trx_stock_mutation', function (Blueprint $table) {
            $table->foreignId('requestor_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->date('approval_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_stock_mutation', function (Blueprint $table) {
            $table->dropForeign(['requestor_id']);
            $table->dropColumn('requestor_id');
            $table->dropForeign(['recipient_id']);
            $table->dropColumn('recipient_id');
            $table->dropColumn('approval_date');
        });
    }
};
