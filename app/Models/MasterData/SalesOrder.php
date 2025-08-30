<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class SalesOrder extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'trx_sales_order';
    
    protected $fillable = [
        'nomor_so',
        'tanggal_so',
        'tanggal_pengiriman',
        'syarat_pembayaran',
        'gudang_id',
        'pelanggan_id',
        'subtotal',
        'total_diskon',
        'ppn_percent',
        'ppn_amount',
        'total_harga_so',
    ];
    
    protected $hidden = ['deleted_at'];
    
    protected $casts = [
        'tanggal_so' => 'date',
        'tanggal_pengiriman' => 'date',
        'subtotal' => 'decimal:2',
        'total_diskon' => 'decimal:2',
        'ppn_percent' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'total_harga_so' => 'decimal:2',
    ];
    
    /**
     * Get the sales order items
     */
    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
    
    /**
     * Get the pelanggan
     */
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }
    
    /**
     * Get the gudang
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}