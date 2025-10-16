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
        Schema::table('ref_document_sequence', function (Blueprint $table) {
            $table->integer('barang')->after('mutasi')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_document_sequence', function (Blueprint $table) {
            $table->dropColumn('barang');
        });
    }
};
