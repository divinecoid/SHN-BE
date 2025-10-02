<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;
use App\Models\MasterData\ItemBarang;
use App\Models\Transactions\WorkOrderPlanningPelaksana;
use App\Models\Transactions\SaranPlatShaftDasar;

class WorkOrderPlanningItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_work_order_planning_item';

    protected $fillable = [
        'sales_order_item_id',
        'wo_item_unique_id',
        'work_order_planning_id',
        'panjang',
        'lebar',
        'tebal',
        'qty',
        'berat',
        'plat_dasar_id',
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'catatan',
        'is_assigned',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tebal' => 'decimal:2',
        'qty' => 'integer',
        'berat' => 'decimal:2',
        'is_assigned' => 'boolean',
    ];

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function workOrderPlanning()
    {
        return $this->belongsTo(WorkOrderPlanning::class);
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class);
    }
    
    public function bentukBarang()
    {
        return $this->belongsTo(BentukBarang::class);
    }
    
    
    public function gradeBarang()
    {
        return $this->belongsTo(GradeBarang::class);
    }

    public function hasManyPelaksana()
    {
        return $this->hasMany(WorkOrderPlanningPelaksana::class, 'wo_plan_item_id', 'id');
    }

    public function hasManySaranPlatShaftDasar()
    {
        return $this->hasMany(SaranPlatShaftDasar::class, 'wo_planning_item_id', 'id');
    }

    public function platDasar()
    {
        return $this->belongsTo(\App\Models\MasterData\ItemBarang::class, 'plat_dasar_id');
    }

    public function workOrderActualItem()
    {
        return $this->hasOne(WorkOrderActualItem::class, 'wo_plan_item_id', 'id');
    }
}
