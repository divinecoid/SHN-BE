<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pelanggan extends Model
{
    use SoftDeletes;
    protected $table = 'ref_pelanggan';
    protected $fillable = [
        'kode',
        'nama_pelanggan',
        'kota',
        'telepon_hp',
        'contact_person',
    ];
    protected $hidden = ['deleted_at'];
} 