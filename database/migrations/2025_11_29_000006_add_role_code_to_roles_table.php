<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'role_code')) {
                $table->string('role_code', 64)->nullable()->after('name');
                $table->unique('role_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'role_code')) {
                $table->dropUnique(['role_code']);
                $table->dropColumn('role_code');
            }
        });
    }
};

