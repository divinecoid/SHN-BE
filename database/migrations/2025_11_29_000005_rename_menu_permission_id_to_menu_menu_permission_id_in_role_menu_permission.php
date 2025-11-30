<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $dbName = DB::getDatabaseName();
        // Drop FK if exists
        $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'ref_role_menu_permission')
            ->where('CONSTRAINT_NAME', 'ref_role_menu_permission_menu_permission_id_foreign')
            ->exists();
        if ($fkExists) {
            Schema::table('ref_role_menu_permission', function (Blueprint $table) {
                $table->dropForeign(['menu_permission_id']);
            });
        }

        // Rename column
        Schema::table('ref_role_menu_permission', function (Blueprint $table) {
            if (Schema::hasColumn('ref_role_menu_permission', 'menu_permission_id') && !Schema::hasColumn('ref_role_menu_permission', 'menu_menu_permission_id')) {
                $table->renameColumn('menu_permission_id', 'menu_menu_permission_id');
            }
        });

        // Add FK for new column
        $fkNewExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'ref_role_menu_permission')
            ->where('CONSTRAINT_NAME', 'ref_role_menu_permission_menu_menu_permission_id_foreign')
            ->exists();
        if (!$fkNewExists) {
            Schema::table('ref_role_menu_permission', function (Blueprint $table) {
                $table->foreign('menu_menu_permission_id')
                    ->references('id')
                    ->on('ref_menu_menu_permission')
                    ->onDelete('cascade');
            });
        }

        // Keep existing unique index; MySQL will retain it with renamed column
    }

    public function down(): void
    {
        $dbName = DB::getDatabaseName();
        // Drop new FK
        $fkNewExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'ref_role_menu_permission')
            ->where('CONSTRAINT_NAME', 'ref_role_menu_permission_menu_menu_permission_id_foreign')
            ->exists();
        if ($fkNewExists) {
            Schema::table('ref_role_menu_permission', function (Blueprint $table) {
                $table->dropForeign(['menu_menu_permission_id']);
            });
        }
        // Rename back
        Schema::table('ref_role_menu_permission', function (Blueprint $table) {
            if (Schema::hasColumn('ref_role_menu_permission', 'menu_menu_permission_id')) {
                $table->renameColumn('menu_menu_permission_id', 'menu_permission_id');
            }
        });
    }
};
