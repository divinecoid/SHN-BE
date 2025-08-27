<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class Supplier extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'ref_supplier';
    protected $fillable = [
        'kode',
        'nama_supplier',
        'kota',
        'telepon_hp',
        'contact_person',
    ];
    protected $hidden = ['deleted_at'];
} 