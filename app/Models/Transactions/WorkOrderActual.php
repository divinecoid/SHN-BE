<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class WorkOrderActual extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_work_order_actual';

    protected $fillable = [
        'work_order_planning_id',
        'tanggal_actual',
        'status',
        'catatan',
        'foto_bukti',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'tanggal_actual' => 'date',
    ];

    public function workOrderActualItems()
    {
        return $this->hasMany(WorkOrderActualItem::class);
    }

    public function workOrderPlanning()
    {
        return $this->belongsTo(WorkOrderPlanning::class);
    }

    public function workOrderActualPelaksana()
    {
        return $this->hasMany(WorkOrderActualPelaksana::class);
    }
}
