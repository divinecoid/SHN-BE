<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class StockMutationItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;

    protected $table = 'trx_stock_mutation_detail';

    protected $fillable = [
        'stock_mutation_id',
        'item_barang_id',
        'unit',
        'quantity',
        'status'
    ];

    protected $hidden = ['deleted_at'];

    public function stockMutation()
    {
        return $this->belongsTo(StockMutation::class);
    }
    
}