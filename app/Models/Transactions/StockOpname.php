<?php

namespace App\Models\Transactions;

use App\Models\MasterData\Gudang;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpname extends Model
{
    use SoftDeletes;

    protected $table = 'trx_stock_opname';

    protected $fillable = [
        'pic_user_id',
        'gudang_id',
        'catatan',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Get the PIC user
     */
    public function picUser()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /**
     * Get the gudang
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    /**
     * Get the stock opname details
     */
    public function stockOpnameDetails()
    {
        return $this->hasMany(StockOpnameDetail::class, 'stock_opname_id');
    }
}
