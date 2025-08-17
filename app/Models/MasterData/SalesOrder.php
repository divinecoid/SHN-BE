<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;
    
    protected $table = 'trx_sales_order';
    
    protected $fillable = [
        'nomor_so',
        'tanggal_so',
        'tanggal_pengiriman',
        'syarat_pembayaran',
        'asal_gudang',
        'nama_pelanggan',
        'telepon',
        'email',
        'alamat',
    ];
    
    protected $hidden = ['deleted_at'];
    
    protected $casts = [
        'tanggal_so' => 'date',
        'tanggal_pengiriman' => 'date',
    ];
    
    /**
     * Get the sales order items
     */
    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}