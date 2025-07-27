<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    protected $table = 'ref_supplier';
    protected $fillable = [
        'kode',
        'nama_supplier',
        'kota',
        'telepon_hp',
        'contact_person',
    ];
} 