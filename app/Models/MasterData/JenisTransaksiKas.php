<?php


namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisTransaksiKas extends Model
{
    use SoftDeletes;
    
    protected $table = 'ref_jenis_transaksi_kas';
    
    protected $fillable = [
        'jenis_biaya_id',
        'keterangan',
        'jumlah',
    ];

    protected $hidden = [
        'deleted_at'
    ];
    
    protected $casts = [
        'jumlah' => 'decimal:2',
    ];
    
    /**
     * Get the jenis biaya that owns the transaksi kas.
     */
    public function jenisBiaya()
    {
        return $this->belongsTo(JenisBiaya::class, 'jenis_biaya_id');
    }
}
