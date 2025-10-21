<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class SaranPlatShaftDasar extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_saran_plat_shaft_dasar';

    protected $fillable = [
        'wo_planning_item_id',
        'item_barang_id',
        'quantity',
        'canvas_file',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'wo_planning_item_id' => 'integer',
        'item_barang_id' => 'integer',
        'quantity' => 'decimal:2',
    ];

    public function workOrderPlanningItem()
    {
        return $this->belongsTo(WorkOrderPlanningItem::class, 'wo_planning_item_id', 'id');
    }

    public function itemBarang()
    {
        return $this->belongsTo(\App\Models\MasterData\ItemBarang::class, 'item_barang_id');
    }

}
