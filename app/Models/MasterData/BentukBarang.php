<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BentukBarang extends Model
{
    use SoftDeletes;
    protected $table = 'ref_bentuk_barang';
    protected $fillable = [
        'kode',
        'nama_bentuk',
    ];
} 