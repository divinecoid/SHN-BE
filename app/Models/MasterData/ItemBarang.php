<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\MasterData\Gudang;

class ItemBarang extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'ref_item_barang';
    protected $fillable = [
        'kode_barang',
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'nama_item_barang',
        'sisa_luas',
        'panjang',
        'lebar',
        'tebal',
        'quantity',
        'quantity_tebal_sama',
        'jenis_potongan',
        'is_available',
        'is_edit',
        'user_id',
        'canvas_file',
        'canvas_image',
        'convert_date',
        'gudang_id'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $casts = [
        'jenis_potongan' => 'string',
        'is_available' => 'boolean',
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
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}