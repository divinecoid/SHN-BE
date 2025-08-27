<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class SalesOrderItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'trx_sales_order_item';
    
    protected $fillable = [
        'sales_order_id',
        'panjang',
        'lebar',
        'qty',
        'jenis_barang',
        'bentuk_barang',
        'grade_barang',
        'harga',
        'satuan',
        'diskon',
        'catatan',
    ];
    
    protected $hidden = ['deleted_at'];
    
    protected $casts = [
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'qty' => 'integer',
        'harga' => 'decimal:2',
        'diskon' => 'decimal:2',
    ];
    
    /**
     * Get the sales order that owns the item
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
}