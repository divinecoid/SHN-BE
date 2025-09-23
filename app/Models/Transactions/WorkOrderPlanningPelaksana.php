<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
class WorkOrderPlanningPelaksana extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_work_order_planning_pelaksana';

    protected $fillable = [
        'wo_plan_item_id',
        'pelaksana_id',
        'qty',
        'weight',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'catatan',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai' => 'datetime:H:i:s',
        'jam_selesai' => 'datetime:H:i:s',
        'qty' => 'integer',
        'weight' => 'decimal:2',
    ];  

    public function workOrderPlanningItem()
    {
        return $this->belongsTo(WorkOrderPlanningItem::class, 'wo_plan_item_id', 'id');
    }

    public function pelaksana()
    {
        return $this->belongsTo(\App\Models\MasterData\Pelaksana::class);
    }
}
