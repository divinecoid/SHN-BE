<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
class WorkOrderPlanning extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_work_order_planning';

    protected $fillable = [
        'nomor_wo',
        'tanggal_wo',
        'id_sales_order',
        'id_pelanggan',
        'id_gudang',
        'id_pelaksana',
        'prioritas',
        'status',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'tanggal_wo' => 'date',
    ];

    public function workOrderPlanningItems()
    {
        return $this->hasMany(WorkOrderPlanningItem::class);
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }
}
