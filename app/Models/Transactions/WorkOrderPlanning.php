<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\Output\InvoicePod;

class WorkOrderPlanning extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_work_order_planning';

    protected $fillable = [
        'wo_unique_id',
        'nomor_wo',
        'tanggal_wo',
        'id_sales_order',
        'id_pelanggan',
        'id_gudang',
        'id_pelaksana',
        'prioritas',
        'status',
        'handover_method',
        'estimate_done',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'tanggal_wo' => 'date',
        'estimate_done' => 'datetime',
    ];

    public function workOrderPlanningItems()
    {
        return $this->hasMany(WorkOrderPlanningItem::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(\App\Models\MasterData\SalesOrder::class, 'id_sales_order');
    }

    public function pelanggan()
    {
        return $this->belongsTo(\App\Models\MasterData\Pelanggan::class, 'id_pelanggan');
    }

    public function gudang()
    {
        return $this->belongsTo(\App\Models\MasterData\Gudang::class, 'id_gudang');
    }

    public function pelaksana()
    {
        return $this->belongsTo(\App\Models\MasterData\Pelaksana::class, 'id_pelaksana');
    }

    public function invoicePod()
    {
        return $this->hasOne(InvoicePod::class, 'work_order_planning_id', 'id');
    }
}
