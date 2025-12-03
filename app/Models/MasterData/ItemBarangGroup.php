<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class ItemBarangGroup extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'ref_item_barang_group';
    
    protected $fillable = [
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'panjang',
        'lebar',
        'tebal',
        'quantity_utuh',
        'quantity_potongan',
        'sequence',
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $casts = [
        'panjang' => 'integer',
        'lebar' => 'integer',
        'tebal' => 'integer',
        'quantity_utuh' => 'integer',
        'quantity_potongan' => 'integer',
        'sequence' => 'integer',
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

