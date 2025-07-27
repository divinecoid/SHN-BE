<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeBarang extends Model
{
    use SoftDeletes;
    protected $table = 'ref_grade_barang';
    protected $fillable = [
        'kode',
        'nama',
    ];
} 