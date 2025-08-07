<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisMutasiStock extends Model
{
    use SoftDeletes;
    protected $table = 'ref_jenis_mutasi_stock';
    protected $fillable = [
        'kode',
        'mutasi_stock',
        'jenis',
    ];
    protected $hidden = ['deleted_at'];
} 