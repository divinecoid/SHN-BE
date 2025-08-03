<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class JenisBarang extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'ref_jenis_barang';
    protected $fillable = [
        'kode',
        'nama_jenis',
    ];

    protected $hidden = ['deleted_at'];
}
