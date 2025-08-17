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
        Schema::table('ref_gudang', function (Blueprint $table) {
            $table->decimal('kapasitas', 15, 2)->nullable()->after('telepon_hp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_gudang', function (Blueprint $table) {
            $table->dropColumn('kapasitas');
        });
    }
};
