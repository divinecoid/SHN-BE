<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class PenerimaanBarangDetail extends Model
{
    use SoftDeletes, HideTimestampsInRelations;

    protected $table = 'trx_penerimaan_barang_detail';

    protected $fillable = [
        'id_penerimaan_barang',
        'id_stock_mutation_detail',
        'id_purchase_order_item',
        'id_rak',
        'qty',
        'id_item_barang',
    ];

    protected $hidden = ['deleted_at'];

    public function penerimaanBarang()
    {
        return $this->belongsTo(PenerimaanBarang::class, 'id_penerimaan_barang');
    }
    
    public function stockMutationDetail()
    {
        return $this->belongsTo(StockMutationDetail::class, 'id_stock_mutation_detail');
    }
    
    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'id_purchase_order_item');
    }
}
