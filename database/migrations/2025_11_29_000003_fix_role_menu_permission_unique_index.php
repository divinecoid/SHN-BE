<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $dbName = DB::getDatabaseName();
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'ref_role_menu_permission')
            ->where('INDEX_NAME', 'ref_role_menu_permission_role_id_menu_id_permission_id_unique')
            ->exists();
        if ($exists) {
            Schema::table('ref_role_menu_permission', function (Blueprint $table) {
                $table->dropUnique('ref_role_menu_permission_role_id_menu_id_permission_id_unique');
            });
        }
    }

    public function down(): void
    {
        // No-op
    }
};

