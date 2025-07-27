<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisBarang extends Model
{
    use SoftDeletes;
    protected $table = 'ref_jenis_barang';
    protected $fillable = [
        'kode',
        'nama_jenis',
    ];
}
