<?php

namespace App\Models\Output;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\MasterData\SalesOrder;
use App\Models\Transactions\WorkOrderActual;

class InvoicePod extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'out_invoicepod';
    
    protected $fillable = [
        'work_order_planning_id',
        'work_order_actual_id',
        'sales_order_id',
        'nomor_invoice',
        'tanggal_cetak_invoice',
        'nomor_pod',
        'tanggal_cetak_pod',
        'total_harga_invoice',
        'discount_invoice',
        'biaya_lain',
        'ppn_invoice',
        'grand_total',
        'uang_muka',
        'sisa_bayar',
        'status_bayar',
        'status_pod',
        'handover_method',
        'catatan',
    ];
    
    protected $hidden = ['deleted_at'];
    
    protected $casts = [
        'tanggal_cetak_invoice' => 'datetime',
        'tanggal_cetak_pod' => 'datetime',
        'total_harga_invoice' => 'decimal:2',
        'discount_invoice' => 'decimal:2',
        'biaya_lain' => 'decimal:2',
        'ppn_invoice' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'uang_muka' => 'decimal:2',
        'sisa_bayar' => 'decimal:2',
        'status_bayar' => 'string',
        'status_pod' => 'string',
        'handover_method' => 'string',
    ];
    
    /**
     * Get the work order planning
     */
    public function workOrderPlanning()
    {
        return $this->hasOne(WorkOrderPlanning::class, 'id', 'work_order_planning_id');
    }
    
    /**
     * Get the work order actual
    /**
     * Get the work order actual
     */
    public function workOrderActual()
    {
        return $this->hasOne(WorkOrderActual::class, 'id', 'work_order_actual_id');
    }
    
    /**
     * Get the sales order
     */
    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, 'id', 'sales_order_id');
    }
    
    /**
     * Get the invoice POD items
     */
    public function invoicePodItems()
    {
        return $this->hasMany(InvoicePodItem::class, 'invoicepod_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_pod_id');
    }
}
