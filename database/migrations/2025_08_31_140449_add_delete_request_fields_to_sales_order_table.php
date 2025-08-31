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
        Schema::table('trx_sales_order', function (Blueprint $table) {
            // Add delete request fields
            $table->enum('status', ['active', 'delete_requested', 'deleted'])->default('active')->after('total_harga_so');
            $table->unsignedBigInteger('delete_requested_by')->nullable()->after('status');
            $table->timestamp('delete_requested_at')->nullable()->after('delete_requested_by');
            $table->unsignedBigInteger('delete_approved_by')->nullable()->after('delete_requested_at');
            $table->timestamp('delete_approved_at')->nullable()->after('delete_approved_by');
            $table->text('delete_reason')->nullable()->after('delete_approved_at');
            $table->text('delete_rejection_reason')->nullable()->after('delete_reason');
            
            // Add foreign key constraints
            $table->foreign('delete_requested_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delete_approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_sales_order', function (Blueprint $table) {
            $table->dropForeign(['delete_requested_by']);
            $table->dropForeign(['delete_approved_by']);
            $table->dropColumn([
                'status',
                'delete_requested_by',
                'delete_requested_at',
                'delete_approved_by',
                'delete_approved_at',
                'delete_reason',
                'delete_rejection_reason'
            ]);
        });
    }
};
