<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class GradeBarang extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'ref_grade_barang';
    protected $fillable = [
        'kode',
        'nama',
    ];

    protected $hidden = ['deleted_at'];
} 