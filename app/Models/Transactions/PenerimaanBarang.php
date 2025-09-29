<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class PenerimaanBarang extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'trx_penerimaan_barang';
    
    protected $fillable = [
        'id_item_barang',
        'id_gudang',
        'id_rak',
        'jumlah_barang',
        'catatan',
    ];
    
    protected $hidden = ['deleted_at'];
    
    /**
     * Get the item barang
     */
    public function itemBarang()
    {
        return $this->belongsTo(ItemBarang::class, 'id_item_barang');
    }
    
    /**
     * Get the gudang
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang');
    }
    
    /**
     * Get the rak
     */
    public function rak()
    {
        return $this->belongsTo(Gudang::class, 'id_rak');
    }
}
