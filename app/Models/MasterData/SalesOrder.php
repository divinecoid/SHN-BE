<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class SalesOrder extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
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
        'handover_method',
        'status',
        'is_wo_qty_matched',
        'delete_requested_by',
        'delete_requested_at',
        'delete_approved_by',
        'delete_approved_at',
        'delete_reason',
        'delete_rejection_reason',
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
        'handover_method' => 'string',
        'is_wo_qty_matched' => 'boolean',
        'delete_requested_at' => 'datetime',
        'delete_approved_at' => 'datetime',
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
    
    /**
     * Get the user who requested deletion
     */
    public function deleteRequestedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'delete_requested_by');
    }
    
    /**
     * Get the admin who approved deletion
     */
    public function deleteApprovedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'delete_approved_by');
    }
    
    /**
     * Scope to get only active sales orders
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    /**
     * Scope to get only delete requested sales orders
     */
    public function scopeDeleteRequested($query)
    {
        return $query->where('status', 'delete_requested');
    }

    
}
