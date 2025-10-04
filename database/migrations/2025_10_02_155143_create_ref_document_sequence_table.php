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
        Schema::create('ref_document_sequence', function (Blueprint $table) {
            $table->id();
            $table->date('sequence_date');
            $table->integer('po')->default(0);
            $table->integer('so')->default(0);
            $table->integer('wo')->default(0);
            $table->integer('pod')->default(0);
            $table->integer('invoice')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_document_sequence');
    }
};
