<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemBarang extends Model
{
    use SoftDeletes;
    protected $table = 'ref_item_barang';
    protected $fillable = [
        'kode_barang',
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'nama_item_barang',
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