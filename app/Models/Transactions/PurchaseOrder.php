<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class PurchaseOrder extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'trx_purchase_order';
    
    protected $fillable = [
        'nomor_po', 
        'id_supplier',
        'id_user_penerima',
        'id_gudang',
        'tanggal_po',
        'tanggal_penerimaan',
        'tanggal_jatuh_tempo',
        'tanggal_pembayaran',
    ];
    
    protected $hidden = ['deleted_at'];
    
    protected $casts = [
        'tanggal_po' => 'date',
        'tanggal_penerimaan' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'tanggal_pembayaran' => 'date',
    ];
    
    /**
     * Get the sales order items
     */
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}