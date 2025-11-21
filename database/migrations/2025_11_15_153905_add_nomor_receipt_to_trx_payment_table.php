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
        Schema::table('trx_payment', function (Blueprint $table) {
            $table->string('nomor_receipt')->nullable()->after('has_generated_receipt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_payment', function (Blueprint $table) {
            $table->dropColumn('nomor_receipt');
        });
    }
};
