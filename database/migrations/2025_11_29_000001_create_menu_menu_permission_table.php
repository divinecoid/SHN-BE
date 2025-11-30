<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ref_menu_menu_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->unique(['menu_id', 'permission_id']);
            $table->foreign('menu_id')->references('id')->on('ref_menu')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('ref_permission')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_menu_menu_permission');
    }
};

