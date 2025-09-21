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
        Schema::table('ref_item_barang', function (Blueprint $table) {
            // Drop the old is_edit_by column
            $table->dropColumn('is_edit_by');
            
            // Add new user_id column with foreign key
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_item_barang', function (Blueprint $table) {
            // Drop foreign key and user_id column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            // Add back the old is_edit_by column
            $table->string('is_edit_by')->nullable();
        });
    }
};
