<?php

namespace App\Models\Output;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\Transactions\WorkOrderPlanning;

class InvoicePodItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'out_invoicepoditem';
    
    protected $fillable = [
        'invoicepod_id',
        'work_order_planning_id',
        'nama_item',
        'unit',
        'dimensi_potong',
        'qty',
        'total_kg',
        'harga_per_unit',
        'total_harga',
    ];
    
    protected $hidden = ['deleted_at'];
    
    protected $casts = [
        'qty' => 'integer',
        'total_kg' => 'decimal:2',
        'harga_per_unit' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'unit' => 'string',
    ];
    
    /**
     * Get the invoice POD
     */
    public function invoicePod()
    {
        return $this->belongsTo(InvoicePod::class, 'invoicepod_id');
    }
    
    /**
     * Get the work order planning
     */
    public function workOrderPlanning()
    {
        return $this->belongsTo(WorkOrderPlanning::class, 'work_order_planning_id');
    }
}
