<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class BeratJenis extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'ref_berat_jenis';
    
    protected $fillable = [
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'berat_per_cm',
        'berat_per_luas',
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $casts = [
        'berat_per_cm' => 'decimal:4',
        'berat_per_luas' => 'decimal:4',
    ];

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }

    public function bentukBarang()
    {
        return $this->belongsTo(BentukBarang::class, 'bentuk_barang_id');
    }

    public function gradeBarang()
    {
        return $this->belongsTo(GradeBarang::class, 'grade_barang_id');
    }
}

