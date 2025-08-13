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
            $table->string('tipe_gudang')->nullable()->after('nama_gudang');
            $table->unsignedBigInteger('parent_id')->nullable()->after('tipe_gudang');
            $table->foreign('parent_id')->references('id')->on('ref_gudang')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_gudang', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['tipe_gudang', 'parent_id']);
        });
    }
};
