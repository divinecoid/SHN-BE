<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ref_document_sequence', function (Blueprint $table) {
            if (!Schema::hasColumn('ref_document_sequence', 'item_barang_request')) {
                $table->unsignedInteger('item_barang_request')->default(0)->after('mutasi');
            }
        });

        // Migrate existing values from 'receipt' column if present
        if (Schema::hasColumn('ref_document_sequence', 'receipt')) {
            DB::table('ref_document_sequence')->update([
                'item_barang_request' => DB::raw('COALESCE(item_barang_request, receipt)')
            ]);
        }

        // Optional: drop old 'receipt' column to avoid confusion
        Schema::table('ref_document_sequence', function (Blueprint $table) {
            if (Schema::hasColumn('ref_document_sequence', 'receipt')) {
                $table->dropColumn('receipt');
            }
        });
    }

    public function down(): void
    {
        // Recreate 'receipt' column for rollback
        Schema::table('ref_document_sequence', function (Blueprint $table) {
            if (!Schema::hasColumn('ref_document_sequence', 'receipt')) {
                $table->unsignedInteger('receipt')->default(0)->after('mutasi');
            }
        });

        // Move values back
        DB::table('ref_document_sequence')->update([
            'receipt' => DB::raw('COALESCE(receipt, item_barang_request)')
        ]);

        // Drop new column
        Schema::table('ref_document_sequence', function (Blueprint $table) {
            if (Schema::hasColumn('ref_document_sequence', 'item_barang_request')) {
                $table->dropColumn('item_barang_request');
            }
        });
    }
};

