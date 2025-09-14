<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class PurchaseOrderItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_purchase_order_item';
    
    protected $hidden = ['deleted_at'];

    protected $casts = [
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tebal' => 'decimal:2',
        'berat' => 'decimal:2',
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];
    protected $fillable = [
        'purchase_order_id',
        'id_item_barang',
        'qty',
        'harga_satuan',
        'subtotal',
        'panjang',
        'lebar',
        'tebal',
        'berat',
        'catatan',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
