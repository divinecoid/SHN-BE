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
        'sisa_panjang',
        'sisa_lebar',
        'panjang',
        'lebar',
        'tebal',
        'berat',
        'quantity',
        'quantity_tebal_sama',
        'jenis_potongan',
        'is_available',
        'is_edit',
        'is_onprogress_po',
        'user_id',
        'canvas_file',
        'canvas_image',
        'convert_date',
        'split_date',
        'merge_date',
        'gudang_id',
        'frozen_at',
        'frozen_by'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $casts = [
        'jenis_potongan' => 'string',
        'is_available' => 'boolean',
        'is_edit' => 'boolean',
        'is_onprogress_po' => 'boolean',
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tebal' => 'decimal:2',
        'berat' => 'decimal:2',
        'sisa_panjang' => 'decimal:2',
        'sisa_lebar' => 'decimal:2',
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
    public function stockOpnameDetails()
    {
        return $this->hasMany(StockOpnameDetail::class, 'item_barang_id');
    }
}