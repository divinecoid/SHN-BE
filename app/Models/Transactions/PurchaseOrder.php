<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\Transactions\Payment;
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
        'total_amount',
        'jumlah_dibayar',
        'status',
        'status_bayar',
        'catatan',
    ];
    
    protected $hidden = ['deleted_at'];
    
    protected $casts = [
        'tanggal_po' => 'date',
        'tanggal_penerimaan' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'tanggal_pembayaran' => 'date',
        'total_amount' => 'decimal:2',
        'jumlah_dibayar' => 'decimal:2',
    ];
    
    /**
     * Get the sales order items
     */
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    
    /**
     * Get the supplier
     */
    public function supplier()
    {
        return $this->belongsTo(\App\Models\MasterData\Supplier::class, 'id_supplier');
    }
    
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}