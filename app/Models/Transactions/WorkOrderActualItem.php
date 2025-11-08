<?php

namespace App\Models\Transactions;

use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\JenisBarang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class WorkOrderActualItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_work_order_actual_item';

    protected $fillable = [
        'work_order_actual_id',
        'wo_plan_item_id',
        'panjang_actual',
        'lebar_actual',
        'tebal_actual',
        'qty_actual',
        'berat_actual',
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'plat_dasar_id',
        'satuan',
        'diskon',
        'catatan',
        'foto_bukti',
    ];

    protected $hidden = ['deleted_at', 'berat_actual'];

    protected $casts = [
        'panjang_actual' => 'decimal:2',
        'lebar_actual' => 'decimal:2',
        'tebal_actual' => 'decimal:2',  
        'qty_actual' => 'integer',
        'berat_actual' => 'decimal:2',
        'diskon' => 'decimal:2',
    ];

    // Expose atribut 'berat' di API sebagai alias dari 'berat_actual'
    // Tambahkan juga qty_planning dan berat_planning agar mudah ditampilkan di UI
    protected $appends = ['berat', 'qty_planning', 'berat_planning'];

    public function getBeratAttribute()
    {
        return $this->berat_actual;
    }

    public function getQtyPlanningAttribute()
    {
        // Ambil qty dari item planning terkait (fallback ke null jika tidak ada)
        return optional($this->workOrderPlanningItem)->qty;
    }

    public function getBeratPlanningAttribute()
    {
        // Ambil berat dari item planning terkait (fallback ke null jika tidak ada)
        return optional($this->workOrderPlanningItem)->berat;
    }

    public function workOrderActual()
    {
        return $this->belongsTo(WorkOrderActual::class);
    }

    public function workOrderPlanningItem()
    {
        return $this->belongsTo(WorkOrderPlanningItem::class, 'wo_plan_item_id', 'id');
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
        return $this->belongsTo(ItemBarang::class, 'plat_dasar_id', 'id');
    }
    
    public function hasManyPelaksana()
    {
        return $this->hasMany(WorkOrderActualPelaksana::class, 'wo_actual_item_id');
    }
    
    /**
     * Get pelaksana data with related information
     */
    public function getPelaksana()
    {
        return $this->hasManyPelaksana()->with('pelaksana');
    }
    
    /**
     * Get pelaksana data with all related information
     */
    public function getPelaksanaWithDetails()
    {
        return $this->hasManyPelaksana()
            ->with(['pelaksana'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_mulai', 'desc');
    }
    
    
}
