<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisBiaya extends Model
{
    use SoftDeletes;
    protected $table = 'ref_jenis_biaya';
    protected $fillable = [
        'kode',
        'jenis_biaya',
    ];
} 