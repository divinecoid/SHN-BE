<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\MasterData\Gudang;
use App\Models\Transactions\PurchaseOrder;
use App\Models\Transactions\StockMutation;
use App\Models\Transactions\PenerimaanBarangDetail;

class PenerimaanBarang extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'trx_penerimaan_barang';
    
    protected $fillable = [
        'origin',
        'id_purchase_order',
        'id_stock_mutation',
        'id_gudang',
        'catatan',
        'url_foto',
    ];
    
    protected $hidden = ['deleted_at'];
    
    /**
     * Get the item barang
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'id_purchase_order');
    }

    /**
     * Get the stock mutation
     */
    public function stockMutation()
    {
        return $this->belongsTo(StockMutation::class, 'id_stock_mutation');
    }
    
    /**
     * Get the gudang
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang');
    }

    /**
     * Get the penerimaan barang details
     */
    public function penerimaanBarangDetails()
    {
        return $this->hasMany(PenerimaanBarangDetail::class, 'id_penerimaan_barang');
    }
}
