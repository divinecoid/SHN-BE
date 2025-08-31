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
        'qty' => 'integer',
        'harga' => 'decimal:2',
        'diskon' => 'decimal:2',
    ];
    protected $fillable = [
        'purchase_order_id',
        'panjang',
        'lebar',
        'tebal',
        'qty',
        'jenis_barang',
        'bentuk_barang',
        'grade_barang',
        'harga',
        'satuan',
        'diskon',
        'catatan',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
