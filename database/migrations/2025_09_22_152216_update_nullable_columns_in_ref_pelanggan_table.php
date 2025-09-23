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
        Schema::table('ref_pelanggan', function (Blueprint $table) {
            $table->string('kota')->nullable()->change();
            $table->string('telepon_hp')->nullable()->change();
            $table->string('contact_person')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_pelanggan', function (Blueprint $table) {
            $table->string('kota')->nullable(false)->change();
            $table->string('telepon_hp')->nullable(false)->change();
            $table->string('contact_person')->nullable(false)->change();
        });
    }
};
