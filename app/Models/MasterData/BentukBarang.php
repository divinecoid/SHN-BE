<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class BentukBarang extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'ref_bentuk_barang';
    protected $fillable = [
        'kode',
        'nama_bentuk',
        'dimensi',
    ];

    protected $hidden = ['deleted_at'];
} 