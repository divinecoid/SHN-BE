<?php

namespace App\Models\Transactions;

use App\Models\MasterData\ItemBarang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpnameDetail extends Model
{
    use SoftDeletes;

    protected $table = 'trx_stock_opname_detail';

    protected $fillable = [
        'stock_opname_id',
        'item_barang_id',
        'status',
        'stok_buku',
        'stok_asli',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'stok_buku' => 'integer',
        'stok_asli' => 'integer',
    ];

    /**
     * Get the stock opname (header)
     */
    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    /**
     * Get the item barang
     */
    public function itemBarang()
    {
        return $this->belongsTo(ItemBarang::class, 'item_barang_id');
    }
}

