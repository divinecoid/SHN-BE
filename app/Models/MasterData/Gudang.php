<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gudang extends Model
{
    use SoftDeletes;
    protected $table = 'ref_gudang';
    protected $fillable = [
        'kode',
        'nama_gudang',
        'telepon_hp',
    ];
} 