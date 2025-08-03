<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class JenisBiaya extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'ref_jenis_biaya';
    protected $fillable = [
        'kode',
        'jenis_biaya',
    ];

    protected $hidden = ['deleted_at'];
    
    /**
     * Get the jenis transaksi kas for this jenis biaya.
     */
    public function jenisTransaksiKas()
    {
        return $this->hasMany(JenisTransaksiKas::class, 'jenis_biaya_id');
    }
} 