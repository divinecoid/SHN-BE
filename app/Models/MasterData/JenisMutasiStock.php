<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class JenisMutasiStock extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'ref_jenis_mutasi_stock';
    protected $fillable = [
        'kode',
        'mutasi_stock',
        'jenis',
    ];
    protected $hidden = ['deleted_at'];
} 