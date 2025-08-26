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
        Schema::create('ref_role_menu_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('menu_id')->constrained('ref_menu')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('ref_permission')->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate role-menu-permission combinations
            $table->unique(['role_id', 'menu_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_role_menu_permission');
    }
};
