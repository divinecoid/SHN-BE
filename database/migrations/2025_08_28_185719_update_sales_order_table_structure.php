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
            // Drop redundant columns
            $table->dropColumn([
                'nama_pelanggan',
                'telepon', 
                'email',
                'alamat'
            ]);
            
            // Change asal_gudang to foreign key
            $table->dropColumn('asal_gudang');
            $table->foreignId('gudang_id')->constrained('ref_gudang')->onDelete('restrict');
            
            // Change id_pelanggan to pelanggan_id for consistency
            $table->dropForeign(['id_pelanggan']);
            $table->dropColumn('id_pelanggan');
            $table->foreignId('pelanggan_id')->constrained('ref_pelanggan')->onDelete('restrict');
            
            // Add summary fields
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_diskon', 15, 2)->default(0);
            $table->decimal('ppn_percent', 5, 2)->default(0);
            $table->decimal('ppn_amount', 15, 2)->default(0);
            $table->decimal('total_harga_so', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trx_sales_order', function (Blueprint $table) {
            // Revert foreign key changes
            $table->dropForeign(['gudang_id']);
            $table->dropColumn('gudang_id');
            $table->string('asal_gudang');
            
            // Revert pelanggan_id changes
            $table->dropForeign(['pelanggan_id']);
            $table->dropColumn('pelanggan_id');
            $table->foreignId('id_pelanggan')->constrained('ref_pelanggan')->onDelete('restrict');
            
            // Revert redundant columns
            $table->string('nama_pelanggan');
            $table->string('telepon');
            $table->string('email');
            $table->text('alamat');
            
            // Drop summary fields
            $table->dropColumn([
                'subtotal',
                'total_diskon', 
                'ppn_percent',
                'ppn_amount',
                'total_harga_so'
            ]);
        });
    }
};
