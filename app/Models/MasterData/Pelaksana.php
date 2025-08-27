<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class Pelaksana extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'ref_pelaksana';
    protected $fillable = [
        'kode',
        'nama_pelaksana',
        'level',
    ];   
    protected $hidden = ['deleted_at'];
} 