<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
class WorkOrderPlanningItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_work_order_planning_item';

    protected $fillable = [
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
        return $this->hasMany(WorkOrderPlanningPelaksana::class);
    }

    public function hasManySaranPlatShaftDasar()
    {
        return $this->hasMany(SaranPlatShaftDasar::class);
    }

    public function platDasar()
    {
        return $this->belongsTo(\App\Models\MasterData\ItemBarang::class, 'plat_dasar_id');
    }
}
