<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ref_role_menu_permission', function (Blueprint $table) {
            if (!Schema::hasColumn('ref_role_menu_permission', 'menu_permission_id')) {
                $table->unsignedBigInteger('menu_permission_id')->nullable()->after('role_id');
            }
        });

        // Migrate existing data from (menu_id, permission_id) pairs into ref_menu_menu_permission
        $pairs = DB::table('ref_role_menu_permission')
            ->select('menu_id', 'permission_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            if ($pair->menu_id === null || $pair->permission_id === null) {
                continue;
            }

            $existing = DB::table('ref_menu_menu_permission')
                ->where('menu_id', $pair->menu_id)
                ->where('permission_id', $pair->permission_id)
                ->first();

            if (!$existing) {
                $id = DB::table('ref_menu_menu_permission')->insertGetId([
                    'menu_id' => $pair->menu_id,
                    'permission_id' => $pair->permission_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $existing = (object)['id' => $id];
            }

            DB::table('ref_role_menu_permission')
                ->where('menu_id', $pair->menu_id)
                ->where('permission_id', $pair->permission_id)
                ->update(['menu_permission_id' => $existing->id]);
        }

        // Add FK and unique constraint if not exists
        $dbName = DB::getDatabaseName();
        $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'ref_role_menu_permission')
            ->where('CONSTRAINT_NAME', 'ref_role_menu_permission_menu_permission_id_foreign')
            ->exists();

        if (!$fkExists) {
            Schema::table('ref_role_menu_permission', function (Blueprint $table) {
                $table->foreign('menu_permission_id')
                    ->references('id')
                    ->on('ref_menu_menu_permission')
                    ->onDelete('cascade');
            });
        }

        $uniqueExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'ref_role_menu_permission')
            ->where('INDEX_NAME', 'ref_role_menu_permission_role_id_menu_permission_id_unique')
            ->exists();
        if (!$uniqueExists) {
            Schema::table('ref_role_menu_permission', function (Blueprint $table) {
                $table->unique(['role_id', 'menu_permission_id']);
            });
        }

        Schema::table('ref_role_menu_permission', function (Blueprint $table) {
            // Drop old foreign keys before dropping columns
            if (Schema::hasColumn('ref_role_menu_permission', 'menu_id')) {
                $table->dropForeign(['menu_id']);
            }
            if (Schema::hasColumn('ref_role_menu_permission', 'permission_id')) {
                $table->dropForeign(['permission_id']);
            }
        });

        Schema::table('ref_role_menu_permission', function (Blueprint $table) {
            if (Schema::hasColumn('ref_role_menu_permission', 'menu_id')) {
                $table->dropColumn('menu_id');
            }
            if (Schema::hasColumn('ref_role_menu_permission', 'permission_id')) {
                $table->dropColumn('permission_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ref_role_menu_permission', function (Blueprint $table) {
            // Recreate old columns (nullable) for rollback
            if (!Schema::hasColumn('ref_role_menu_permission', 'menu_id')) {
                $table->unsignedBigInteger('menu_id')->nullable()->after('role_id');
            }
            if (!Schema::hasColumn('ref_role_menu_permission', 'permission_id')) {
                $table->unsignedBigInteger('permission_id')->nullable()->after('menu_id');
            }
            if (Schema::hasColumn('ref_role_menu_permission', 'menu_permission_id')) {
                $table->dropForeign(['menu_permission_id']);
                $table->dropUnique(['role_id', 'menu_permission_id']);
                $table->dropColumn('menu_permission_id');
            }
        });
    }
};
