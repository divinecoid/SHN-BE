<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to match enum values if needed
        DB::table('ref_item_barang')
            ->where('jenis_potongan', '!=', '')
            ->whereNotIn('jenis_potongan', ['utuh', 'potongan'])
            ->update(['jenis_potongan' => 'utuh']); // Default to utuh for unknown values

        // Change column to enum
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->enum('jenis_potongan', ['utuh', 'potongan'])
                  ->nullable()
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            $table->string('jenis_potongan')->nullable()->change();
        });
    }
};
