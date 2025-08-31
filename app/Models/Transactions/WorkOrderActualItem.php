<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class WorkOrderActualItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_work_order_actual_item';

    protected $fillable = [
        'work_order_actual_id',
        'panjang_actual',
        'lebar_actual',
        'tebal_actual',
        'qty_actual',
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'plat_dasar_id',
        'satuan',
        'diskon',
        'catatan',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'panjang_actual' => 'decimal:2',
        'lebar_actual' => 'decimal:2',
        'tebal_actual' => 'decimal:2',  
        'qty_actual' => 'integer',
        'diskon' => 'decimal:2',
    ];

    public function workOrderActual()
    {
        return $this->belongsTo(WorkOrderActual::class);
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

    public function platDasar()
    {
        return $this->belongsTo(ItemBarang::class);
    }
    
    public function hasManyPelaksana()
    {
        return $this->hasMany(WorkOrderActualPelaksana::class);
    }
    
    
}
