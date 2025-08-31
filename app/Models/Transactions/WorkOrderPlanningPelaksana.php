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
        'work_order_planning_item_id',
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
        'jam_mulai' => 'time',
        'jam_selesai' => 'time',
        'qty' => 'integer',
        'weight' => 'decimal:2',
    ];  

    public function workOrderPlanningItem()
    {
        return $this->belongsTo(WorkOrderPlanningItem::class);
    }

    public function pelaksana()
    {
        return $this->belongsTo(Pelaksana::class);
    }
}
